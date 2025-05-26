<?php

namespace App\Services;

class EnergyConversionService
{
    //Gas en electra prijzen zijn hier hard-coded maar moeten natuurlijk later realtime prijzen laten zien. 
    public float $gasRate;    // €/m³ 
    public float $electricityRate;  // €/kWh
    public function __construct()
    {
        $this->gasRate = 1.45;
        $this->electricityRate = 0.35;

    }
    public function m3ToEuro($m3)
    {
        return $m3 * $this->gasRate;
    }

    public function euroToM3($euro)
    {
        return $euro / $this->gasRate;
    }

    public function kwhToEuro($kwh)
    {
        return $kwh * $this->electricityRate;
    }

    public function euroToKwh($euro)
    {
        return $euro / $this->electricityRate;
    }
}
