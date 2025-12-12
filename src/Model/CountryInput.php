<?php

declare(strict_types=1);

namespace BallparkCalculator\Model;

final class CountryInput
{
    public function __construct(
        public readonly string $country,
        public readonly int $sites = 0,
        public readonly int $patients = 0,
        public readonly int $monitoringVisitsOnsite = 0,
        public readonly int $monitoringVisitsRemote = 0,
        public readonly int $unblindedVisits = 0,
        public readonly ?int $countriesInRegion = null, // Number of actual countries (for EC/IRB count)
    ) {}
    
    /**
     * Get the number of EC/IRBs (countries) in this region.
     * Defaults based on Excel logic if not explicitly set.
     */
    public function getCounties(): int
    {
        if ($this->countriesInRegion !== null) {
            return $this->countriesInRegion;
        }
        
        // Excel default logic varies by region:
        // US, Turkiye: 1 if active
        // Non_EU, Georgia, Ukraine: equals number of sites
        // EU_CEE, EU_West: 1 if active (but user should specify)
        return match ($this->country) {
            'US', 'Turkiye' => $this->sites > 0 ? 1 : 0,
            'Non_EU', 'Georgia', 'Ukraine' => $this->sites,
            default => $this->sites > 0 ? 1 : 0,
        };
    }

    public function isActive(): bool
    {
        return $this->sites > 0;
    }

    public function hasUnblindedVisits(): bool
    {
        return $this->unblindedVisits > 0;
    }

    public static function fromArray(string $country, array $data): self
    {
        return new self(
            country: $country,
            sites: (int) ($data['sites'] ?? 0),
            patients: (int) ($data['patients'] ?? 0),
            monitoringVisitsOnsite: (int) ($data['monitoring_onsite'] ?? 0),
            monitoringVisitsRemote: (int) ($data['monitoring_remote'] ?? 0),
            unblindedVisits: (int) ($data['unblinded_visits'] ?? 0),
            countriesInRegion: isset($data['countries_in_region']) ? (int) $data['countries_in_region'] : null,
        );
    }
}
