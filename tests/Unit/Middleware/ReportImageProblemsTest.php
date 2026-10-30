<?php

namespace Tests\Unit\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use InternetGuru\LaravelCommon\Http\Middleware\ReportImageProblems;
use Tests\TestCase;

class ReportImageProblemsTest extends TestCase
{
    private function withImageRoute(): void
    {
        config(['ig-images.route' => 'img']);
        Route::get('/img/{path}', fn () => '')->where('path', '.*')->name('ig-images.show');
        Route::getRoutes()->refreshNameLookups();
    }

    private function handle(string $body, string $path = '/'): Response
    {
        $html = "<html><head></head><body>{$body}</body></html>";

        return (new ReportImageProblems)->handle(
            Request::create($path),
            fn () => new Response($html, 200, ['Content-Type' => 'text/html'])
        );
    }

    public function test_a_complete_image_is_left_alone()
    {
        Log::spy();
        $body = '<img src="a.jpg" width="10" height="10" alt="A">';

        $response = $this->handle($body);

        $this->assertStringContainsString($body, $response->getContent());
        Log::shouldNotHaveReceived('log');
    }

    public function test_each_problem_is_logged_at_the_level_of_the_most_severe()
    {
        Log::spy();

        $this->handle('<img src="a.jpg" width="10" alt=""><img src="b.jpg" width="10" height="10">');

        Log::shouldHaveReceived('log')->once()->with('warning', 'Image problems', [
            'url' => 'http://localhost',
            'images' => ['a.jpg' => 'missing height, empty alt', 'b.jpg' => 'missing alt'],
        ]);
    }

    public function test_missing_alt_is_a_notice_and_empty_alt_is_info()
    {
        Log::spy();

        $this->handle('<img src="a.jpg" width="10" height="10">', '/one');
        $this->handle('<img src="a.jpg" width="10" height="10" alt="">', '/two');

        Log::shouldHaveReceived('log')->with('notice', 'Image problems', \Mockery::any());
        Log::shouldHaveReceived('log')->with('info', 'Image problems', \Mockery::any());
    }

    public function test_the_same_problems_on_a_page_are_logged_once_a_day()
    {
        Log::spy();

        $this->handle('<img src="a.jpg">');
        $this->handle('<img src="a.jpg">');
        $this->travel(25)->hours();
        $this->handle('<img src="a.jpg">');

        Log::shouldHaveReceived('log')->twice();
    }

    public function test_attributes_bound_in_the_browser_count_as_present()
    {
        Log::spy();

        $this->handle('<img :src="url" :width="w" x-bind:height="h" :alt="name">');

        Log::shouldNotHaveReceived('log');
    }

    public function test_an_attribute_named_inside_another_value_does_not_count()
    {
        Log::spy();

        $this->handle('<img src="a.jpg" data-x="width=1 height=1" alt="width">');

        Log::shouldHaveReceived('log')->once()->with('warning', 'Image problems', \Mockery::any());
    }

    public function test_images_are_marked_only_in_debug_mode()
    {
        config(['app.debug' => true]);
        $marked = $this->handle('<img src="a.jpg" width="10" height="10">')->getContent();

        config(['app.debug' => false]);
        $plain = $this->handle('<img src="b.jpg" width="10" height="10">')->getContent();

        $this->assertStringContainsString('<img data-ig-image-warning="medium" title="Image: missing alt" src="a.jpg"', $marked);
        $this->assertStringContainsString('<style>', $marked);
        $this->assertStringNotContainsString('data-ig-image-warning', $plain);
        $this->assertStringNotContainsString('<style>', $plain);
    }

    public function test_an_existing_title_is_kept()
    {
        config(['app.debug' => true]);

        $content = $this->handle('<img src="a.jpg" title="Own" alt="A">')->getContent();

        $this->assertStringContainsString('<img data-ig-image-warning="severe" src="a.jpg" title="Own"', $content);
    }

    public function test_nothing_happens_when_turned_off()
    {
        config(['ig-common.image_warnings' => false, 'app.debug' => true]);
        Log::spy();

        $content = $this->handle('<img src="a.jpg">')->getContent();

        $this->assertStringNotContainsString('data-ig-image-warning', $content);
        Log::shouldNotHaveReceived('log');
    }

    public function test_non_html_responses_are_ignored()
    {
        Log::spy();

        (new ReportImageProblems)->handle(Request::create('/'), fn () => response()->json(['html' => '<img src="a.jpg">']));

        Log::shouldNotHaveReceived('log');
    }

    public function test_a_local_photo_outside_laravel_images_is_critical()
    {
        $this->withImageRoute();
        Log::spy();

        $this->handle('<img src="/storage/a.jpg" width="1" height="1" alt="A"><img src="http://localhost/b.PNG" width="1" height="1" alt="B"><img src="/build/assets/c-1a2b.webp" width="1" height="1" alt="C">');

        Log::shouldHaveReceived('log')->once()->with('error', 'Image problems', [
            'url' => 'http://localhost',
            'images' => [
                '/storage/a.jpg' => 'not served by laravel-images',
                'http://localhost/b.PNG' => 'not served by laravel-images',
                '/build/assets/c-1a2b.webp' => 'not served by laravel-images',
            ],
        ]);
    }

    public function test_images_laravel_images_cannot_or_need_not_serve_are_not_critical()
    {
        $this->withImageRoute();
        Log::spy();

        $this->handle(implode('', array_map(fn ($src) => "<img src=\"{$src}\" width=\"1\" height=\"1\" alt=\"A\">", [
            '/img/a.jpg?w=10&s=x',
            '/logo.svg',
            'https://example.com/a.jpg',
            'http://localhost:5173/resources/images/a.jpg',
            'data:image/png;base64,AAAA',
        ])));

        Log::shouldNotHaveReceived('log');
    }

    public function test_local_photos_are_not_critical_without_laravel_images()
    {
        Log::spy();

        $this->handle('<img src="/storage/a.jpg" width="1" height="1" alt="A">');

        Log::shouldNotHaveReceived('log');
    }

    public function test_the_image_check_page_shows_each_level()
    {
        $this->withImageRoute();
        config(['app.debug' => true]);

        $response = $this->get(route('image-check.index'))->assertOk();

        foreach (['critical', 'severe', 'medium', 'low'] as $level) {
            $response->assertSee("data-ig-image-warning=\"{$level}\"", false);
        }
        $this->get(route('image-check.sample'))->assertOk()->assertHeader('Content-Type', 'image/png');
    }
}
