<?php

namespace App\Services;

class EnergyConversionService
{
    // Make the properties public so they can be accessed directly
    public $electricityRate = 0.40; // 40 cents per kWh including tax
    public $gasRate = 1.45;         // €1.45 per m³ including tax
    
    // Fixed delivery costs per month
    private $electricityFixedCost = 8.50;   // €8.50 monthly fixed cost for electricity
    private $gasFixedCost = 7.75;           // €7.75 monthly fixed cost for gas
    
    /**
     * Convert electricity usage from kWh to Euro
     */
    public function kwhToEuro(float $kwh): float
    {
        // For simplicity, we're not including the fixed costs in the per-kWh calculation
        return round($kwh * $this->electricityRate, 2);
    }
    
    /**
     * Convert electricity cost from Euro to kWh
     */
    public function euroToKwh(float $euro): float
    {
        // For simplicity, we're not including the fixed costs in this calculation
        return round($euro / $this->electricityRate, 2);
    }
    
    /**
     * Convert gas usage from m³ to Euro
     */
    public function m3ToEuro(float $m3): float
    {
        // For simplicity, we're not including the fixed costs in the per-m³ calculation
        return round($m3 * $this->gasRate, 2);
    }
    
    /**
     * Convert gas cost from Euro to m³
     */
    public function euroToM3(float $euro): float
    {
        // For simplicity, we're not including the fixed costs in this calculation
        return round($euro / $this->gasRate, 2);
    }
    
    /**
     * Get monthly fixed costs for electricity
     */
    public function getElectricityFixedCost(): float
    {
        return $this->electricityFixedCost;
    }
    
    /**
     * Get monthly fixed costs for gas
     */
    public function getGasFixedCost(): float
    {
        return $this->gasFixedCost;
    }
    
    /**
     * Get electricity price per kWh
     */
    public function getElectricityPricePerKwh(): float
    {
        return $this->electricityRate;
    }
    
    /**
     * Get gas price per m³
     */
    public function getGasPricePerM3(): float
    {
        return $this->gasRate;
    }
    
    /**
     * Calculate full electricity costs including fixed delivery costs
     */
    public function calculateTotalElectricityCost(float $kwh, int $periodDays = 30): float
    {
        $variableCost = $this->kwhToEuro($kwh);
        // Prorate the fixed costs based on the period
        $fixedCost = $this->electricityFixedCost * ($periodDays / 30);
        
        return round($variableCost + $fixedCost, 2);
    }
    
    /**
     * Calculate full gas costs including fixed delivery costs
     */
    public function calculateTotalGasCost(float $m3, int $periodDays = 30): float
    {
        $variableCost = $this->m3ToEuro($m3);
        // Prorate the fixed costs based on the period
        $fixedCost = $this->gasFixedCost * ($periodDays / 30);
        
        return round($variableCost + $fixedCost, 2);
    }
}