<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\Support\Arr;
use Illuminate\View\Component;
use Illuminate\View\View;

class Card extends Component
{
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
    ) {
        $this->badges = array_values(Arr::wrap($badge));
        $this->level = max(1, min(6, $level));
        $this->linkLabel = $linkLabel ?? __('ig-common::layouts.card.open');
        $this->gray = filter_var($gray, FILTER_VALIDATE_BOOLEAN);
    }

    public function render(): View
    {
        return view('ig-common::components.card');
    }
}
