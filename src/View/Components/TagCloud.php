<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class TagCloud extends Component
{
    /**
     * The tags to show, each with the custom properties carrying its colour and fallback size.
     *
     * @var array<int, array{label: string, style: string}>
     */
    public array $items;

    /** Draws the tags as plain coloured words of varying size, not as chips. */
    public bool $isTypography;

    /**
     * @param  array<int, string>|string  $tags  A list of tags, or a comma separated string of them.
     */
    public function __construct(array|string $tags = [], bool|string $typography = false)
    {
        $this->isTypography = filter_var($typography, FILTER_VALIDATE_BOOLEAN);
        $this->items = $this->items(is_string($tags) ? explode(',', $tags) : $tags);
    }

    /**
     * @param  array<int, string>  $tags
     * @return array<int, array{label: string, style: string}>
     */
    private function items(array $tags): array
    {
        $labels = array_values(array_filter(array_map(trim(...), $tags)));

        return array_map(fn (string $label, int $index): array => [
            'label' => $label,
            'style' => $this->style($label, $index),
        ], $labels, array_keys($labels));
    }

    /**
     * The custom properties of a single tag. Hues are spread by the golden
     * angle, so neighbouring tags never land on a similar colour however many
     * there are. In the typographic cloud the sizes are measured in the
     * browser, so the one set here only stands in until the script has run.
     */
    private function style(string $label, int $index): string
    {
        $hue = (int) round(fmod($index * 137.5, 360));

        if (! $this->isTypography) {
            return "--tag-hue: {$hue}";
        }

        return "--tag-hue: {$hue}; --tag-size: {$this->fallbackSize($label)}rem";
    }

    /**
     * The size a tag falls back to before the layout script runs: the size
     * stands in for weight, so short terms are set large and long ones smaller,
     * which is what keeps the lines of a word cloud roughly even instead of
     * leaving one term running away with a line.
     */
    private function fallbackSize(string $label): float
    {
        return round(max(1.1, min(2.6, 26 / max(8, mb_strlen($label)))), 2);
    }

    public function render(): View
    {
        return view('ig-common::components.tag-cloud');
    }
}
