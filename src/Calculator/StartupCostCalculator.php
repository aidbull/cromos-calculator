<?php

declare(strict_types=1);

namespace BallparkCalculator\Calculator;

use BallparkCalculator\Config\CountryRates;
use BallparkCalculator\Model\CostBreakdown;
use BallparkCalculator\Model\DerivedInputs;
use BallparkCalculator\Model\ProjectInput;

class StartupCostCalculator
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
        if ($derived->startupMonths === 0.0) {
            return;
        }

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

    private function calcSiteSelectionCosts(DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);

        $costs->addStartupService('sites_contacted', 0.5 * $craRate * $derived->sitesContacted);
        $costs->addStartupService('cdas_signed', 1.5 * $craRate * $derived->sitesCdas);
        $costs->addStartupService('questionnaires_collected', 2 * $craRate * $derived->sitesQuestionnaires);
        $costs->addStartupService('site_regulatory_docs', 8 * $craRate * $derived->sites);
    }

    private function calcQualificationVisitCosts(DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;

        if ($derived->qualificationVisitsOnsite > 0) {
            $costs->addStartupService(
                'qualification_visits_onsite',
                $this->config->visitCost('qualification_onsite', $c) * $derived->qualificationVisitsOnsite
            );
        }

        if ($derived->qualificationVisitsRemote > 0) {
            $costs->addStartupService(
                'qualification_visits_remote',
                $this->config->visitCost('qualification_remote', $c) * $derived->qualificationVisitsRemote
            );
        }
    }

    private function calcContractCosts(DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;

        if ($derived->sites === 0) {
            return;
        }

        $costs->addStartupService(
            'contract_templates',
            $this->config->contractTemplateCost($c) * $derived->countires
        );

        $costs->addStartupService(
            'contract_negotiation',
            $this->config->contractNegotiationCost($c) * $derived->sites
        );
    }

    private function calcRegulatoryCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;

        $costs->addStartupService(
            'initial_ec_submissions',
            $this->config->regulatoryHours('initial_ec_submission') * $this->config->hourlyRate('ra', $c) * $derived->sites
        );

        if (!$this->config->isUs($c)) {
            $costs->addStartupService(
                'country_dossier',
                $this->config->regulatoryHours('country_dossier') * $this->config->hourlyRate('ra', $c) * $derived->countires
            );
        }
    }

    private function calcMeetingCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;

        $costs->addStartupService(
            'investigator_meeting',
            4 * $this->config->hourlyRate('investigator_meeting', $c) * $derived->crasRequired
        );
    }

    private function calcProjectPlanCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;
        $pmRate = $this->config->hourlyRate('pm', $c);
        $craRate = $this->config->hourlyRate('cra', $c);

        $costs->addStartupService('team_setup', 2 * $pmRate * $derived->crasRequired);
        $costs->addStartupService('team_training', 22 * $craRate * $derived->crasRequired);
    }

    private function calcClinicalOpsCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;
        $craRate = $this->config->hourlyRate('cra', $c);
        $pmRate = $this->config->hourlyRate('pm', $c);
        $adminRate = $this->config->hourlyRate('admin', $c);

        $costs->addStartupService(
            'tmf_maintenance',
            round($this->config->monthlyCost('tmf_maintenance', $c) * $derived->startupMonths, 2)
        );

        $costs->addStartupService(
            'ctms_update',
            $this->config->monthlyCost('ctms_update', $c) * $derived->startupMonths
        );

        $costs->addStartupService(
            'internal_communication',
            2 * $craRate * $derived->startupMonths
        );

        $costs->addStartupService(
            'team_management',
            2 * $pmRate * $derived->countires * $derived->startupMonths
        );

        $costs->addStartupService(
            'visit_report_review',
            $pmRate * ($derived->getTotalQualificationVisits() + $derived->getTotalInitiationVisits())
        );

        $costs->addStartupService(
            'country_issues_resolution',
            4 * $pmRate * $derived->countires * $derived->startupMonths
        );

        $costs->addStartupService('sites_setup', 14 * $craRate * $derived->sites);

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

        $costs->addStartupService(
            'passthrough_management',
            4 * $adminRate * $derived->startupMonths
        );
    }

    private function calcQaCosts(ProjectInput $project, DerivedInputs $derived, CostBreakdown $costs)
    {
        // QA costs are primarily global
    }

    private function calcPassthroughCosts(DerivedInputs $derived, CostBreakdown $costs)
    {
        $c = $derived->country;

        if ($derived->qualificationVisitsOnsite > 0) {
            $costs->addStartupPassthrough(
                'travel_qualification',
                $this->config->fixedCost('travel_sqv', $c) * $derived->qualificationVisitsOnsite
            );
        }

        if ($derived->initiationVisitsOnsite > 0) {
            $costs->addStartupPassthrough(
                'travel_initiation',
                $this->config->fixedCost('travel_siv', $c) * $derived->initiationVisitsOnsite
            );
        }

        $costs->addStartupPassthrough(
            'translation',
            $this->config->fixedCost('translation_cost', $c) * $derived->countires
        );

        $costs->addStartupPassthrough(
            'copying_printing',
            $this->config->fixedCost('copying_printing', $c) * $derived->countires
        );

        $costs->addStartupPassthrough(
            'communication',
            $this->config->fixedCost('communication_expense', $c) * $derived->sites
        );

        if ($this->config->isUs($c)) {
            $costs->addStartupPassthrough(
                'central_irb',
                $this->config->global('central_irb_fee') * $derived->sites * $derived->startupMonths
            );
        }

        $costs->addStartupPassthrough(
            'site_startup_fee',
            $this->config->fixedCost('site_startup_fee', $c) * $derived->sites
        );

        $costs->addStartupPassthrough(
            'site_contract_fee',
            $this->config->fixedCost('site_contract_fee', $c) * $derived->sites
        );

        $totalStartupVisits = $derived->qualificationVisitsOnsite + $derived->initiationVisitsOnsite;
        $costs->addStartupPassthrough(
            'monitor_visit_fee',
            $this->config->fixedCost('monitor_visit_fee', $c) * $totalStartupVisits
        );
    }
}
