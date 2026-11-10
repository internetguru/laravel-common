<?php

namespace InternetGuru\LaravelCommon\Traits;

use InternetGuru\LaravelCommon\Support\Palette;
use InternetGuru\LaravelCommon\View\Components\Label;
use UnitEnum;

/**
 * Renders a HasLabel value as a label. Meant for enums, whose cases cannot be
 * passed to a Blade component from a place that echoes plain HTML - a table
 * column formatter, most of all.
 */
trait RendersLabel
{
    public function toLabelHtml(bool $slim = false): string
    {
        $variant = $this->variant();
        $isCase = $variant === null && $this instanceof UnitEnum;

        return Label::html(
            $this->label(),
            variant: $variant,
            color: $variant === null && ! $isCase ? $this->labelDotColor() : null,
            group: $isCase ? static::class : null,
            key: $isCase ? $this->name : null,
            icon: $this->labelIcon(),
            slim: $slim,
        );
    }

    /**
     * The color of every case that names no variant, by the name of the case:
     * the label group of the enum, read by LabelGroups. Cases share one palette,
     * each in a color of its own, as far apart as the number of them allows;
     * cases with a color of their own take no slot of it.
     *
     * @return array<string, string>
     */
    public static function labelColors(): array
    {
        $own = [];
        $keys = [];
        $forced = [];

        foreach (static::cases() as $case) {
            if ($case->variant() !== null) {
                continue;
            }

            $choice = $case->labelColor();

            if (is_string($choice)) {
                $own[$case->name] = $choice;

                continue;
            }

            $keys[] = $case->name;

            if (is_int($choice)) {
                $forced[$case->name] = $choice;
            }
        }

        return $own + Palette::colors($keys, $forced, static::labelPaletteSeed());
    }

    /**
     * Font Awesome classes of an icon standing in for the dot, for a value
     * already recognized by a mark of its own. None by default.
     */
    protected function labelIcon(): ?string
    {
        return null;
    }

    /**
     * A color forced on the value when it names no variant: a palette slot, or
     * a color of its own. None by default, which leaves an enum case to the
     * palette it shares with the other cases.
     */
    protected function labelColor(): string|int|null
    {
        return null;
    }

    /** What turns the palette of the enum's cases, so sets differ in color. */
    protected static function labelPaletteSeed(): int|string
    {
        return static::class;
    }

    /**
     * The color of the dot of a value that is not an enum and names no variant:
     * it has no set to share, so it is given a color or a slot of its own, or
     * none at all.
     */
    protected function labelDotColor(): ?string
    {
        $chosen = $this->labelColor();

        return is_int($chosen) ? Palette::color($chosen, static::labelPaletteSeed()) : $chosen;
    }
}
