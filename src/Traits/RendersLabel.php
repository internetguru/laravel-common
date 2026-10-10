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
        return Label::html(
            $this->label(),
            variant: $this->variant(),
            color: $this->variant() === null ? $this->labelDotColor() : null,
            seed: $this->labelSeed(),
            icon: $this->labelIcon(),
            slim: $slim,
        );
    }

    /**
     * Font Awesome classes of an icon standing in for the dot, for a value
     * already recognised by a mark of its own. None by default.
     */
    protected function labelIcon(): ?string
    {
        return null;
    }

    /**
     * A colour forced on the value when it names no variant: a palette slot, or
     * a colour of its own. None by default, which leaves an enum case to the
     * palette it shares with the other cases.
     */
    protected function labelColor(): string|int|null
    {
        return null;
    }

    /** What turns the palette of the enum's cases, so sets differ in colour. */
    protected static function labelPaletteSeed(): int|string
    {
        return static::class;
    }

    /**
     * The colour of the dot when the value names no variant. The cases of an
     * enum share one palette, each in a colour of its own, as far apart as
     * the number of them allows; cases with a variant or a colour of their own
     * take no slot of it.
     */
    protected function labelDotColor(): string|int|null
    {
        $chosen = $this->labelColor();

        if (! $this instanceof UnitEnum || is_string($chosen)) {
            return $chosen;
        }

        $keys = [];
        $forced = [];

        foreach ($this::cases() as $case) {
            $choice = $case->labelColor();

            if ($case->variant() !== null || is_string($choice)) {
                continue;
            }

            $keys[] = $case->name;

            if (is_int($choice)) {
                $forced[$case->name] = $choice;
            }
        }

        return Palette::colors($keys, $forced, static::labelPaletteSeed())[$this->name];
    }

    /**
     * What the dot's colour is derived from when the value names no variant and
     * no colour of its own. A backed enum keeps its colour across renames of
     * the case.
     */
    protected function labelSeed(): ?string
    {
        return property_exists($this, 'value') ? (string) $this->value : null;
    }
}
