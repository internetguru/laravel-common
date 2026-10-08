<?php

namespace InternetGuru\LaravelCommon\Testing;

use DOMDocument;
use DOMNodeList;
use DOMXPath;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;

/**
 * Pest tests every application built on laravel-common should pass, registered from one of its test files:
 *
 *     CommonTests::register(languages: ['en', 'cs']);
 */
class CommonTests
{
    /**
     * @param  list<string>  $languages  the languages the switch offers
     * @param  string|null  $subpage  a page one breadcrumb level below the homepage
     * @param  bool  $breadcrumb  false for a site designed without a breadcrumb
     */
    public static function register(array $languages = ['en', 'cs'], bool $demo = false, ?string $subpage = '/login', bool $breadcrumb = true): void
    {
        describe('laravel-common layout', function () {
            it('renders the header, main and footer with the required meta tags', function () {
                $page = CommonTests::page($this->get('/')->assertOk());

                expect($page->query('//header'))->toHaveCount(1)
                    ->and($page->query('//main'))->toHaveCount(1)
                    ->and($page->query('//footer'))->toHaveCount(1)
                    ->and($page->attribute('//meta[@charset]', 'charset'))->toBe('utf-8')
                    ->and($page->attribute('//meta[@name="viewport"]', 'content'))->toContain('width=device-width')
                    ->and($page->text('//title'))->not->toBe('')
                    ->and($page->attribute('//html', 'lang'))->toMatch('/^[a-z]{2}/')
                    ->and($page->attribute('//meta[@name="csrf-token" or @name="csrf_token"]', 'content'))->not->toBe('')
                    ->and($page->query('//h1'))->toHaveCount(1)
                    ->and($page->query('//*[contains(@class, "messages-wrapper")]')->length)->toBeGreaterThanOrEqual(1);
            });
        });

        if ($breadcrumb) {
            describe('laravel-common breadcrumb', function () use ($subpage) {
                it('ends with the active page', function () {
                    $items = CommonTests::page($this->get('/'))->query(CommonTests::BREADCRUMB_ITEMS);

                    expect($items->length)->toBeGreaterThanOrEqual(1)
                        ->and($items->item($items->length - 1)->getAttribute('class'))->toContain('active');
                });

                if ($subpage !== null) {
                    it('grows on a subpage', function () use ($subpage) {
                        $home = CommonTests::page($this->get('/'))->query(CommonTests::BREADCRUMB_ITEMS)->length;
                        $sub = CommonTests::page($this->get($subpage))->query(CommonTests::BREADCRUMB_ITEMS)->length;

                        expect($sub)->toBeGreaterThan($home);
                    });
                }
            });
        }

        if (count($languages) > 1) {
            describe('laravel-common language switch', function () use ($languages) {
                it('offers every language and highlights the current one', function () use ($languages) {
                    $page = CommonTests::page($this->get('/'));

                    expect($page->query('//*[@data-testid="lang-switch"]/li'))->toHaveCount(count($languages))
                        ->and($page->query('//*[@data-testid="lang-switch"]//strong'))->toHaveCount(1);
                });

                it('switches the language and keeps it on the next page', function () use ($languages) {
                    $other = $languages[1];

                    expect(CommonTests::page($this->followingRedirects()->get("/?lang=$other"))->attribute('//html', 'lang'))->toStartWith($other)
                        ->and(CommonTests::page($this->get('/'))->attribute('//html', 'lang'))->toStartWith($other);
                });
            });
        }

        describe('laravel-common error pages', function () {
            it('lists the error pages', function () {
                expect(CommonTests::page($this->get('/error')->assertOk())->query('//*[contains(@class, "error")]//a')->length)
                    ->toBeGreaterThanOrEqual(8);
            });

            it('renders each error page with its status', function (int $status) {
                config(['app.debug' => false]);

                $response = $this->get("/error/$status")->assertStatus($status);

                expect(CommonTests::page($response)->text('//h1'))->toContain((string) $status);
            })->with([401, 403, 404, 500, 503]);

            it('answers an unknown error code and an unknown page with 404', function () {
                $this->get('/error/999')->assertNotFound();
                $this->get('/nonexistent-page-' . uniqid())->assertNotFound();
            });
        });

        describe('laravel-common i18n pages', function () {
            it('renders the translation overview pages', function (string $uri) {
                expect(CommonTests::page($this->get($uri)->assertOk())->query('//h1'))->toHaveCount(1);
            })->with(['/i18n', '/i18n/complete', '/i18n/missing-all', '/i18n/missing-cs', '/i18n/missing-en']);
        });

        if ($demo) {
            it('shows the demo banner', function () {
                expect(CommonTests::page($this->get('/'))->query('//*[@data-testid="demo-info"]'))->toHaveCount(1);
            });
        }
    }

    public const BREADCRUMB_ITEMS = '//*[@data-testid="breadcrumb"]//li[contains(@class, "breadcrumb-item")]';

    public static function page(TestResponse $response): self
    {
        return new self((string) $response->getContent());
    }

    private DOMXPath $xpath;

    private function __construct(string $html)
    {
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        $this->xpath = new DOMXPath($document);
    }

    /**
     * @return DOMNodeList<\DOMNode>
     */
    public function query(string $xpath): DOMNodeList
    {
        return $this->xpath->query($xpath) ?: throw new InvalidArgumentException("Invalid XPath: $xpath");
    }

    public function text(string $xpath): string
    {
        return trim($this->query($xpath)->item(0)?->textContent ?? '');
    }

    public function attribute(string $xpath, string $name): string
    {
        $node = $this->query($xpath)->item(0);

        return $node instanceof \DOMElement ? $node->getAttribute($name) : '';
    }
}
