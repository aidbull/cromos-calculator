<?php

declare(strict_types=1);

namespace BallparkCalculator\Model;

/**
 * Visit type constants (replacement for PHP 8.1 enum)
 */
class VisitType
{
    const ON_SITE = 'on-site';
    const REMOTE = 'remote';
    
    /**
     * @param string|null $value
     * @return string|null
     */
    public static function tryFrom($value)
    {
        if ($value === self::ON_SITE || $value === self::REMOTE) {
            return $value;
        }
        return null;
    }
}

class ProjectInput
{
    /** @var int */
    public $enrollmentMonths;
    
    /** @var int */
    public $treatmentMonths;
    
    /** @var int */
    public $followupMonths;
    
    /** @var string */
    public $qualificationVisitType;
    
    /** @var string */
    public $initiationVisitType;
    
    /** @var string */
    public $closeoutVisitType;
    
    /** @var float */
    public $saeRate;
    
    /** @var int */
    public $susarsWeeks;
    
    /** @var int */
    public $vendors;
    
    /** @var float|null */
    public $investigatorGrantPerPatient;
    
    /** @var array<string, CountryInput> */
    private $countries = array();

    /**
     * @param int $enrollmentMonths
     * @param int $treatmentMonths
     * @param int $followupMonths
     * @param string $qualificationVisitType
     * @param string $initiationVisitType
     * @param string $closeoutVisitType
     * @param float $saeRate
     * @param int $susarsWeeks
     * @param int $vendors
     * @param float|null $investigatorGrantPerPatient
     */
    public function __construct(
        $enrollmentMonths = 12,
        $treatmentMonths = 4,
        $followupMonths = 12,
        $qualificationVisitType = 'on-site',
        $initiationVisitType = 'remote',
        $closeoutVisitType = 'remote',
        $saeRate = 0.15,
        $susarsWeeks = 13,
        $vendors = 1,
        $investigatorGrantPerPatient = null
    ) {
        $this->enrollmentMonths = (int)$enrollmentMonths;
        $this->treatmentMonths = (int)$treatmentMonths;
        $this->followupMonths = (int)$followupMonths;
        $this->qualificationVisitType = $qualificationVisitType;
        $this->initiationVisitType = $initiationVisitType;
        $this->closeoutVisitType = $closeoutVisitType;
        $this->saeRate = (float)$saeRate;
        $this->susarsWeeks = (int)$susarsWeeks;
        $this->vendors = (int)$vendors;
        $this->investigatorGrantPerPatient = $investigatorGrantPerPatient;
    }

    /**
     * @param CountryInput $country
     * @return $this
     */
    public function addCountry(CountryInput $country)
    {
        $this->countries[$country->country] = $country;
        return $this;
    }

    /**
     * @param string $name
     * @return CountryInput|null
     */
    public function getCountry($name)
    {
        return isset($this->countries[$name]) ? $this->countries[$name] : null;
    }

    /**
     * @return array<string, CountryInput>
     */
    public function getActiveCountries()
    {
        return array_filter($this->countries, function (CountryInput $c) {
            return $c->isActive();
        });
    }

    /**
     * @return int
     */
    public function getTotalSites()
    {
        $total = 0;
        foreach ($this->countries as $c) {
            $total += $c->sites;
        }
        return $total;
    }

    /**
     * @return int
     */
    public function getTotalPatients()
    {
        $total = 0;
        foreach ($this->countries as $c) {
            $total += $c->patients;
        }
        return $total;
    }

    /**
     * @return int
     */
    public function getActiveCountryCount()
    {
        return count($this->getActiveCountries());
    }

    /**
     * @return bool
     */
    public function hasUnblindedVisits()
    {
        foreach ($this->countries as $country) {
            if ($country->hasUnblindedVisits()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function hasEuCountries()
    {
        return (isset($this->countries['EU_CEE']) && $this->countries['EU_CEE']->isActive())
            || (isset($this->countries['EU_West']) && $this->countries['EU_West']->isActive());
    }

    /**
     * @return int
     */
    public function getActivePhaseDuration()
    {
        return $this->enrollmentMonths + $this->treatmentMonths + $this->followupMonths + 3;
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data)
    {
        $qualType = isset($data['qualification_visit_type']) ? $data['qualification_visit_type'] : 'on-site';
        $initType = isset($data['initiation_visit_type']) ? $data['initiation_visit_type'] : 'remote';
        $closeType = isset($data['closeout_visit_type']) ? $data['closeout_visit_type'] : 'remote';
        
        $input = new self(
            isset($data['enrollment_months']) ? (int)$data['enrollment_months'] : 12,
            isset($data['treatment_months']) ? (int)$data['treatment_months'] : 4,
            isset($data['followup_months']) ? (int)$data['followup_months'] : 12,
            VisitType::tryFrom($qualType) ?: VisitType::ON_SITE,
            VisitType::tryFrom($initType) ?: VisitType::REMOTE,
            VisitType::tryFrom($closeType) ?: VisitType::REMOTE,
            isset($data['sae_rate']) ? (float)$data['sae_rate'] : 0.15,
            isset($data['susars_weeks']) ? (int)$data['susars_weeks'] : 13,
            isset($data['vendors']) ? (int)$data['vendors'] : 1,
            isset($data['investigator_grant']) ? (float)$data['investigator_grant'] : null
        );

        $countryNames = array('US', 'EU_CEE', 'EU_West', 'Non_EU', 'Georgia', 'Turkiye', 'Ukraine');
        foreach ($countryNames as $country) {
            if (isset($data['countries'][$country])) {
                $input->addCountry(CountryInput::fromArray($country, $data['countries'][$country]));
            }
        }

        return $input;
    }
}
