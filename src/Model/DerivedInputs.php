<?php

declare(strict_types=1);

namespace BallparkCalculator\Model;

/**
 * Intermediate calculated values derived from user inputs.
 */
class DerivedInputs
{
    /** @var string */
    public $country;
    
    /** @var int */
    public $sites;
    
    /** @var int */
    public $patients;
    
    /** @var float */
    public $startupMonths;
    
    /** @var int */
    public $activePhaseMonths;
    
    /** @var int */
    public $totalMonths;
    
    /** @var int */
    public $sitesContacted;
    
    /** @var int */
    public $sitesCdas;
    
    /** @var int */
    public $sitesQuestionnaires;
    
    /** @var int */
    public $qualificationVisitsOnsite;
    
    /** @var int */
    public $qualificationVisitsRemote;
    
    /** @var int */
    public $initiationVisitsOnsite;
    
    /** @var int */
    public $initiationVisitsRemote;
    
    /** @var int */
    public $monitoringVisitsOnsite;
    
    /** @var int */
    public $monitoringVisitsRemote;
    
    /** @var int */
    public $unblindedVisits;
    
    /** @var int */
    public $closeoutVisitsOnsite;
    
    /** @var int */
    public $closeoutVisitsRemote;
    
    /** @var int */
    public $siteMonthsActive;
    
    /** @var int */
    public $sitePayments;
    
    /** @var int */
    public $saes;
    
    /** @var int */
    public $expeditedSafetySubmissions;
    
    /** @var int */
    public $periodicSafetyNotifications;
    
    /** @var int */
    public $countires;
    
    /** @var int */
    public $annualSubmissionCycles;
    
    /** @var int */
    public $crasRequired;

    public function __construct(
        $country,
        $sites,
        $patients,
        $startupMonths,
        $activePhaseMonths,
        $totalMonths,
        $sitesContacted,
        $sitesCdas,
        $sitesQuestionnaires,
        $qualificationVisitsOnsite,
        $qualificationVisitsRemote,
        $initiationVisitsOnsite,
        $initiationVisitsRemote,
        $monitoringVisitsOnsite,
        $monitoringVisitsRemote,
        $unblindedVisits,
        $closeoutVisitsOnsite,
        $closeoutVisitsRemote,
        $siteMonthsActive,
        $sitePayments,
        $saes,
        $expeditedSafetySubmissions,
        $periodicSafetyNotifications,
        $countires,
        $annualSubmissionCycles,
        $crasRequired
    ) {
        $this->country = $country;
        $this->sites = (int)$sites;
        $this->patients = (int)$patients;
        $this->startupMonths = (float)$startupMonths;
        $this->activePhaseMonths = (int)$activePhaseMonths;
        $this->totalMonths = (int)$totalMonths;
        $this->sitesContacted = (int)$sitesContacted;
        $this->sitesCdas = (int)$sitesCdas;
        $this->sitesQuestionnaires = (int)$sitesQuestionnaires;
        $this->qualificationVisitsOnsite = (int)$qualificationVisitsOnsite;
        $this->qualificationVisitsRemote = (int)$qualificationVisitsRemote;
        $this->initiationVisitsOnsite = (int)$initiationVisitsOnsite;
        $this->initiationVisitsRemote = (int)$initiationVisitsRemote;
        $this->monitoringVisitsOnsite = (int)$monitoringVisitsOnsite;
        $this->monitoringVisitsRemote = (int)$monitoringVisitsRemote;
        $this->unblindedVisits = (int)$unblindedVisits;
        $this->closeoutVisitsOnsite = (int)$closeoutVisitsOnsite;
        $this->closeoutVisitsRemote = (int)$closeoutVisitsRemote;
        $this->siteMonthsActive = (int)$siteMonthsActive;
        $this->sitePayments = (int)$sitePayments;
        $this->saes = (int)$saes;
        $this->expeditedSafetySubmissions = (int)$expeditedSafetySubmissions;
        $this->periodicSafetyNotifications = (int)$periodicSafetyNotifications;
        $this->countires = (int)$countires;
        $this->annualSubmissionCycles = (int)$annualSubmissionCycles;
        $this->crasRequired = (int)$crasRequired;
    }

    /** @return int */
    public function getTotalQualificationVisits()
    {
        return $this->qualificationVisitsOnsite + $this->qualificationVisitsRemote;
    }

    /** @return int */
    public function getTotalInitiationVisits()
    {
        return $this->initiationVisitsOnsite + $this->initiationVisitsRemote;
    }

    /** @return int */
    public function getTotalCloseoutVisits()
    {
        return $this->closeoutVisitsOnsite + $this->closeoutVisitsRemote;
    }

    /** @return int */
    public function getTotalOnsiteVisits()
    {
        return $this->qualificationVisitsOnsite
            + $this->initiationVisitsOnsite
            + $this->monitoringVisitsOnsite
            + $this->unblindedVisits
            + $this->closeoutVisitsOnsite;
    }
}
