<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_the_home_page_renders(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Richard Hanrick');
    }
}
