<?php

declare(strict_types=1);

namespace KateMorley\Grid\Data;

/** Defines the sign convention used for every US cross-border flow figure. */
final class UsNetFlow
{
    /**
     * Converts EIA interchange to the public site's convention.
     *
     * EIA reports positive interchange as an outflow and negative interchange
     * as an inflow. The site shows the inverse: positive means net imports and
     * negative means net exports.
     */
    public static function importsPositive(float $eiaInterchange): float
    {
        return -$eiaInterchange;
    }
}
