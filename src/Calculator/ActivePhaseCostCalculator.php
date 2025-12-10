<?php

declare(strict_types=1);

namespace BallparkCalculator\Calculator;

use BallparkCalculator\Config\CountryRates;
use BallparkCalculator\Model\CostBreakdown;
use BallparkCalculator\Model\DerivedInputs;
use BallparkCalculator\Model\ProjectInput;

final class ActivePhaseCostCalculator
{
    public function __construct(
        private readonly CountryRates $config,
    ) {}

    public function calculate(
        ProjectInput $project,
        DerivedInputs $derived,
        CostBreakdown $costs,
    ): void {
        if ($derived->activePhaseMonths === 0) {
            return;
        }

        $this->calcRegulatoryCosts($project, $derived, $costs);
        $this->calcMeetingCosts($derived, $costs);
        $this->calcProjectPlanUpdateCosts($derived, $costs);
        $this->calcClinicalOpsCosts($project, $derived, $costs);
        $this->calcMonitoringCosts($derived, $costs);
        $this->calcSitePaymentCosts($derived, $costs);
        $this->calcSafetyReportingCosts($derived, $costs);
        $this->calcPassthroughCosts($derived, $costs);
    }

    private function calcRegulatoryCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;
        $raRate = $this->config->hourlyRate('ra', $c);
        $annualCycles = $derived->annualSubmissionCycles;

        // Major RA submissions (country-specific)
        if (!$this->config->isUs($c)) {
            $costs->addActiveService(
                'major_ra_submissions',
                $this->config->majorRaSubmissionCost($c)
            );
        }

        // Minor RA submissions (per annual cycle)
        $minorRaHours = $this->config->regulatoryHours('minor_ra_submission');
        $costs->addActiveService(
            'minor_ra_submissions',
            $minorRaHours * $raRate * (1 + $annualCycles)
        );

        // Major EC/IRB submissions (per annual cycle)
        $majorEcHours = $this->config->regulatoryHours('major_ec_submission');
        $costs->addActiveService(
            'major_ec_submissions',
            $majorEcHours * $raRate * $annualCycles * $derived->ecIrbCount
        );

        // Minor EC/IRB submissions
        $minorEcHours = $this->config->regulatoryHours('minor_ec_submission');
        $costs->addActiveService(
            'minor_ec_submissions',
            $minorEcHours * $raRate * (5 + $annualCycles * 5) // Base 5 + 5 per year
        );

        // EU Legal representative services (annual)
        if ($this->config->isEuCountry($c)) {
            $costs->addActiveService(
                'eu_legal_rep',
                $this->config->global('eu_legal_rep_annual') * $derived->activePhaseMonths
            );
        }
    }

    private function calcMeetingCosts(DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;
        $pmRate = $this->config->hourlyRate('pm', $c);
        $qaRate = $this->config->hourlyRate('qa', $c);

        // Regular client calls (already in global, country gets proportional share)
        // Formula: (0.5+1+0.5) * (PM + QA) * weeks
        $weeksInPhase = (int) round($derived->activePhaseMonths * 30.4 / 7);
        // Handled at project level
    }

    private function calcProjectPlanUpdateCosts(DerivedInputs $derived, CostBreakdown $costs): void
    {
        // Annual plan updates are global costs
        // Country-level: annual team re-training

        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);
        $annualCycles = $derived->annualSubmissionCycles;

        // Annual project team re-training (30% of initial training, per year)
        $initialTrainingCost = 22 * $craRate * $derived->crasRequired;
        $costs->addActiveService(
            'team_retraining',
            0.30 * $initialTrainingCost * $annualCycles
        );
    }

    private function calcClinicalOpsCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);
        $pmRate = $this->config->hourlyRate('pm', $c);
        $adminRate = $this->config->hourlyRate('admin', $c);
        $months = $derived->activePhaseMonths;

        // TMF Maintenance (monthly)
        $costs->addActiveService(
            'tmf_maintenance',
            $this->config->monthlyCost('tmf_maintenance', $c) * $months
        );

        // CTMS Update (monthly)
        $costs->addActiveService(
            'ctms_update',
            $this->config->monthlyCost('ctms_update', $c) * $months
        );

        // Internal communication (monthly)
        $costs->addActiveService(
            'internal_communication',
            2 * $craRate * $months
        );

        // Project team management (monthly per country)
        $costs->addActiveService(
            'team_management',
            2 * $pmRate * $derived->ecIrbCount * $months
        );

        // Review of visit reports
        $totalVisits = $derived->monitoringVisitsOnsite 
            + $derived->monitoringVisitsRemote 
            + $derived->unblindedVisits 
            + $derived->closeoutVisitsOnsite 
            + $derived->closeoutVisitsRemote;
        $costs->addActiveService(
            'visit_report_review',
            $pmRate * $totalVisits
        );

        // Resolution of country-level issues (monthly)
        $costs->addActiveService(
            'country_issues_resolution',
            4 * $pmRate * $derived->ecIrbCount * $months
        );

        // Pass-through costs management (monthly)
        $costs->addActiveService(
            'passthrough_management',
            4 * $adminRate * $months
        );
    }

    private function calcMonitoringCosts(DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;

        // Site management (monthly per site)
        $costs->addActiveService(
            'site_management',
            $this->config->siteManagementMonthlyCost($c) * $derived->siteMonthsActive
        );

        // On-site monitoring visits
        if ($derived->monitoringVisitsOnsite > 0) {
            $costs->addActiveService(
                'monitoring_visits_onsite',
                $this->config->visitCost('monitoring_onsite', $c) * $derived->monitoringVisitsOnsite
            );
        }

        // Remote monitoring visits
        if ($derived->monitoringVisitsRemote > 0) {
            $costs->addActiveService(
                'monitoring_visits_remote',
                $this->config->visitCost('monitoring_remote', $c) * $derived->monitoringVisitsRemote
            );
        }

        // Unblinded visits
        if ($derived->unblindedVisits > 0) {
            $costs->addActiveService(
                'unblinded_visits',
                $this->config->visitCost('unblinded', $c) * $derived->unblindedVisits
            );
        }

        // Close-out visits (on-site)
        if ($derived->closeoutVisitsOnsite > 0) {
            $costs->addActiveService(
                'closeout_visits_onsite',
                $this->config->visitCost('closeout_onsite', $c) * $derived->closeoutVisitsOnsite
            );
        }

        // Close-out visits (remote)
        if ($derived->closeoutVisitsRemote > 0) {
            $costs->addActiveService(
                'closeout_visits_remote',
                $this->config->visitCost('closeout_remote', $c) * $derived->closeoutVisitsRemote
            );
        }
    }

    private function calcSitePaymentCosts(DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);

        // Administration of site payments (5 hrs * CRA rate * site-months)
        $costs->addActiveService(
            'site_payment_admin',
            5 * $craRate * $derived->siteMonthsActive
        );
    }

    private function calcSafetyReportingCosts(DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);
        $raRate = $this->config->hourlyRate('ra', $c);

        // Assisting investigators in reporting SAEs (4 hrs * CRA rate * SAE count)
        $costs->addActiveService(
            'sae_assistance',
            4 * $craRate * $derived->saes
        );

        // Expedited safety notifications to EC/IRB
        $costs->addActiveService(
            'expedited_safety_ec',
            $craRate * $derived->expeditedSafetySubmissions * $derived->ecIrbCount
        );

        // Periodic safety notifications to EC/IRB
        $costs->addActiveService(
            'periodic_safety_ec',
            $craRate * $derived->periodicSafetyNotifications * $derived->ecIrbCount
        );

        // Expedited safety notifications to RA (non-US)
        if (!$this->config->isUs($c)) {
            $costs->addActiveService(
                'expedited_safety_ra',
                $this->config->regulatoryHours('expedited_safety_ra') * $raRate * $derived->expeditedSafetySubmissions
            );
        }

        // Periodic safety notifications to RA
        $costs->addActiveService(
            'periodic_safety_ra',
            $this->config->regulatoryHours('periodic_safety_ra') * $raRate * $derived->periodicSafetyNotifications
        );
    }

    private function calcPassthroughCosts(DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;
        $sites = $derived->sites;
        $annualCycles = $derived->annualSubmissionCycles;

        // Travel - monitoring visits
        if ($derived->monitoringVisitsOnsite > 0) {
            $costs->addActivePassthrough(
                'travel_monitoring',
                $this->config->fixedCost('travel_omv', $c) * $derived->monitoringVisitsOnsite
            );
        }

        // Travel - unblinded visits
        if ($derived->unblindedVisits > 0) {
            $costs->addActivePassthrough(
                'travel_unblinded',
                $this->config->fixedCost('travel_omv', $c) * $derived->unblindedVisits
            );
        }

        // Travel - close-out visits
        if ($derived->closeoutVisitsOnsite > 0) {
            $costs->addActivePassthrough(
                'travel_closeout',
                $this->config->fixedCost('travel_cov', $c) * $derived->closeoutVisitsOnsite
            );
        }

        // Various ongoing costs (per site-month)
        $costs->addActivePassthrough(
            'various_ongoing',
            $this->config->fixedCost('various_ongoing', $c) * $derived->siteMonthsActive
        );

        // Monitor visit fees
        $totalActiveVisits = $derived->monitoringVisitsOnsite 
            + $derived->unblindedVisits 
            + $derived->closeoutVisitsOnsite;
        $costs->addActivePassthrough(
            'monitor_visit_fee',
            $this->config->fixedCost('monitor_visit_fee', $c) * $totalActiveVisits
        );

        // US-only site fees
        if ($this->config->isUs($c)) {
            // Site regulatory annual fee
            $costs->addActivePassthrough(
                'site_regulatory_annual',
                $this->config->fixedCost('site_regulatory_annual', $c) * $annualCycles * $sites
            );

            // Pharmacy annual fee
            $costs->addActivePassthrough(
                'pharmacy_annual',
                $this->config->fixedCost('pharmacy_annual', $c) * $annualCycles * $sites
            );

            // Site close-out fee
            $costs->addActivePassthrough(
                'site_closeout_fee',
                $this->config->fixedCost('site_closeout_fee', $c) * $sites
            );

            // Pharmacy close-out fee
            $costs->addActivePassthrough(
                'pharmacy_closeout_fee',
                $this->config->fixedCost('pharmacy_closeout_fee', $c) * $sites
            );

            // Central IRB fee (active phase)
            $costs->addActivePassthrough(
                'central_irb',
                $this->config->global('central_irb_fee') * $derived->activePhaseMonths * $sites
            );
        }
    }
}
