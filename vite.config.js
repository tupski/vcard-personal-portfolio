import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Admin / auth UI (Tailwind).
                'resources/css/app.css',
                // Public portfolio: a faithful port of the original
                // assets/css/style.css (see resources/css/frontend.css).
                'resources/css/frontend.css',
                'resources/js/app.js',
            ],
            refresh: true,
            fonts: [
                // The original template loaded Poppins 300–600 from Google
                // Fonts. Bunny serves the same family self-hosted.
                bunny('Poppins', {
                    weights: [300, 400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
