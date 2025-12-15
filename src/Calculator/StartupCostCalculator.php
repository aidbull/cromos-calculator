<?php

declare(strict_types=1);

namespace BallparkCalculator\Calculator;

use BallparkCalculator\Config\CountryRates;
use BallparkCalculator\Model\CostBreakdown;
use BallparkCalculator\Model\DerivedInputs;
use BallparkCalculator\Model\ProjectInput;

final class StartupCostCalculator
{
    public function __construct(
        private readonly CountryRates $config,
    ) {}

    public function calculate(
        ProjectInput $project,
        DerivedInputs $derived,
        CostBreakdown $costs,
    ): void {
        if ($derived->startupMonths === 0.0) {
            return;
        }

        $country = $derived->country;
        
        $this->calcSiteSelectionCosts($derived, $costs);
        $this->calcQualificationVisitCosts($derived, $costs);
        $this->calcContractCosts($derived, $costs);
        $this->calcRegulatoryCosts($project, $derived, $costs);
        $this->calcMeetingCosts($project, $derived, $costs);
        $this->calcProjectPlanCosts($project, $derived, $costs);
        $this->calcClinicalOpsCosts($project, $derived, $costs);
        $this->calcQaCosts($project, $derived, $costs);
        $this->calcPassthroughCosts($derived, $costs);
    }

    private function calcSiteSelectionCosts(DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);

        // Potential sites contacted (0.5 hrs * rate * count)
        $costs->addStartupService(
            'sites_contacted',
            0.5 * $craRate * $derived->sitesContacted
        );

        // CDAs signed (1.5 hrs * rate * count)
        $costs->addStartupService(
            'cdas_signed',
            1.5 * $craRate * $derived->sitesCdas
        );

        // Questionnaires collected (2 hrs * rate * count)
        $costs->addStartupService(
            'questionnaires_collected',
            2 * $craRate * $derived->sitesQuestionnaires
        );

        // Collecting site regulatory documents (8 hrs * rate * sites)
        $costs->addStartupService(
            'site_regulatory_docs',
            8 * $craRate * $derived->sites
        );
    }

    private function calcQualificationVisitCosts(DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;

        // Qualification visits (on-site)
        if ($derived->qualificationVisitsOnsite > 0) {
            $costs->addStartupService(
                'qualification_visits_onsite',
                $this->config->visitCost('qualification_onsite', $c) * $derived->qualificationVisitsOnsite
            );
        }

        // Qualification visits (remote)
        if ($derived->qualificationVisitsRemote > 0) {
            $costs->addStartupService(
                'qualification_visits_remote',
                $this->config->visitCost('qualification_remote', $c) * $derived->qualificationVisitsRemote
            );
        }
    }

    private function calcContractCosts(DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;

        if ($derived->sites === 0) {
            return;
        }

        // Study-specific contract & budget templates (per country)
        $costs->addStartupService(
            'contract_templates',
            $this->config->contractTemplateCost($c) * $derived->countires
        );

        // Site-specific contracts & budgets negotiation (per site)
        $costs->addStartupService(
            'contract_negotiation',
            $this->config->contractNegotiationCost($c) * $derived->sites
        );
    }

    private function calcRegulatoryCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;

        // Initial EC/IRB submissions (per site)
        $costs->addStartupService(
            'initial_ec_submissions',
            $this->config->regulatoryHours('initial_ec_submission') * $this->config->hourlyRate('ra', $c) * $derived->sites
        );

        // EU-specific: Legal representation setup
        if (!$this->config->isUs($c)) {
            // Country-specific dossier
            $costs->addStartupService(
                'country_dossier',
                $this->config->regulatoryHours('country_dossier') * $this->config->hourlyRate('ra', $c) * $derived->countires
            );
        }
    }

    private function calcMeetingCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;

        // Investigator start-up meeting (4 hrs * investigator_meeting_rate * CRAs)
        $costs->addStartupService(
            'investigator_meeting',
            4 * $this->config->hourlyRate('investigator_meeting', $c) * $derived->crasRequired
        );
    }

    private function calcProjectPlanCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs): void
    {
        // Project plans are global costs, calculated only once at project level
        // Country-level: Team setup and training costs

        $c = $derived->country;
        $pmRate = $this->config->hourlyRate('pm', $c);
        $craRate = $this->config->hourlyRate('cra', $c);

        // Project team setup (2 hrs * PM rate * CRAs)
        $costs->addStartupService(
            'team_setup',
            2 * $pmRate * $derived->crasRequired
        );

        // Project team training (22 hrs * CRA rate * CRAs)
        $costs->addStartupService(
            'team_training',
            22 * $craRate * $derived->crasRequired
        );
    }

    private function calcClinicalOpsCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);
        $pmRate = $this->config->hourlyRate('pm', $c);
        $adminRate = $this->config->hourlyRate('admin', $c);

        // TMF Maintenance (startup months)
        $costs->addStartupService(
            'tmf_maintenance',
            round($this->config->monthlyCost('tmf_maintenance', $c) * $derived->startupMonths, 2)
        );

        // CTMS Update (startup months)
        $costs->addStartupService(
            'ctms_update',
            $this->config->monthlyCost('ctms_update', $c) * $derived->startupMonths
        );

        // Internal communication (2 hrs * rate * startup months)
        $costs->addStartupService(
            'internal_communication',
            2 * $craRate * $derived->startupMonths
        );

        // Project team management (2 hrs * PM rate * countries)
        $costs->addStartupService(
            'team_management',
            2 * $pmRate * $derived->countires * $derived->startupMonths
        );

        // Review of visit reports (PM rate * total qualification visits)
        $costs->addStartupService(
            'visit_report_review',
            $pmRate * ($derived->getTotalQualificationVisits() + $derived->getTotalInitiationVisits())
        );

        // Resolution of country-level issues (4 hrs * PM rate * countries * startup months)
        $costs->addStartupService(
            'country_issues_resolution',
            4 * $pmRate * $derived->countires * $derived->startupMonths
        );

        // Sites setup (14 hrs * CRA rate * sites)
        $costs->addStartupService(
            'sites_setup',
            14 * $craRate * $derived->sites
        );

        // Site initiation visits
        if ($derived->initiationVisitsOnsite > 0) {
            $costs->addStartupService(
                'initiation_visits_onsite',
                $this->config->visitCost('initiation_onsite', $c) * $derived->initiationVisitsOnsite
            );
        }
        if ($derived->initiationVisitsRemote > 0) {
            $costs->addStartupService(
                'initiation_visits_remote',
                $this->config->visitCost('initiation_remote', $c) * $derived->initiationVisitsRemote
            );
        }

        // Pass-through costs management (4 hrs * admin rate * startup months)
        $costs->addStartupService(
            'passthrough_management',
            4 * $adminRate * $derived->startupMonths
        );
    }

    private function calcQaCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs): void
    {
        // QA costs are primarily global, handled at project level
    }

    private function calcPassthroughCosts(DerivedInputs $derived, CostBreakdown $costs): void
    {
        $c = $derived->country;

        // Travel - qualification visits
        if ($derived->qualificationVisitsOnsite > 0) {
            $costs->addStartupPassthrough(
                'travel_qualification',
                $this->config->fixedCost('travel_sqv', $c) * $derived->qualificationVisitsOnsite
            );
        }

        // Travel - initiation visits
        if ($derived->initiationVisitsOnsite > 0) {
            $costs->addStartupPassthrough(
                'travel_initiation',
                $this->config->fixedCost('travel_siv', $c) * $derived->initiationVisitsOnsite
            );
        }

        // Translation costs (per country)
        $costs->addStartupPassthrough(
            'translation',
            $this->config->fixedCost('translation_cost', $c) * $derived->countires
        );

        // Copying & printing (per country)
        $costs->addStartupPassthrough(
            'copying_printing',
            $this->config->fixedCost('copying_printing', $c) * $derived->countires
        );

        // Communication expenses (per site)
        $costs->addStartupPassthrough(
            'communication',
            $this->config->fixedCost('communication_expense', $c) * $derived->sites
        );

        // Central IRB fee (US only)
        if ($this->config->isUs($c)) {
            $costs->addStartupPassthrough(
                'central_irb',
                $this->config->global('central_irb_fee') * $derived->sites * $derived->startupMonths
            );
        }

        // Site startup fee
        $costs->addStartupPassthrough(
            'site_startup_fee',
            $this->config->fixedCost('site_startup_fee', $c) * $derived->sites
        );

        // Site contract negotiation fee
        $costs->addStartupPassthrough(
            'site_contract_fee',
            $this->config->fixedCost('site_contract_fee', $c) * $derived->sites
        );

        // Monitor visit fees
        $totalStartupVisits = $derived->qualificationVisitsOnsite + $derived->initiationVisitsOnsite;
        $costs->addStartupPassthrough(
            'monitor_visit_fee',
            $this->config->fixedCost('monitor_visit_fee', $c) * $totalStartupVisits
        );
    }
}
