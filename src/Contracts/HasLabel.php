<?php

namespace InternetGuru\LaravelCommon\Contracts;

/**
 * A value that can be shown as a label: an enum case, or anything else with a
 * fixed set of values. Implement it together with the RendersLabel trait to get
 * the markup for free.
 */
interface HasLabel
{
    /** The text of the label, translated. */
    public function label(): string;

    /**
     * The Bootstrap theme color the dot is drawn in, or null to leave the
     * color to the palette the value shares with the rest of its set.
     */
    public function variant(): ?string;
}
