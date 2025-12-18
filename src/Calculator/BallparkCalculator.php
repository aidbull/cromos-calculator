<?php

declare(strict_types=1);

namespace BallparkCalculator\Calculator;

use BallparkCalculator\Config\CountryRates;
use BallparkCalculator\Model\CostBreakdown;
use BallparkCalculator\Model\DerivedInputs;
use BallparkCalculator\Model\ProjectInput;

/**
 * Main orchestrator for ballpark cost calculations.
 */
class BallparkCalculator
{
    /** @var CountryRates */
    private $config;
    
    /** @var DerivedInputsCalculator */
    private $derivedCalculator;
    
    /** @var StartupCostCalculator */
    private $startupCalculator;
    
    /** @var ActivePhaseCostCalculator */
    private $activeCalculator;

    /**
     * @param CountryRates $config
     */
    public function __construct(CountryRates $config)
    {
        $this->config = $config;
        $this->derivedCalculator = new DerivedInputsCalculator($config);
        $this->startupCalculator = new StartupCostCalculator($config);
        $this->activeCalculator = new ActivePhaseCostCalculator($config);
    }

    /**
     * @param ProjectInput $project
     * @return array
     */
    public function calculate(ProjectInput $project)
    {
        $countryBreakdowns = array();
        $derivedByCountry = array();

        foreach ($project->getActiveCountries() as $countryInput) {
            $derived = $this->derivedCalculator->calculate($project, $countryInput);
            $derivedByCountry[$countryInput->country] = $derived;

            $costs = new CostBreakdown($countryInput->country);
            
            $this->startupCalculator->calculate($project, $derived, $costs);
            $this->activeCalculator->calculate($project, $derived, $costs);
            
            $countryBreakdowns[$countryInput->country] = $costs;
        }

        $globalCosts = $this->calculateGlobalCosts($project, $derivedByCountry);
        $totals = $this->aggregateTotals($countryBreakdowns, $globalCosts);

        return array(
            'countries' => $countryBreakdowns,
            'global' => $globalCosts,
            'totals' => $totals,
        );
    }

    /**
     * @param ProjectInput $project
     * @param array $derivedByCountry
     * @return CostBreakdown
     */
    private function calculateGlobalCosts(ProjectInput $project, array $derivedByCountry)
    {
        $costs = new CostBreakdown('GLOBAL');

        if ($project->getTotalSites() === 0) {
            return $costs;
        }

        $pmRate = $this->config->hourlyRate('pm', 'US');
        $maxStartupMonths = 0;
        $maxActiveMonths = 0;

        /** @var DerivedInputs $derived */
        foreach ($derivedByCountry as $derived) {
            $maxStartupMonths = max($maxStartupMonths, $derived->startupMonths);
            $maxActiveMonths = max($maxActiveMonths, $derived->activePhaseMonths);
        }

        // Get first derived for some calculations
        $derived = reset($derivedByCountry);
        if (!$derived) {
            return $costs;
        }

        // ---- STARTUP GLOBAL COSTS ----

        $costs->addStartupService(
            'questionnaire_development',
            $this->config->global('questionnaire_development')
        );

        if ($project->hasEuCountries()) {
            $costs->addStartupService(
                'eu_part1_dossier',
                $this->config->global('eu_part1_dossier')
            );

            $costs->addStartupService(
                'eu_legal_rep_setup',
                $this->config->global('eu_legal_rep_setup')
            );
        }

        $pmCount = $this->calculatePmCount($project);
        $costs->addStartupService(
            'external_kickoff',
            3105.5 + 202 * ($pmCount - 1)
        );

        $costs->addStartupService(
            'investigator_meeting_global',
            5714 + 202 * ($pmCount - 1)
        );

        $startupWeeks = (int)round($maxStartupMonths * 30.4 / 7);
        $costs->addStartupService(
            'client_calls',
            (0.5 + 1 + 0.5) * (202 + 77) * $startupWeeks + 202 * ($pmCount - 1) * $startupWeeks
        );

        $vendors = $project->vendors;
        $costs->addStartupService('project_management_plan', (10 + 8 + 3 * $vendors) * 202);
        $costs->addStartupService('tmf_management_plan', 10 * 202);
        $costs->addStartupService('monitoring_plan', 14 * 202);
        
        if ($project->hasUnblindedVisits()) {
            $costs->addStartupService('unblinded_monitoring_plan', 7 * 202);
        }
        
        $costs->addStartupService('risk_management_plan', 10 * 202);
        $costs->addStartupService('deviation_handling_plan', 8 * 202);
        $costs->addStartupService('quality_management_plan', 10 * 202);

        $costs->addStartupService('vendors_setup', 3 * $this->config->global('vendors_setup'));
        $costs->addStartupService('team_setup', $this->config->global('team_setup'));
        $costs->addStartupService('team_training', 20 * 202 * $pmCount);
        $costs->addStartupService('internal_communication', $this->config->global('internal_communication') * $maxStartupMonths);

        $costs->addStartupService('tmf_maintenance', $this->config->global('tmf_maintenance_global') * $maxStartupMonths);
        $costs->addStartupService('vendor_management', 808 * $vendors * $maxStartupMonths);
        $costs->addStartupService('tracking_reporting', $this->config->global('tracking_reporting') * $maxStartupMonths);
        $costs->addStartupService('budget_invoicing', $this->config->global('budget_invoicing') * $maxStartupMonths);

        $costs->addStartupService('protocol_checklist', $this->config->global('protocol_checklist'));
        $costs->addStartupService('tmf_audit_initial', $this->config->global('tmf_audit_initial'));
        
        if ($project->hasUnblindedVisits()) {
            $costs->addStartupService('tmf_audit_unblinded_initial', $this->config->global('tmf_audit_unblinded_initial'));
        }

        $costs->addStartupService('passthrough_management', $this->config->global('passthrough_management') * $maxStartupMonths);

        // ---- ACTIVE PHASE GLOBAL COSTS ----

        $costs->addActiveService('eu_legal_rep', $this->config->global('eu_legal_rep_annual') * $derived->activePhaseMonths);

        if ($project->hasEuCountries()) {
            $costs->addActiveService(
                'eu_ctis_major',
                32 * 179
            );
            $costs->addStartupService('eu_ctis_minor', 179 * 4 * (1 + $derived->annualSubmissionCycles));

            $costs->addActiveService(
                'periodic_safety_ra',
                $this->config->regulatoryHours('periodic_safety_ra') * 179 * $derived->periodicSafetyNotifications
            );
        }

        $activeWeeks = (int)round($maxActiveMonths * 30.4 / 7);
        $costs->addActiveService(
            'client_calls',
            ((0.5 + 1 + 0.5) * (202 + 77) + 202 * ($pmCount - 1)) * $maxActiveMonths
        );

        $annualCycles = (int)round($maxActiveMonths / 12);
        $costs->addActiveService('project_management_plan_update', 0.33 * (10 + 8 + 3 * $vendors) * 202 * $annualCycles);
        $costs->addActiveService('tmf_management_plan_update', 0.33 * 10 * 202 * $annualCycles);
        $costs->addActiveService('monitoring_plan_update', 0.33 * 14 * 202 * $annualCycles);
        
        if ($project->hasUnblindedVisits()) {
            $costs->addActiveService('unblinded_monitoring_plan_update', 0.33 * 7 * 202 * $annualCycles);
        }
        
        $costs->addActiveService('risk_management_plan_update', 0.33 * 10 * 202 * $annualCycles);
        $costs->addActiveService('deviation_handling_plan_update', 0.33 * 8 * 202 * $annualCycles);
        $costs->addActiveService('quality_management_plan_update', 0.33 * 10 * 202 * $annualCycles);

        $costs->addActiveService('team_retraining', 0.30 * 20 * 202 * $pmCount * $annualCycles);

        $costs->addActiveService('tmf_maintenance', $this->config->global('tmf_maintenance_global') * $maxActiveMonths);
        $costs->addActiveService('vendor_management', 808 * $vendors * $maxActiveMonths);
        $costs->addActiveService('tracking_reporting', $this->config->global('tracking_reporting') * $maxActiveMonths);
        $costs->addActiveService('budget_invoicing', $this->config->global('budget_invoicing') * $maxActiveMonths);
        $costs->addActiveService('internal_communication', $this->config->global('internal_communication') * $maxActiveMonths);

        $costs->addActiveService('tmf_audit_annual', $this->config->global('tmf_audit_annual') * $annualCycles);
        
        if ($project->hasUnblindedVisits()) {
            $costs->addActiveService('tmf_audit_unblinded_annual', $this->config->global('tmf_audit_unblinded_annual') * $annualCycles);
        }

        $costs->addActiveService('passthrough_management', $this->config->global('passthrough_management') * $maxActiveMonths);

        return $costs;
    }

    /**
     * @param ProjectInput $project
     * @return int
     */
    private function calculatePmCount(ProjectInput $project)
    {
        $base = $project->hasUnblindedVisits() ? 2 : 1;
        return max($base, 1);
    }

    /**
     * @param array $countryBreakdowns
     * @param CostBreakdown $globalCosts
     * @return array
     */
    private function aggregateTotals(array $countryBreakdowns, CostBreakdown $globalCosts)
    {
        $startupTotal = $globalCosts->getRoundedStartupService() + $globalCosts->getRoundedStartupPassthrough();
        $activeTotal = $globalCosts->getRoundedActiveService() + $globalCosts->getRoundedActivePassthrough();

        /** @var CostBreakdown $breakdown */
        foreach ($countryBreakdowns as $breakdown) {
            $startupTotal += $breakdown->getRoundedStartupService() + $breakdown->getRoundedStartupPassthrough();
            $activeTotal += $breakdown->getRoundedActiveService() + $breakdown->getRoundedActivePassthrough();
        }

        return array(
            'startup' => $startupTotal,
            'active' => $activeTotal,
            'grand_total' => $startupTotal + $activeTotal,
        );
    }

    /**
     * @param ProjectInput $project
     * @return array
     */
    public function calculateAsArray(ProjectInput $project)
    {
        $result = $this->calculate($project);

        $output = array(
            'countries' => array(),
            'global' => $result['global']->toArray(),
            'totals' => $result['totals'],
        );

        /** @var CostBreakdown $breakdown */
        foreach ($result['countries'] as $country => $breakdown) {
            $output['countries'][$country] = $breakdown->toArray();
        }

        return $output;
    }
}
