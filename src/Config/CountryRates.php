<?php

declare(strict_types=1);

namespace BallparkCalculator\Config;

final class CountryRates
{
    private array $config;
    
    public function __construct(string $configPath)
    {
        $this->config = require $configPath;
    }

    // ---- Global constants ----
    
    public function global(string $key): int|float
    {
        return $this->config['global'][$key] 
            ?? throw new \InvalidArgumentException("Unknown global key: {$key}");
    }

    // ---- Country-specific rates ----
    
    public function hourlyRate(string $role, string $country): float
    {
        return (float) ($this->config['hourly_rates'][$role][$country] 
            ?? throw new \InvalidArgumentException("Unknown rate: {$role}/{$country}"));
    }

    public function visitHours(string $visitType, string $country): int
    {
        return (int) ($this->config['visit_hours'][$country][$visitType] 
            ?? throw new \InvalidArgumentException("Unknown visit hours: {$visitType}/{$country}"));
    }

    public function fixedCost(string $costType, string $country): float
    {
        return (float) ($this->config['fixed_costs'][$country][$costType] ?? 0);
    }

    public function startupMonths(string $country): float
    {
        return (float) ($this->config['startup_months'][$country] 
            ?? throw new \InvalidArgumentException("Unknown startup months for: {$country}"));
    }

    public function monthlyCost(string $costType, string $country): float
    {
        return (float) ($this->config['monthly_costs'][$costType][$country] ?? 0);
    }

    public function regulatoryHours(string $key): int|array
    {
        return $this->config['regulatory_hours'][$key] 
            ?? throw new \InvalidArgumentException("Unknown regulatory hours: {$key}");
    }

    // ---- Region checks ----

    public function isEuCountry(string $country): bool
    {
        return in_array($country, $this->config['regions']['eu'], true);
    }

    public function isNonEuCountry(string $country): bool
    {
        return in_array($country, $this->config['regions']['non_eu'], true);
    }

    public function isUs(string $country): bool
    {
        return $country === 'US';
    }

    public function getNonEuDiscount(): float
    {
        return (float) $this->config['non_eu_discount'];
    }

    public function getAllCountries(): array
    {
        return $this->config['regions']['all'];
    }

    // ---- Calculated helpers ----

    public function visitCost(string $visitType, string $country): float
    {
        $hours = $this->visitHours($visitType, $country);
        $rate = $this->hourlyRate('cra', $country);
        return $hours * $rate;
    }

    public function siteManagementMonthlyCost(string $country): float
    {
        // Formula: 5*cra_rate + 6*admin_rate
        return (5 * $this->hourlyRate('cra', $country)) 
             + (6 * $this->hourlyRate('admin', $country));
    }

    public function contractTemplateCost(string $country): float
    {
        return $this->hourlyRate('contract', $country) * $this->global('contract_template_hours');
    }

    public function contractNegotiationCost(string $country): float
    {
        return $this->hourlyRate('contract', $country) * $this->global('contract_negotiation_hours');
    }

    public function majorRaSubmissionCost(string $country): float
    {
        $hours = $this->regulatoryHours('major_ra_submission');
        $raRate = $this->hourlyRate('ra', $country);
        $craRate = $this->hourlyRate('cra', $country);
        
        return ($hours['ra_hours'] * $raRate) 
             + ($hours['cra_hours'] * $craRate) 
             + ($hours['ra_followup_hours'] * $raRate);
    }
}
