<?php

echo "START\n";

use KateMorley\Grid\Environment;
use KateMorley\Grid\Data\UsLatestGeneration;
use KateMorley\Grid\Data\UsStateMap;
use KateMorley\Grid\State\Datum;
use KateMorley\Grid\State\Generation;
use KateMorley\Grid\State\Types;
use KateMorley\Grid\State\UsTypes;
use KateMorley\Grid\State\Demand;

spl_autoload_register(function ($class) {
  require_once(
    __DIR__
    . '/classes/'
    . strtr(substr($class, 16), '\\', '/')
    . '.php'
  );
});

Environment::load(__DIR__ . '/.env');

echo "ENV LOADED\n";

$datum = UsLatestGeneration::fetch();
$map = UsStateMap::build($datum);
$stateDatum = new Datum($map);
$usTypes = new UsTypes($map);

echo "STATE DATUM CREATED\n";

echo "GENERATION BY SOURCE\n";
var_dump([
  'coal'    => $stateDatum->generation->get(Generation::COAL),
  'gas'     => $stateDatum->generation->get(Generation::GAS),
  'solar'   => $stateDatum->generation->get(Generation::SOLAR),
  'wind'    => $stateDatum->generation->get(Generation::WIND),
  'hydro'   => $stateDatum->generation->get(Generation::HYDROELECTRIC),
  'nuclear' => $stateDatum->generation->get(Generation::NUCLEAR),
  'biomass' => $stateDatum->generation->get(Generation::BIOMASS),
]);

echo "UK TYPES\n";
var_dump([
  'fossils'    => $stateDatum->types->get(Types::FOSSILS),
  'renewables' => $stateDatum->types->get(Types::RENEWABLES),
  'others'     => $stateDatum->types->get(Types::OTHERS),
]);

echo "US TYPES\n";
var_dump([
  'fossils'    => $usTypes->get(UsTypes::FOSSILS),
  'renewables' => $usTypes->get(UsTypes::RENEWABLES),
  'others'     => $usTypes->get(UsTypes::OTHERS),
]);

echo "DEMAND SUMMARY\n";
var_dump([
  'generation_total' => $stateDatum->demand->getGeneration(),
  'demand_total'     => $stateDatum->demand->get(Demand::DEMAND),
  'transfers'        => $stateDatum->demand->get(Demand::TRANSFERS),
]);

echo "END\n";