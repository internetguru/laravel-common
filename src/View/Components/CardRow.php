<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class CardRow extends Component
{
    /** Accessible name of the group of cards. */
    public string $label;

    /** Scrolls sideways with arrows, rather than wrapping onto as many lines as needed. */
    public bool $isCarousel;

    /** Tints each card with a colour derived from the label. */
    public bool $isTinted;

    /** Centres the cards instead of pinning them to the left edge. */
    public bool $isCentered;

    /**
     * Custom properties holding the tint of each card, empty unless tinted.
     *
     * @var array<int, string>
     */
    public array $tints;

    /**
     * @param  string|null  $size  In the grid layout, 'narrow' fits four cards to a line and 'wide' two; omit for three.
     */
    public function __construct(
        ?string $label = null,
        string $layout = 'carousel',
        public ?string $size = null,
        bool|string $tinted = false,
        bool|string $centered = false,
    ) {
        $this->label = $label ?? __('ig-common::layouts.card_row.label');
        $this->isCarousel = $layout === 'carousel';
        $this->isTinted = filter_var($tinted, FILTER_VALIDATE_BOOLEAN);
        $this->isCentered = filter_var($centered, FILTER_VALIDATE_BOOLEAN);
        $this->tints = $this->isTinted ? $this->tints() : [];
    }

    /**
     * The wheel is rotated to an arbitrary angle and its eight evenly spaced
     * hues are handed out in steps of three or five slots instead of in
     * spectrum order. Both steps cycle through all eight slots, so
     * neighbouring cards - including the wrap from the eighth back to the
     * first - always sit about 135 degrees apart, however the palette happens
     * to fall. Every choice is derived from the label, so a given row keeps
     * the same palette across requests while different rows get different
     * colours.
     *
     * @return array<int, string>
     */
    private function tints(): array
    {
        $rotation = crc32($this->label) % 360;
        $step = crc32($this->label . 'step') % 2 === 0 ? 3 : 5;
        $firstSlot = crc32($this->label . 'slot') % 8;

        $tints = [];

        foreach (range(0, 7) as $position) {
            $slotIndex = ($firstSlot + $position * $step) % 8;
            $jitter = crc32($this->label . $position) % 13 - 6;
            $hue = ($rotation + $slotIndex * 45 + $jitter + 360) % 360;

            $index = $position + 1;

            $tints[] = "--card-tint-{$index}: hsl({$hue} 62% 90%)";
        }

        return $tints;
    }

    public function render(): View
    {
        return view('ig-common::components.card-row');
    }
}
