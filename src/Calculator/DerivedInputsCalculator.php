<?php

declare(strict_types=1);

namespace BallparkCalculator\Calculator;

use BallparkCalculator\Config\CountryRates;
use BallparkCalculator\Model\CountryInput;
use BallparkCalculator\Model\DerivedInputs;
use BallparkCalculator\Model\ProjectInput;
use BallparkCalculator\Model\VisitType;

class DerivedInputsCalculator
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
     * @param CountryInput $country
     * @return DerivedInputs
     */
    public function calculate(ProjectInput $project, CountryInput $country)
    {
        if (!$country->isActive()) {
            return $this->createEmpty($country->country);
        }

        $startupMonths = $this->config->startupMonths($country->country);
        $activePhaseMonths = $project->getActivePhaseDuration();
        
        if ($this->config->isUs($country->country)) {
            $sitePayments = $activePhaseMonths * $country->sites;
            $monitoringVisitsRemote = $country->monitoringVisitsRemote * $country->sites;
        } else {
            $sitePayments = $activePhaseMonths / 3 * $country->sites;
            $monitoringVisitsRemote = 0;
        }

        return new DerivedInputs(
            $country->country,
            $country->sites,
            $country->patients,
            $startupMonths,
            $activePhaseMonths,
            (int)ceil($startupMonths) + $activePhaseMonths,
            (int)round($this->config->global('site_multiplier_contacted') * $country->sites),
            (int)round($this->config->global('site_multiplier_cdas') * $country->sites),
            (int)round($this->config->global('site_multiplier_questionnaires') * $country->sites),
            $this->calcQualificationOnsite($project, $country),
            $this->calcQualificationRemote($project, $country),
            $this->calcInitiationOnsite($project, $country),
            $this->calcInitiationRemote($project, $country),
            $country->monitoringVisitsOnsite * $country->sites,
            $monitoringVisitsRemote,
            $country->unblindedVisits * $country->sites,
            $this->calcCloseoutOnsite($project, $country),
            $this->calcCloseoutRemote($project, $country),
            $activePhaseMonths * $country->sites,
            (int)round($sitePayments),
            (int)round($project->saeRate * $country->patients),
            $this->calcExpeditedSafetySubmissions($project, $activePhaseMonths),
            (int)round($activePhaseMonths / 12),
            $country->getCounties(),
            (int)round($activePhaseMonths / 12),
            $this->calcCrasRequired($project, $country)
        );
    }

    /**
     * @param ProjectInput $project
     * @param CountryInput $country
     * @return int
     */
    private function calcQualificationOnsite(ProjectInput $project, CountryInput $country)
    {
        if ($project->qualificationVisitType !== VisitType::ON_SITE) {
            return 0;
        }
        return (int)round($this->config->global('qualification_onsite_pct') * $country->sites);
    }

    /**
     * @param ProjectInput $project
     * @param CountryInput $country
     * @return int
     */
    private function calcQualificationRemote(ProjectInput $project, CountryInput $country)
    {
        if ($project->qualificationVisitType !== VisitType::REMOTE) {
            return 0;
        }
        return (int)round($this->config->global('qualification_remote_pct') * $country->sites);
    }

    /**
     * @param ProjectInput $project
     * @param CountryInput $country
     * @return int
     */
    private function calcInitiationOnsite(ProjectInput $project, CountryInput $country)
    {
        if ($project->initiationVisitType !== VisitType::ON_SITE) {
            return 0;
        }
        return (int)round($this->config->global('initiation_onsite_pct') * $country->sites);
    }

    /**
     * @param ProjectInput $project
     * @param CountryInput $country
     * @return int
     */
    private function calcInitiationRemote(ProjectInput $project, CountryInput $country)
    {
        if ($project->initiationVisitType !== VisitType::REMOTE) {
            return 0;
        }
        return (int)round($this->config->global('initiation_remote_pct') * $country->sites);
    }

    /**
     * @param ProjectInput $project
     * @param CountryInput $country
     * @return int
     */
    private function calcCloseoutOnsite(ProjectInput $project, CountryInput $country)
    {
        if ($project->closeoutVisitType !== VisitType::ON_SITE) {
            return 0;
        }
        return (int)round($this->config->global('closeout_onsite_pct') * $country->sites);
    }

    /**
     * @param ProjectInput $project
     * @param CountryInput $country
     * @return int
     */
    private function calcCloseoutRemote(ProjectInput $project, CountryInput $country)
    {
        if ($project->closeoutVisitType !== VisitType::REMOTE) {
            return 0;
        }
        return (int)round($this->config->global('closeout_remote_pct') * $country->sites);
    }

    /**
     * @param ProjectInput $project
     * @param int $activePhaseMonths
     * @return int
     */
    private function calcExpeditedSafetySubmissions(ProjectInput $project, $activePhaseMonths)
    {
        $weeksInPhase = $activePhaseMonths * 30.4 / 7;
        return (int)round($weeksInPhase / $project->susarsWeeks);
    }

    /**
     * @param ProjectInput $project
     * @param CountryInput $country
     * @return int
     */
    private function calcCrasRequired(ProjectInput $project, CountryInput $country)
    {
        if (!$country->isActive()) {
            return 0;
        }
        $base = $project->hasUnblindedVisits() ? 2 : 1;
        return $base * $country->getCounties();
    }

    /**
     * @param string $country
     * @return DerivedInputs
     */
    private function createEmpty($country)
    {
        return new DerivedInputs(
            $country,
            0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
        );
    }
}
