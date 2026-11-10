<?php

namespace InternetGuru\LaravelCommon\View\Components;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;
use Illuminate\View\View;
use InternetGuru\LaravelCommon\Exceptions\LabelException;
use InternetGuru\LaravelCommon\Support\LabelGroups;
use InternetGuru\LaravelCommon\Support\Palette;
use UnitEnum;

/**
 * A chip naming one value out of a set - a status, a role, a payment type, a
 * branch. It is read as plain text on white, with the value itself carried by a
 * small colored dot rather than by a filled background, so a table of them
 * stays quiet however many kinds it holds.
 *
 * The color is given in one of two ways. By hand, as a variant or a color.
 * Or from the palette, as a key of a group registered in LabelGroups or of an
 * enum: a value has one color everywhere, and no two values of a group share
 * one. A label given neither, or a value that is not valid, is an error in
 * debug mode and a gray dot with a logged warning otherwise, as a missing
 * translation is.
 */
class Label extends Component
{
    /**
     * Color notations an explicit color may be given in. Deliberately narrow:
     * the value ends up inside a style attribute.
     */
    protected const COLOR_PATTERN = '/^(#[0-9a-f]{3,8}|(rgb|hsl|oklch)a?\([\d\s.,%\/]+\)|var\(--[\w-]+\))$/i';

    /** The color of the dot, ready to be set as a custom property. */
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
     * @param  string|null  $variant  Theme color naming the kind of value, e.g. `success`, drawn in its palette tone.
     * @param  string|null  $color  An explicit color; it takes precedence over the variant.
     * @param  string|null  $group  A group registered in LabelGroups, or an enum class using RendersLabel.
     * @param  string|int|UnitEnum|null  $key  The value within its group, which picks its palette color; an enum case stands for its name.
     * @param  string|null  $icon  Font Awesome classes of an icon shown in place of the dot.
     * @param  bool  $slim  Cut the padding down, for a label read inside a line of text.
     */
    public function __construct(
        public ?string $text = null,
        ?string $variant = null,
        ?string $color = null,
        ?string $group = null,
        string|int|UnitEnum|null $key = null,
        public ?string $icon = null,
        public bool $slim = false,
    ) {
        $this->dotColor = $this->dotColor($variant, $color, $group, $key instanceof UnitEnum ? $key->name : $key);
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
        ?string $color = null,
        ?string $group = null,
        string|int|UnitEnum|null $key = null,
        ?string $icon = null,
        bool $slim = false,
    ): string {
        $label = new self($text, $variant, $color, $group, $key, $icon, $slim);

        return Blade::renderComponent($label->withAttributes([]));
    }

    /**
     * A color wins, then the tone of the variant, then the color of the key in
     * its group. A value that is given but not valid is not passed over for
     * the next one: a typo would otherwise show as some other color without
     * anyone noticing. A color is checked before it is used, as it is written
     * into a style attribute.
     */
    protected function dotColor(?string $variant, ?string $color, ?string $group, string|int|null $key): string
    {
        if ($color !== null) {
            return preg_match(self::COLOR_PATTERN, $color)
                ? $color
                : $this->fail("Label '{$this->text}' has an invalid color: {$color}");
        }

        if ($variant !== null) {
            return Palette::variant($variant)
                ?? $this->fail("Label '{$this->text}' has an unknown variant: {$variant}");
        }

        if ($group === null && $key === null) {
            return $this->fail("Label '{$this->text}' has no variant, color or group");
        }

        if ($group === null || $key === null) {
            return $this->fail("Label '{$this->text}' needs both a group and a key");
        }

        $colors = LabelGroups::colors($group);

        if ($colors === null) {
            return $this->fail("Label '{$this->text}' has an unknown group: {$group}");
        }

        return $colors[$key] ?? $this->fail("Label '{$this->text}' has a key not in group {$group}: {$key}");
    }

    /**
     * An error in debug mode; otherwise a logged warning and the neutral tone,
     * so a page in production still renders.
     */
    protected function fail(string $message): string
    {
        if (App::hasDebugModeEnabled()) {
            throw new LabelException($message);
        }

        Log::warning($message);

        return Palette::variant('secondary');
    }

    public function render(): View
    {
        return view('ig-common::components.label');
    }
}
