<?php

declare(strict_types=1);

namespace BallparkCalculator\Calculator;

use BallparkCalculator\Config\CountryRates;
use BallparkCalculator\Model\CountryInput;
use BallparkCalculator\Model\DerivedInputs;
use BallparkCalculator\Model\ProjectInput;
use BallparkCalculator\Model\VisitType;

final class DerivedInputsCalculator
{
    public function __construct(
        private readonly CountryRates $config,
    ) {}

    public function calculate(ProjectInput $project, CountryInput $country): DerivedInputs
    {
        if (!$country->isActive()) {
            return $this->createEmpty($country->country);
        }

        $startupMonths = $this->config->startupMonths($country->country);
        $activePhaseMonths = $project->getActivePhaseDuration();
        
        return new DerivedInputs(
            country: $country->country,
            
            // Original counts
            sites: $country->sites,
            patients: $country->patients,
            
            // Time periods
            startupMonths: $startupMonths,
            activePhaseMonths: $activePhaseMonths,
            totalMonths: (int) ceil($startupMonths) + $activePhaseMonths,
            
            // Site selection (multipliers from config)
            sitesContacted: (int) round($this->config->global('site_multiplier_contacted') * $country->sites),
            sitesCdas: (int) round($this->config->global('site_multiplier_cdas') * $country->sites),
            sitesQuestionnaires: (int) round($this->config->global('site_multiplier_questionnaires') * $country->sites),
            
            // Visit counts
            qualificationVisitsOnsite: $this->calcQualificationOnsite($project, $country),
            qualificationVisitsRemote: $this->calcQualificationRemote($project, $country),
            initiationVisitsOnsite: $this->calcInitiationOnsite($project, $country),
            initiationVisitsRemote: $this->calcInitiationRemote($project, $country),
            monitoringVisitsOnsite: $country->monitoringVisitsOnsite * $country->sites,
            monitoringVisitsRemote: $country->monitoringVisitsRemote * $country->sites,
            unblindedVisits: $country->unblindedVisits * $country->sites,
            closeoutVisitsOnsite: $this->calcCloseoutOnsite($project, $country),
            closeoutVisitsRemote: $this->calcCloseoutRemote($project, $country),
            
            // Site management
            siteMonthsActive: $activePhaseMonths * $country->sites,
            sitePayments: (int) round($activePhaseMonths / 3 * $country->sites),
            
            // Safety
            saes: (int) round($project->saeRate * $country->patients),
            expeditedSafetySubmissions: $this->calcExpeditedSafetySubmissions($project, $activePhaseMonths),
            periodicSafetyNotifications: (int) round($activePhaseMonths / 12),
            
            // Regulatory
            countires: $country->getCounties(),
            annualSubmissionCycles: (int) round($activePhaseMonths / 12),

            // Team
            crasRequired: $this->calcCrasRequired($project, $country),
        );
    }

    private function calcQualificationOnsite(ProjectInput $project, CountryInput $country): int
    {
        if ($project->qualificationVisitType !== VisitType::ON_SITE) {
            return 0;
        }
        return (int) round($this->config->global('qualification_onsite_pct') * $country->sites);
    }

    private function calcQualificationRemote(ProjectInput $project, CountryInput $country): int
    {
        if ($project->qualificationVisitType !== VisitType::REMOTE) {
            return 0;
        }
        return (int) round($this->config->global('qualification_remote_pct') * $country->sites);
    }

    private function calcInitiationOnsite(ProjectInput $project, CountryInput $country): int
    {
        if ($project->initiationVisitType !== VisitType::ON_SITE) {
            return 0;
        }
        return (int) round($this->config->global('initiation_onsite_pct') * $country->sites);
    }

    private function calcInitiationRemote(ProjectInput $project, CountryInput $country): int
    {
        if ($project->initiationVisitType !== VisitType::REMOTE) {
            return 0;
        }
        return (int) round($this->config->global('initiation_remote_pct') * $country->sites);
    }

    private function calcCloseoutOnsite(ProjectInput $project, CountryInput $country): int
    {
        if ($project->closeoutVisitType !== VisitType::ON_SITE) {
            return 0;
        }
        return (int) round($this->config->global('closeout_onsite_pct') * $country->sites);
    }

    private function calcCloseoutRemote(ProjectInput $project, CountryInput $country): int
    {
        if ($project->closeoutVisitType !== VisitType::REMOTE) {
            return 0;
        }
        return (int) round($this->config->global('closeout_remote_pct') * $country->sites);
    }

    private function calcExpeditedSafetySubmissions(ProjectInput $project, int $activePhaseMonths): int
    {
        // Formula: ROUND(activeMonths * 30.4 / 7 / susarsWeeks, 0)
        $weeksInPhase = $activePhaseMonths * 30.4 / 7;
        return (int) round($weeksInPhase / $project->susarsWeeks);
    }

    private function calcCrasRequired(ProjectInput $project, CountryInput $country): int
    {
        if (!$country->isActive()) {
            return 0;
        }
        
        // Base: 1 CRA, +1 if unblinded visits exist
        $base = $project->hasUnblindedVisits() ? 2 : 1;
        return $base;
    }

    private function createEmpty(string $country): DerivedInputs
    {
        return new DerivedInputs(
            country: $country,
            sites: 0,
            patients: 0,
            startupMonths: 0,
            activePhaseMonths: 0,
            totalMonths: 0,
            sitesContacted: 0,
            sitesCdas: 0,
            sitesQuestionnaires: 0,
            qualificationVisitsOnsite: 0,
            qualificationVisitsRemote: 0,
            initiationVisitsOnsite: 0,
            initiationVisitsRemote: 0,
            monitoringVisitsOnsite: 0,
            monitoringVisitsRemote: 0,
            unblindedVisits: 0,
            closeoutVisitsOnsite: 0,
            closeoutVisitsRemote: 0,
            siteMonthsActive: 0,
            sitePayments: 0,
            saes: 0,
            expeditedSafetySubmissions: 0,
            periodicSafetyNotifications: 0,
            countires: 0,
            annualSubmissionCycles: 0,
            crasRequired: 0,
        );
    }
}
