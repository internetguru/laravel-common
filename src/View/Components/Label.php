<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\Component;
use Illuminate\View\View;
use InternetGuru\LaravelCommon\Support\Palette;

/**
 * A chip naming one value out of a set - a status, a role, a payment type, a
 * branch. It is read as plain text on white, with the value itself carried by a
 * small coloured dot rather than by a filled background, so a table of them
 * stays quiet however many kinds it holds.
 */
class Label extends Component
{
    /**
     * Colour notations an explicit colour may be given in. Deliberately narrow:
     * the value ends up inside a style attribute.
     */
    protected const COLOR_PATTERN = '/^(#[0-9a-f]{3,8}|(rgb|hsl|oklch)a?\([\d\s.,%\/]+\)|var\(--[\w-]+\))$/i';

    /**
     * How many palette slots a value is hashed into when it is given no colour
     * of its own: the first ring, whose colours are the furthest apart.
     */
    protected const HASHED_SLOTS = 8;

    /** The colour of the dot, ready to be set as a custom property. */
    public string $dotColor;

    /**
     * The icon, already marked up. Built here rather than in the template: a
     * label is rendered inside Livewire components, whose compiler writes its
     * own markers around every Blade conditional, and those would land in the
     * middle of the chip.
     */
    public string $iconMarkup;

    /**
     * @param  string|null  $text  The label itself; the slot is used instead when it is given.
     * @param  string|null  $variant  Theme colour naming the kind of value, e.g. `success`, drawn in its palette tone.
     * @param  string|int|null  $color  A palette slot, or an explicit colour; either takes precedence over the variant.
     * @param  string|null  $seed  What the colour is derived from when neither a variant nor a colour is given; the text by default.
     * @param  string|null  $icon  Font Awesome classes of an icon shown in place of the dot.
     * @param  bool  $slim  Cut the padding down, for a label read inside a line of text.
     */
    public function __construct(
        public ?string $text = null,
        ?string $variant = null,
        string|int|null $color = null,
        ?string $seed = null,
        public ?string $icon = null,
        public bool $slim = false,
    ) {
        $this->dotColor = $this->dotColor($variant, $color, $seed ?? $text ?? '');
        $this->iconMarkup = $icon === null ? '' : sprintf('<i class="%s" aria-hidden="true"></i>', e($icon));
    }

    /**
     * The label as markup, for the places that build their HTML in PHP rather
     * than in a template - a model browser column formatter, above all, which
     * is given the name of a function and echoes what it returns.
     */
    public static function html(
        ?string $text = null,
        ?string $variant = null,
        string|int|null $color = null,
        ?string $seed = null,
        ?string $icon = null,
        bool $slim = false,
    ): string {
        $label = new self($text, $variant, $color, $seed, $icon, $slim);

        return Blade::renderComponent($label->withAttributes([]));
    }

    /**
     * A palette slot or an explicit colour wins, then the tone of the variant,
     * and failing both the value is given a colour of its own. A colour or
     * variant that is not one of the notations above is dropped rather than
     * passed on: it is written into a style attribute. A slot comes from Blade
     * as a string of digits, so one is taken for a slot too.
     */
    protected function dotColor(?string $variant, string|int|null $color, string $seed): string
    {
        if (is_int($color) ? $color >= 0 : ctype_digit((string) $color)) {
            return Palette::color((int) $color);
        }

        if ($color !== null && preg_match(self::COLOR_PATTERN, $color)) {
            return $color;
        }

        return Palette::variant((string) $variant) ?? Palette::color($this->hashedSlot($seed));
    }

    /**
     * The slot a value is drawn in, taken from a hash of the value so that it
     * keeps its colour wherever it is shown, without anything having to be
     * stored against it. Two values may share a slot; a set whose values must
     * all differ gets its slots from the palette instead.
     */
    protected function hashedSlot(string $seed): int
    {
        return crc32($seed) % self::HASHED_SLOTS;
    }

    public function render(): View
    {
        return view('ig-common::components.label');
    }
}
