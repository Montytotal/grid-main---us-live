<?php

declare(strict_types=1);

namespace KateMorley\Grid\State;

class UsTypes extends Types
{
    public const FOSSILS = 'fossils';
    public const RENEWABLES = 'renewables';
    public const OTHERS = 'others';

    public const KEYS = [
        self::FOSSILS,
        self::RENEWABLES,
        self::OTHERS,
    ];

    public function __construct(array $map)
    {
        parent::__construct([
            self::FOSSILS => (float)($map['coal'] ?? 0)
                + (float)($map['ccgt'] ?? 0)
                + (float)($map['ocgt'] ?? 0)
                + (float)($map['oil'] ?? 0),

            self::RENEWABLES => (float)($map['embedded_solar'] ?? 0)
                + (float)($map['embedded_wind'] ?? 0)
                + (float)($map['wind'] ?? 0)
                + (float)($map['hydro'] ?? 0),

            self::OTHERS => (float)($map['nuclear'] ?? 0)
                + (float)($map['biomass'] ?? 0)
                + (float)($map['other'] ?? 0)
                + (float)($map['battery'] ?? 0),
        ]);
    }
}