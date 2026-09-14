<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\Support\Arr;
use Illuminate\View\Component;
use Illuminate\View\View;

class Card extends Component
{
    /**
     * File types a link is marked with, so a card leading to a download says so
     * before it is followed. Anything else is taken for a web page.
     *
     * @var array<int, string>
     */
    protected const LINKED_FORMATS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'csv', 'zip'];

    /** The kind of file the link leads to, shown next to the button; null for a web page. */
    public ?string $format;

    /**
     * Chips shown above the heading.
     *
     * @var array<int, string>
     */
    public array $badges;

    /** Accessible name of the corner link, which is only an icon. */
    public string $linkLabel;

    /** Puts the card on a grey surface, with its heading and content centred. */
    public bool $gray;

    /** Heading level of the title, so a card keeps the outline of the page it sits on. */
    public int $level;

    /**
     * @param  array<int, string>|string|null  $badge  Text of the chip above the heading, or several of them.
     * @param  string|null  $format  File type of the link, read off its extension when not given; an empty string leaves the link unmarked.
     */
    public function __construct(
        public ?string $title = null,
        int $level = 4,
        public ?string $subtitle = null,
        array|string|null $badge = null,
        public ?string $badgeType = null,
        public ?string $link = null,
        ?string $linkLabel = null,
        bool|string $gray = false,
        public string $icon = 'fa-solid fa-arrow-up-right-from-square',
        ?string $format = null,
    ) {
        $this->badges = array_values(Arr::wrap($badge));
        $this->level = max(1, min(6, $level));
        $this->gray = filter_var($gray, FILTER_VALIDATE_BOOLEAN);
        $this->format = $format === null ? $this->formatOf($link) : ($format ?: null);

        $linkLabel ??= __('ig-common::layouts.card.open');

        $this->linkLabel = $this->labelWithFormat($linkLabel);
    }

    /**
     * The file type a link leads to, read off its path: either the extension it
     * ends in, or a folder named after the type, as a paper served from
     * "/pdf/2607.21166" is. A query string or fragment behind the path is left
     * out of both, so a parameter is never taken for the file itself.
     */
    protected function formatOf(?string $link): ?string
    {
        if ($link === null) {
            return null;
        }

        $path = parse_url($link, PHP_URL_PATH) ?: '';
        $segments = explode('/', strtolower($path));
        $extension = pathinfo(end($segments), PATHINFO_EXTENSION);

        foreach ([$extension, ...array_slice($segments, 0, -1)] as $candidate) {
            if (in_array($candidate, self::LINKED_FORMATS, true)) {
                return strtoupper($candidate);
            }
        }

        return null;
    }

    /**
     * The button carries only an icon, so its accessible name is the one place
     * the file type can be spelled out - unless the given label says it
     * already, which would otherwise be read out twice.
     */
    protected function labelWithFormat(string $linkLabel): string
    {
        if ($this->format === null || str_contains(strtolower($linkLabel), strtolower($this->format))) {
            return $linkLabel;
        }

        return "$linkLabel ($this->format)";
    }

    public function render(): View
    {
        return view('ig-common::components.card');
    }
}
