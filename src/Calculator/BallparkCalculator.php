<?php

declare(strict_types=1);

namespace BallparkCalculator\Calculator;

use BallparkCalculator\Config\CountryRates;
use BallparkCalculator\Model\CostBreakdown;
use BallparkCalculator\Model\DerivedInputs;
use BallparkCalculator\Model\ProjectInput;

/**
 * Main orchestrator for ballpark cost calculations.
 * Coordinates calculation across all countries and phases.
 */
final class BallparkCalculator
{
    private DerivedInputsCalculator $derivedCalculator;
    private StartupCostCalculator $startupCalculator;
    private ActivePhaseCostCalculator $activeCalculator;

    public function __construct(
        private readonly CountryRates $config,
    ) {
        $this->derivedCalculator = new DerivedInputsCalculator($config);
        $this->startupCalculator = new StartupCostCalculator($config);
        $this->activeCalculator = new ActivePhaseCostCalculator($config);
    }

    /**
     * Calculate costs for all countries in the project.
     * 
     * @return array{
     *     countries: array<string, CostBreakdown>,
     *     global: CostBreakdown,
     *     totals: array{startup: float, active: float, grand_total: float}
     * }
     */
    public function calculate(ProjectInput $project): array
    {
        $countryBreakdowns = [];
        $derivedByCountry = [];

        // Calculate per-country costs
        foreach ($project->getActiveCountries() as $countryInput) {
            $derived = $this->derivedCalculator->calculate($project, $countryInput);
            $derivedByCountry[$countryInput->country] = $derived;

            $costs = new CostBreakdown($countryInput->country);
            
            $this->startupCalculator->calculate($project, $derived, $costs);
            $this->activeCalculator->calculate($project, $derived, $costs);
            
            $countryBreakdowns[$countryInput->country] = $costs;
        }

        // Calculate global/project-level costs
        $globalCosts = $this->calculateGlobalCosts($project, $derivedByCountry);

        // Aggregate totals
        $totals = $this->aggregateTotals($countryBreakdowns, $globalCosts);

        return [
            'countries' => $countryBreakdowns,
            'global' => $globalCosts,
            'totals' => $totals,
        ];
    }

    /**
     * @param array<string, DerivedInputs> $derivedByCountry
     */
    private function calculateGlobalCosts(ProjectInput $project, array $derivedByCountry): CostBreakdown
    {
        $costs = new CostBreakdown('GLOBAL');

        if ($project->getTotalSites() === 0) {
            return $costs;
        }

        $pmRate = $this->config->hourlyRate('pm', 'US'); // Global PM rate
        $maxStartupMonths = 0;
        $maxActiveMonths = 0;

        foreach ($derivedByCountry as $derived) {
            $maxStartupMonths = max($maxStartupMonths, $derived->startupMonths);
            $maxActiveMonths = max($maxActiveMonths, $derived->activePhaseMonths);
        }

        // ---- STARTUP GLOBAL COSTS ----

        // Questionnaire development (one-time)
        $costs->addStartupService(
            'questionnaire_development',
            $this->config->global('questionnaire_development')
        );

        // EU Part I Study Dossier (if EU countries active)
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

        // External kick-off meeting
        $pmCount = $this->calculatePmCount($project);
        $costs->addStartupService(
            'external_kickoff',
            3105.5 + 202 * ($pmCount - 1)
        );

        // Investigator start-up meeting (global portion)
        $costs->addStartupService(
            'investigator_meeting_global',
            5714 + 202 * ($pmCount - 1)
        );

        // Regular client calls (startup weeks)
        $startupWeeks = (int) round($maxStartupMonths * 30.4 / 7);
        $costs->addStartupService(
            'client_calls',
            (0.5 + 1 + 0.5) * (202 + 77) * $startupWeeks + 202 * ($pmCount - 1) * $startupWeeks
        );

        // Project plans (one-time)
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

        // Vendors & team setup
        $costs->addStartupService('vendors_setup', 3 * $this->config->global('vendors_setup'));
        $costs->addStartupService('team_setup', $this->config->global('team_setup') );
        $costs->addStartupService('team_training', 20 * 202 * $pmCount * $derived->ecIrbCount);

        // Global TMF & tracking (startup)
        $costs->addStartupService('tmf_maintenance', $this->config->global('tmf_maintenance_global') * $maxStartupMonths);
        $costs->addStartupService('vendor_management', 808 * $vendors * $maxStartupMonths);
        $costs->addStartupService('tracking_reporting', $this->config->global('tracking_reporting') * $maxStartupMonths);
        $costs->addStartupService('budget_invoicing', $this->config->global('budget_invoicing') * $maxStartupMonths);

        // QA - startup
        $costs->addStartupService('protocol_checklist', $this->config->global('protocol_checklist'));
        $costs->addStartupService('tmf_audit_initial', $this->config->global('tmf_audit_initial'));
        
        if ($project->hasUnblindedVisits()) {
            $costs->addStartupService('tmf_audit_unblinded_initial', $this->config->global('tmf_audit_unblinded_initial'));
        }

        // ---- ACTIVE PHASE GLOBAL COSTS ----

        // EU Part I major submissions (CTIS)
        if ($project->hasEuCountries()) {
            $costs->addActiveService(
                'eu_ctis_major',
                32 * 179 // Fixed rate for EU regulatory
            );
        }

        // Regular client calls (active weeks)
        $activeWeeks = (int) round($maxActiveMonths * 30.4 / 7);
        $costs->addActiveService(
            'client_calls',
            (0.5 + 1 + 0.5) * (202 + 77) * $activeWeeks + 202 * ($pmCount - 1) * $activeWeeks
        );

        // Annual plan updates (33% of initial)
        $annualCycles = (int) round($maxActiveMonths / 12);
        $costs->addActiveService('project_management_plan_update', 0.33 * (10 + 8 + 3 * $vendors) * 202 * $annualCycles);
        $costs->addActiveService('tmf_management_plan_update', 0.33 * 10 * 202 * $annualCycles);
        $costs->addActiveService('monitoring_plan_update', 0.33 * 14 * 202 * $annualCycles);
        
        if ($project->hasUnblindedVisits()) {
            $costs->addActiveService('unblinded_monitoring_plan_update', 0.33 * 7 * 202 * $annualCycles);
        }
        
        $costs->addActiveService('risk_management_plan_update', 0.33 * 10 * 202 * $annualCycles);
        $costs->addActiveService('deviation_handling_plan_update', 0.33 * 8 * 202 * $annualCycles);
        $costs->addActiveService('quality_management_plan_update', 0.33 * 10 * 202 * $annualCycles);

        // Team retraining (30% of initial, annual)
        $costs->addActiveService('team_retraining', 0.30 * 20 * 202 * $pmCount * $annualCycles);

        // Global TMF & tracking (active)
        $costs->addActiveService('tmf_maintenance', $this->config->global('tmf_maintenance_global') * $maxActiveMonths);
        $costs->addActiveService('vendor_management', 808 * $vendors * $maxActiveMonths);
        $costs->addActiveService('tracking_reporting', $this->config->global('tracking_reporting') * $maxActiveMonths);
        $costs->addActiveService('budget_invoicing', $this->config->global('budget_invoicing') * $maxActiveMonths);

        // QA - active (annual)
        $costs->addActiveService('tmf_audit_annual', $this->config->global('tmf_audit_annual') * $annualCycles);
        
        if ($project->hasUnblindedVisits()) {
            $costs->addActiveService('tmf_audit_unblinded_annual', $this->config->global('tmf_audit_unblinded_annual') * $annualCycles);
        }

        return $costs;
    }

    private function calculatePmCount(ProjectInput $project): int
    {
        $base = $project->hasUnblindedVisits() ? 2 : 1;
        return max($base, 1);
    }

    /**
     * @param array<string, CostBreakdown> $countryBreakdowns
     */
    private function aggregateTotals(array $countryBreakdowns, CostBreakdown $globalCosts): array
    {
        $startupTotal = $globalCosts->getRoundedStartupService() + $globalCosts->getRoundedStartupPassthrough();
        $activeTotal = $globalCosts->getRoundedActiveService() + $globalCosts->getRoundedActivePassthrough();

        foreach ($countryBreakdowns as $breakdown) {
            $startupTotal += $breakdown->getRoundedStartupService() + $breakdown->getRoundedStartupPassthrough();
            $activeTotal += $breakdown->getRoundedActiveService() + $breakdown->getRoundedActivePassthrough();
        }

        return [
            'startup' => $startupTotal,
            'active' => $activeTotal,
            'grand_total' => $startupTotal + $activeTotal,
        ];
    }

    /**
     * Get detailed results as array (for JSON output).
     */
    public function calculateAsArray(ProjectInput $project): array
    {
        $result = $this->calculate($project);

        $output = [
            'countries' => [],
            'global' => $result['global']->toArray(),
            'totals' => $result['totals'],
        ];

        foreach ($result['countries'] as $country => $breakdown) {
            $output['countries'][$country] = $breakdown->toArray();
        }

        return $output;
    }
}
