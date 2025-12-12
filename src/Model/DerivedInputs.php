<?php

declare(strict_types=1);

namespace BallparkCalculator\Model;

/**
 * Intermediate calculated values derived from user inputs.
 * These feed into the cost calculations.
 */
final class DerivedInputs
{
    public function __construct(
        public readonly string $country,
        
        // Original counts
        public readonly int $sites,
        public readonly int $patients,
        
        // Time periods
        public readonly float $startupMonths,
        public readonly int $activePhaseMonths,
        public readonly int $totalMonths,
        
        // Site selection counts
        public readonly int $sitesContacted,
        public readonly int $sitesCdas,
        public readonly int $sitesQuestionnaires,
        
        // Visit counts
        public readonly int $qualificationVisitsOnsite,
        public readonly int $qualificationVisitsRemote,
        public readonly int $initiationVisitsOnsite,
        public readonly int $initiationVisitsRemote,
        public readonly int $monitoringVisitsOnsite,
        public readonly int $monitoringVisitsRemote,
        public readonly int $unblindedVisits,
        public readonly int $closeoutVisitsOnsite,
        public readonly int $closeoutVisitsRemote,
        
        // Site management
        public readonly int $siteMonthsActive,
        public readonly int $sitePayments,
        
        // Safety counts
        public readonly int $saes,
        public readonly int $expeditedSafetySubmissions,
        public readonly int $periodicSafetyNotifications,
        
        // Regulatory
        public readonly int $countires,
        public readonly int $annualSubmissionCycles,
        
        // Team
        public readonly int $crasRequired,
    ) {}

    public function getTotalQualificationVisits(): int
    {
        return $this->qualificationVisitsOnsite + $this->qualificationVisitsRemote;
    }

    public function getTotalInitiationVisits(): int
    {
        return $this->initiationVisitsOnsite + $this->initiationVisitsRemote;
    }

    public function getTotalCloseoutVisits(): int
    {
        return $this->closeoutVisitsOnsite + $this->closeoutVisitsRemote;
    }

    public function getTotalOnsiteVisits(): int
    {
        return $this->qualificationVisitsOnsite
            + $this->initiationVisitsOnsite
            + $this->monitoringVisitsOnsite
            + $this->unblindedVisits
            + $this->closeoutVisitsOnsite;
    }
}
