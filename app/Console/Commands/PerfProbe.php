<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Development-only performance probe.
 *
 * Reports the query count, query time and rendered size for every public page
 * by dispatching the real route through the HTTP kernel, so the numbers come
 * from the actual application rather than from a re-implementation.
 *
 * This exists so optimization decisions are based on measurement instead of
 * guesswork. It is not used at runtime.
 */
class PerfProbe extends Command
{
    protected $signature = 'perf:probe {--repeat=3 : Requests per route to report a median}';

    protected $description = 'Measure query count, query time and HTML size for public pages';

    /**
     * Public routes to probe, in a stable order.
     *
     * @var list<string>
     */
    private const ROUTES = ['home', 'resume', 'portfolio', 'blog', 'contact'];

    public function handle(): int
    {
        $repeat = max(1, (int) $this->option('repeat'));

        $targets = [];

        foreach (self::ROUTES as $name) {
            if (Route::has($name)) {
                $targets[$name] = route($name, absolute: false);
            }
        }

        // One real published post, when the blog has any.
        $slug = DB::table('blog_posts')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->value('slug');

        if (is_string($slug) && $slug !== '') {
            $targets['blog.show'] = '/blog/'.$slug;
        }

        if (Route::has('sitemap')) {
            $targets['sitemap'] = '/sitemap.xml';
        }

        $rows = [];

        foreach ($targets as $name => $uri) {
            $samples = [];

            for ($i = 0; $i < $repeat; $i++) {
                $samples[] = $this->measure($uri);
            }

            // The first request populates the content cache, so it is reported
            // separately: "cold" is a one-off cost, "warm" is what every
            // subsequent visitor pays and therefore the number that matters.
            $warm = array_slice($samples, 1) ?: $samples;

            $rows[] = [
                'route' => $name,
                'uri' => $uri,
                'cold' => $samples[0]['queries'],
                'warm' => (int) $this->median(array_column($warm, 'queries')),
                'query_ms' => round($this->median(array_column($warm, 'query_ms')), 2),
                'total_ms' => round($this->median(array_column($warm, 'total_ms')), 2),
                'kb' => round($this->median(array_column($warm, 'bytes')) / 1024, 1),
            ];
        }

        $this->table(
            ['route', 'uri', 'cold q', 'warm q', 'query ms', 'total ms', 'size KB'],
            $rows,
        );

        return self::SUCCESS;
    }

    /**
     * Dispatch one request and collect its metrics.
     *
     * @return array{queries: int, query_ms: float, total_ms: float, bytes: int}
     */
    private function measure(string $uri): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $started = microtime(true);

        $response = $this->laravel->handle(
            Request::create($uri, 'GET'),
        );

        $totalMs = (microtime(true) - $started) * 1000;

        $log = DB::getQueryLog();

        DB::disableQueryLog();

        $content = (string) $response->getContent();

        return [
            'queries' => count($log),
            'query_ms' => array_sum(array_column($log, 'time')),
            'total_ms' => $totalMs,
            'bytes' => strlen($content),
        ];
    }

    /**
     * @param  list<float>  $values
     */
    private function median(array $values): float
    {
        sort($values);

        $count = count($values);
        $middle = intdiv($count, 2);

        return $count % 2 === 1
            ? (float) $values[$middle]
            : ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }
}
