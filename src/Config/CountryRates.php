<?php

declare(strict_types=1);

namespace BallparkCalculator\Config;

class CountryRates
{
    /** @var array */
    private $config;
    
    /**
     * @param string $configPath
     */
    public function __construct($configPath)
    {
        $this->config = require $configPath;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function global($key)
    {
        if (!isset($this->config['global'][$key])) {
            throw new \InvalidArgumentException("Unknown global key: {$key}");
        }
        return $this->config['global'][$key];
    }

    /**
     * @param string $role
     * @param string $country
     * @return float
     */
    public function hourlyRate($role, $country)
    {
        if (!isset($this->config['hourly_rates'][$role][$country])) {
            throw new \InvalidArgumentException("Unknown rate: {$role}/{$country}");
        }
        return (float)$this->config['hourly_rates'][$role][$country];
    }

    /**
     * @param string $visitType
     * @param string $country
     * @return int
     */
    public function visitHours($visitType, $country)
    {
        if (!isset($this->config['visit_hours'][$country][$visitType])) {
            throw new \InvalidArgumentException("Unknown visit hours: {$visitType}/{$country}");
        }
        return (int)$this->config['visit_hours'][$country][$visitType];
    }

    /**
     * @param string $costType
     * @param string $country
     * @return float
     */
    public function fixedCost($costType, $country)
    {
        return isset($this->config['fixed_costs'][$country][$costType])
            ? (float)$this->config['fixed_costs'][$country][$costType]
            : 0.0;
    }

    /**
     * @param string $country
     * @return float
     */
    public function startupMonths($country)
    {
        if (!isset($this->config['startup_months'][$country])) {
            throw new \InvalidArgumentException("Unknown startup months for: {$country}");
        }
        return (float)$this->config['startup_months'][$country];
    }

    /**
     * @param string $costType
     * @param string $country
     * @return float
     */
    public function monthlyCost($costType, $country)
    {
        return isset($this->config['monthly_costs'][$costType][$country])
            ? (float)$this->config['monthly_costs'][$costType][$country]
            : 0.0;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function regulatoryHours($key)
    {
        if (!isset($this->config['regulatory_hours'][$key])) {
            throw new \InvalidArgumentException("Unknown regulatory hours: {$key}");
        }
        return $this->config['regulatory_hours'][$key];
    }

    /**
     * @param string $country
     * @return bool
     */
    public function isEuCountry($country)
    {
        return in_array($country, $this->config['regions']['eu'], true);
    }

    /**
     * @param string $country
     * @return bool
     */
    public function isNonEuCountry($country)
    {
        return in_array($country, $this->config['regions']['non_eu'], true);
    }

    /**
     * @param string $country
     * @return bool
     */
    public function isUs($country)
    {
        return $country === 'US';
    }

    /**
     * @return float
     */
    public function getNonEuDiscount()
    {
        return (float)$this->config['non_eu_discount'];
    }

    /**
     * @return array
     */
    public function getAllCountries()
    {
        return $this->config['regions']['all'];
    }

    /**
     * @param string $visitType
     * @param string $country
     * @return float
     */
    public function visitCost($visitType, $country)
    {
        $hours = $this->visitHours($visitType, $country);
        $rate = $this->hourlyRate('cra', $country);
        return $hours * $rate;
    }

    /**
     * @param string $country
     * @return float
     */
    public function siteManagementMonthlyCost($country)
    {
        return (5 * $this->hourlyRate('cra', $country))
             + (6 * $this->hourlyRate('admin', $country));
    }

    /**
     * @param string $country
     * @return float
     */
    public function contractTemplateCost($country)
    {
        return $this->hourlyRate('contract', $country) * $this->global('contract_template_hours');
    }

    /**
     * @param string $country
     * @return float
     */
    public function contractNegotiationCost($country)
    {
        return $this->hourlyRate('contract', $country) * $this->global('contract_negotiation_hours');
    }

    /**
     * @param string $country
     * @return float
     */
    public function majorRaSubmissionCost($country)
    {
        $hours = $this->regulatoryHours('major_ra_submission');
        $raRate = $this->hourlyRate('ra', $country);
        $craRate = $this->hourlyRate('cra', $country);
        
        return ($hours['ra_hours'] * $raRate)
             + ($hours['cra_hours'] * $craRate)
             + ($hours['ra_followup_hours'] * $raRate);
    }
}
