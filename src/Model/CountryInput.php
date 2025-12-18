<?php

declare(strict_types=1);

namespace BallparkCalculator\Model;

class CountryInput
{
    /** @var string */
    public $country;
    
    /** @var int */
    public $sites;
    
    /** @var int */
    public $patients;
    
    /** @var int */
    public $monitoringVisitsOnsite;
    
    /** @var int */
    public $monitoringVisitsRemote;
    
    /** @var int */
    public $unblindedVisits;
    
    /** @var int|null */
    public $countriesInRegion;

    /**
     * @param string $country
     * @param int $sites
     * @param int $patients
     * @param int $monitoringVisitsOnsite
     * @param int $monitoringVisitsRemote
     * @param int $unblindedVisits
     * @param int|null $countriesInRegion
     */
    public function __construct(
        $country,
        $sites = 0,
        $patients = 0,
        $monitoringVisitsOnsite = 0,
        $monitoringVisitsRemote = 0,
        $unblindedVisits = 0,
        $countriesInRegion = null
    ) {
        $this->country = $country;
        $this->sites = (int)$sites;
        $this->patients = (int)$patients;
        $this->monitoringVisitsOnsite = (int)$monitoringVisitsOnsite;
        $this->monitoringVisitsRemote = (int)$monitoringVisitsRemote;
        $this->unblindedVisits = (int)$unblindedVisits;
        $this->countriesInRegion = $countriesInRegion !== null ? (int)$countriesInRegion : null;
    }

    /**
     * Get the number of EC/IRBs (countries) in this region.
     * @return int
     */
    public function getCounties()
    {
        if ($this->countriesInRegion !== null) {
            return $this->countriesInRegion;
        }
        
        switch ($this->country) {
            case 'US':
            case 'Turkiye':
                return $this->sites > 0 ? 1 : 0;
            case 'Non_EU':
            case 'Georgia':
            case 'Ukraine':
                return $this->sites;
            default:
                return $this->sites > 0 ? 1 : 0;
        }
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->sites > 0;
    }

    /**
     * @return bool
     */
    public function hasUnblindedVisits()
    {
        return $this->unblindedVisits > 0;
    }

    /**
     * @param string $country
     * @param array $data
     * @return self
     */
    public static function fromArray($country, array $data)
    {
        return new self(
            $country,
            isset($data['sites']) ? (int)$data['sites'] : 0,
            isset($data['patients']) ? (int)$data['patients'] : 0,
            isset($data['monitoring_onsite']) ? (int)$data['monitoring_onsite'] : 0,
            isset($data['monitoring_remote']) ? (int)$data['monitoring_remote'] : 0,
            isset($data['unblinded_visits']) ? (int)$data['unblinded_visits'] : 0,
            isset($data['countries_in_region']) ? (int)$data['countries_in_region'] : null
        );
    }
}
