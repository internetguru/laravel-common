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
     * The Bootstrap theme colour the dot is drawn in, or null to leave the
     * colour to be derived from the value itself.
     */
    public function variant(): ?string;
}
