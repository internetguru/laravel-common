<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;
use Illuminate\View\View;
use InternetGuru\LaravelCommon\Exceptions\LabelException;

/**
 * The heading of a page with a remark about it, given as the slot. With a badge,
 * the badge stands above the heading and the remark is its tooltip; without one,
 * the remark follows the heading as a line of its own. The variant colors either
 * and picks the icon that goes with it.
 */
class PageHeading extends Component
{
    public const ICONS = [
        'info' => 'fa-circle-info',
        'success' => 'fa-circle-check',
        'warning' => 'fa-triangle-exclamation',
        'danger' => 'fa-circle-exclamation',
    ];

    public string $badgeIcon;

    /**
     * @param  string|Htmlable  $heading  The heading itself; an Htmlable keeps its markup.
     * @param  string|null  $badge  The text of a badge above the heading.
     * @param  string  $variant  One of info, success, warning or danger.
     * @param  string|null  $icon  Font Awesome classes replacing the variant's icon on the badge.
     * @param  bool  $tooltip  With a badge, make the remark its tooltip; false writes it below the heading.
     */
    public function __construct(
        public string|Htmlable $heading,
        public ?string $badge = null,
        public string $variant = 'info',
        ?string $icon = null,
        public bool $tooltip = true,
    ) {
        if (! isset(self::ICONS[$variant])) {
            $name = $heading instanceof Htmlable ? strip_tags($heading->toHtml()) : $heading;
            $this->fail("Page heading '{$name}' has an unknown variant: {$variant}");
            $this->variant = 'info';
        }
        $this->badgeIcon = $icon ?? 'fa-solid fa-fw ' . self::ICONS[$this->variant];
    }

    protected function fail(string $message): void
    {
        if (App::hasDebugModeEnabled()) {
            throw new LabelException($message);
        }

        Log::warning($message);
    }

    public function render(): View
    {
        return view('ig-common::components.page-heading');
    }
}
