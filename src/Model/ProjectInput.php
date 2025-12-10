<?php

declare(strict_types=1);

namespace BallparkCalculator\Model;

enum VisitType: string
{
    case ON_SITE = 'on-site';
    case REMOTE = 'remote';
}

final class ProjectInput
{
    /** @var array<string, CountryInput> */
    private array $countries = [];

    public function __construct(
        // Duration in months
        public readonly int $enrollmentMonths = 12,
        public readonly int $treatmentMonths = 4,
        public readonly int $followupMonths = 12,
        
        // Visit types
        public readonly VisitType $qualificationVisitType = VisitType::ON_SITE,
        public readonly VisitType $initiationVisitType = VisitType::REMOTE,
        public readonly VisitType $closeoutVisitType = VisitType::REMOTE,
        
        // Safety parameters
        public readonly float $saeRate = 0.15,
        public readonly int $susarsWeeks = 13,
        
        // Vendor count
        public readonly int $vendors = 1,
        
        // Investigator grant (optional, per patient)
        public readonly ?float $investigatorGrantPerPatient = null,
    ) {}

    public function addCountry(CountryInput $country): self
    {
        $this->countries[$country->country] = $country;
        return $this;
    }

    public function getCountry(string $name): ?CountryInput
    {
        return $this->countries[$name] ?? null;
    }

    /** @return array<string, CountryInput> */
    public function getActiveCountries(): array
    {
        return array_filter(
            $this->countries,
            fn(CountryInput $c) => $c->isActive()
        );
    }

    public function getTotalSites(): int
    {
        return array_sum(array_map(
            fn(CountryInput $c) => $c->sites,
            $this->countries
        ));
    }

    public function getTotalPatients(): int
    {
        return array_sum(array_map(
            fn(CountryInput $c) => $c->patients,
            $this->countries
        ));
    }

    public function getActiveCountryCount(): int
    {
        return count($this->getActiveCountries());
    }

    public function hasUnblindedVisits(): bool
    {
        foreach ($this->countries as $country) {
            if ($country->hasUnblindedVisits()) {
                return true;
            }
        }
        return false;
    }

    public function hasEuCountries(): bool
    {
        return isset($this->countries['EU_CEE']) && $this->countries['EU_CEE']->isActive()
            || isset($this->countries['EU_West']) && $this->countries['EU_West']->isActive();
    }

    public function getActivePhaseDuration(): int
    {
        return $this->enrollmentMonths + $this->treatmentMonths + $this->followupMonths + 3; // +3 closeout
    }

    public static function fromArray(array $data): self
    {
        $input = new self(
            enrollmentMonths: (int) ($data['enrollment_months'] ?? 12),
            treatmentMonths: (int) ($data['treatment_months'] ?? 4),
            followupMonths: (int) ($data['followup_months'] ?? 12),
            qualificationVisitType: VisitType::tryFrom($data['qualification_visit_type'] ?? 'on-site') ?? VisitType::ON_SITE,
            initiationVisitType: VisitType::tryFrom($data['initiation_visit_type'] ?? 'remote') ?? VisitType::REMOTE,
            closeoutVisitType: VisitType::tryFrom($data['closeout_visit_type'] ?? 'remote') ?? VisitType::REMOTE,
            saeRate: (float) ($data['sae_rate'] ?? 0.15),
            susarsWeeks: (int) ($data['susars_weeks'] ?? 13),
            vendors: (int) ($data['vendors'] ?? 1),
            investigatorGrantPerPatient: isset($data['investigator_grant']) ? (float) $data['investigator_grant'] : null,
        );

        $countryNames = ['US', 'EU_CEE', 'EU_West', 'Non_EU', 'Georgia', 'Turkiye', 'Ukraine'];
        foreach ($countryNames as $country) {
            if (isset($data['countries'][$country])) {
                $input->addCountry(CountryInput::fromArray($country, $data['countries'][$country]));
            }
        }

        return $input;
    }
}
