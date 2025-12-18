<?php

declare(strict_types=1);

namespace BallparkCalculator\Calculator;

use BallparkCalculator\Config\CountryRates;
use BallparkCalculator\Model\CostBreakdown;
use BallparkCalculator\Model\DerivedInputs;
use BallparkCalculator\Model\ProjectInput;

class ActivePhaseCostCalculator
{
    /** @var CountryRates */
    private $config;

    /**
     * @param CountryRates $config
     */
    public function __construct(CountryRates $config)
    {
        $this->config = $config;
    }

    /**
     * @param ProjectInput $project
     * @param DerivedInputs $derived
     * @param CostBreakdown $costs
     */
    public function calculate(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs)
    {
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

    private function calcRegulatoryCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;
        $raRate = $this->config->hourlyRate('ra', $c);
        $annualCycles = $derived->annualSubmissionCycles;

        if (!$this->config->isUs($c)) {
            $costs->addActiveService(
                'major_ra_submissions',
                $this->config->majorRaSubmissionCost($c) * $derived->countires
            );

            $minorRaHours = $this->config->regulatoryHours('minor_ra_submission');
            if ($c == 'EU_CEE') {
                $costs->addActiveService(
                    'minor_ra_submissions',
                    $minorRaHours * $raRate * 5
                );
            } else {
                $costs->addActiveService(
                    'minor_ra_submissions',
                    $minorRaHours * $raRate * (3 + $annualCycles)
                );
            }
        }

        $majorEcHours = $this->config->regulatoryHours('major_ec_submission');
        if ($this->config->isNonEuCountry($c)) {
            $costs->addActiveService(
                'major_ec_submissions',
                $majorEcHours * $raRate * $annualCycles * $derived->sites
            );

            $minorEcHours = $this->config->regulatoryHours('minor_ec_submission');
            $costs->addActiveService(
                'minor_ec_submissions',
                $minorEcHours * $raRate * (3 + $annualCycles * $derived->sites)
            );
        } elseif ($this->config->isUs($c)) {
            $costs->addActiveService(
                'major_ec_submissions',
                $majorEcHours * $raRate * $annualCycles
            );

            $minorEcHours = $this->config->regulatoryHours('minor_ec_submission');
            $costs->addActiveService(
                'minor_ec_submissions',
                $minorEcHours * $raRate * (5 + $annualCycles)
            );
        }
    }

    private function calcMeetingCosts(DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;
        $pmRate = $this->config->hourlyRate('pm', $c);
        $qaRate = $this->config->hourlyRate('qa', $c);

        // Regular client calls (already in global, country gets proportional share)
        // Formula: (0.5+1+0.5) * (PM + QA) * weeks
        $weeksInPhase = (int)round($derived->activePhaseMonths * 30.4 / 7);
    }

    private function calcProjectPlanUpdateCosts(DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);
        $annualCycles = $derived->annualSubmissionCycles;

        $initialTrainingCost = 22 * $craRate * $derived->crasRequired;
        $costs->addActiveService(
            'team_retraining',
            0.30 * $initialTrainingCost * $annualCycles
        );
    }

    private function calcClinicalOpsCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);
        $pmRate = $this->config->hourlyRate('pm', $c);
        $adminRate = $this->config->hourlyRate('admin', $c);
        $months = $derived->activePhaseMonths;

        $costs->addActiveService(
            'tmf_maintenance',
            $this->config->monthlyCost('tmf_maintenance', $c) * $months
        );

        $costs->addActiveService(
            'ctms_update',
            $this->config->monthlyCost('ctms_update', $c) * $months
        );

        $costs->addActiveService(
            'internal_communication',
            2 * $craRate * $months
        );

        $costs->addActiveService(
            'team_management',
            2 * $pmRate * $derived->countires * $months
        );

        if ($this->config->isUs($c)) {
            $totalVisits = $derived->monitoringVisitsOnsite
                + $derived->monitoringVisitsRemote
                + $derived->unblindedVisits
                + $derived->closeoutVisitsOnsite
                + $derived->closeoutVisitsRemote;
        } else {
            $totalVisits = $derived->monitoringVisitsOnsite
                + $derived->unblindedVisits
                + $derived->closeoutVisitsOnsite;
        }

        $costs->addActiveService(
            'visit_report_review',
            $pmRate * $totalVisits
        );

        $costs->addActiveService(
            'country_issues_resolution',
            4 * $pmRate * $derived->countires * $months
        );

        $costs->addActiveService(
            'passthrough_management',
            4 * $adminRate * $months
        );
    }

    private function calcMonitoringCosts(DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;

        $costs->addActiveService(
            'site_management',
            $this->config->siteManagementMonthlyCost($c) * $derived->siteMonthsActive
        );

        if ($derived->monitoringVisitsOnsite > 0) {
            $costs->addActiveService(
                'monitoring_visits_onsite',
                $this->config->visitCost('monitoring_onsite', $c) * $derived->monitoringVisitsOnsite
            );
        }

        if ($derived->monitoringVisitsRemote > 0) {
            $costs->addActiveService(
                'monitoring_visits_remote',
                $this->config->visitCost('monitoring_remote', $c) * $derived->monitoringVisitsRemote
            );
        }

        if ($derived->unblindedVisits > 0) {
            $costs->addActiveService(
                'unblinded_visits',
                $this->config->visitCost('unblinded', $c) * $derived->unblindedVisits
            );
        }

        if ($derived->closeoutVisitsOnsite > 0) {
            $costs->addActiveService(
                'closeout_visits_onsite',
                $this->config->visitCost('closeout_onsite', $c) * $derived->closeoutVisitsOnsite
            );
        }

        if ($derived->closeoutVisitsRemote > 0) {
            $costs->addActiveService(
                'closeout_visits_remote',
                $this->config->visitCost('closeout_remote', $c) * $derived->closeoutVisitsRemote
            );
        }
    }

    private function calcSitePaymentCosts(DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);

        $costs->addActiveService(
            'site_payment_admin',
            5 * $craRate * $derived->sitePayments
        );
    }

    private function calcSafetyReportingCosts(DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);
        $raRate = $this->config->hourlyRate('ra', $c);

        $costs->addActiveService(
            'sae_assistance',
            4 * $craRate * $derived->saes
        );

        if ($this->config->isNonEuCountry($c)) {
            $costs->addActiveService(
                'expedited_safety_ra',
                $raRate * $derived->expeditedSafetySubmissions * $this->config->regulatoryHours('expedited_safety_ra')
            );

            $costs->addActiveService(
                'periodic_safety_ra',
                $this->config->regulatoryHours('periodic_safety_ra') * $raRate * $derived->periodicSafetyNotifications
            );

            $costs->addActiveService(
                'expedited_safety_ec',
                $craRate * $derived->expeditedSafetySubmissions * $derived->sites
            );

            $costs->addActiveService(
                'periodic_safety_ec',
                $craRate * $derived->periodicSafetyNotifications * $derived->sites
            );
        } elseif ($this->config->isUs($c)) {
            $costs->addActiveService(
                'expedited_safety_ec',
                $craRate * $derived->expeditedSafetySubmissions
            );

            $costs->addActiveService(
                'periodic_safety_ec',
                $craRate * $derived->periodicSafetyNotifications
            );
        }

        if ($this->config->isEuCountry($c)) {
            $costs->addActiveService(
                'periodic_safety_ra',
                $this->config->regulatoryHours('periodic_safety_ra') * $raRate * $derived->periodicSafetyNotifications
            );
        }
    }

    private function calcPassthroughCosts(DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;
        $annualCycles = $derived->annualSubmissionCycles;

        if ($derived->monitoringVisitsOnsite > 0) {
            $costs->addActivePassthrough(
                'travel_monitoring',
                $this->config->fixedCost('travel_omv', $c) * $derived->monitoringVisitsOnsite
            );
        }

        if ($derived->unblindedVisits > 0) {
            $costs->addActivePassthrough(
                'travel_unblinded',
                $this->config->fixedCost('travel_cov', $c) * $derived->unblindedVisits
            );
        }

        if ($derived->closeoutVisitsOnsite > 0) {
            $costs->addActivePassthrough(
                'travel_closeout',
                $this->config->fixedCost('travel_cov', $c) * $derived->closeoutVisitsOnsite
            );
        }

        $costs->addActivePassthrough(
            'various_ongoing',
            $this->config->fixedCost('various_ongoing', $c) * $derived->siteMonthsActive
        );

        $totalActiveVisits = $derived->monitoringVisitsOnsite
            + $derived->unblindedVisits
            + $derived->closeoutVisitsOnsite;
        $costs->addActivePassthrough(
            'monitor_visit_fee',
            $this->config->fixedCost('monitor_visit_fee', $c) * $totalActiveVisits
        );

        if ($this->config->isUs($c)) {
            $costs->addActivePassthrough(
                'site_regulatory_annual',
                $this->config->fixedCost('site_regulatory_annual', $c) * $annualCycles * $derived->sites
            );

            $costs->addActivePassthrough(
                'pharmacy_annual',
                $this->config->fixedCost('pharmacy_annual', $c) * $annualCycles * $derived->sites
            );

            $costs->addActivePassthrough(
                'site_closeout_fee',
                $this->config->fixedCost('site_closeout_fee', $c) * $derived->sites
            );

            $costs->addActivePassthrough(
                'pharmacy_closeout_fee',
                $this->config->fixedCost('pharmacy_closeout_fee', $c) * $derived->sites
            );

            $costs->addActivePassthrough(
                'central_irb',
                $this->config->global('central_irb_fee') * $derived->activePhaseMonths * $derived->sites
            );
        }
    }
}
