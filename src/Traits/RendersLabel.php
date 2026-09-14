<?php

namespace InternetGuru\LaravelCommon\Traits;

use InternetGuru\LaravelCommon\View\Components\Label;

/**
 * Renders a HasLabel value as a label. Meant for enums, whose cases cannot be
 * passed to a Blade component from a place that echoes plain HTML - a table
 * column formatter, most of all.
 */
trait RendersLabel
{
    public function toLabelHtml(): string
    {
        return Label::html(
            $this->label(),
            variant: $this->variant(),
            seed: $this->labelSeed(),
            icon: $this->labelIcon(),
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
     * What the dot's colour is derived from when the value names no variant of
     * its own. A backed enum keeps its colour across renames of the case.
     */
    protected function labelSeed(): ?string
    {
        return property_exists($this, 'value') ? (string) $this->value : null;
    }
}
