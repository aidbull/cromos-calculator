<?php

declare(strict_types=1);

namespace BallparkCalculator\Model;

/**
 * Contains all calculated costs for a country.
 * Organized by phase (startup/active) and type (service/pass-through).
 */
final class CostBreakdown
{
    /** @var array<string, float> */
    private array $startupService = [];
    
    /** @var array<string, float> */
    private array $startupPassthrough = [];
    
    /** @var array<string, float> */
    private array $activeService = [];
    
    /** @var array<string, float> */
    private array $activePassthrough = [];

    public function __construct(
        public readonly string $country,
    ) {}

    // ---- Setters ----

    public function addStartupService(string $item, float $cost): self
    {
        $this->startupService[$item] = $cost;
        return $this;
    }

    public function addStartupPassthrough(string $item, float $cost): self
    {
        $this->startupPassthrough[$item] = $cost;
        return $this;
    }

    public function addActiveService(string $item, float $cost): self
    {
        $this->activeService[$item] = $cost;
        return $this;
    }

    public function addActivePassthrough(string $item, float $cost): self
    {
        $this->activePassthrough[$item] = $cost;
        return $this;
    }

    // ---- Getters ----

    /** @return array<string, float> */
    public function getStartupService(): array
    {
        return $this->startupService;
    }

    /** @return array<string, float> */
    public function getStartupPassthrough(): array
    {
        return $this->startupPassthrough;
    }

    /** @return array<string, float> */
    public function getActiveService(): array
    {
        return $this->activeService;
    }

    /** @return array<string, float> */
    public function getActivePassthrough(): array
    {
        return $this->activePassthrough;
    }

    // ---- Totals ----

    public function getStartupServiceTotal(): float
    {
        return array_sum($this->startupService);
    }

    public function getStartupPassthroughTotal(): float
    {
        return array_sum($this->startupPassthrough);
    }

    public function getStartupTotal(): float
    {
        return $this->getStartupServiceTotal() + $this->getStartupPassthroughTotal();
    }

    public function getActiveServiceTotal(): float
    {
        return array_sum($this->activeService);
    }

    public function getActivePassthroughTotal(): float
    {
        return array_sum($this->activePassthrough);
    }

    public function getActiveTotal(): float
    {
        return $this->getActiveServiceTotal() + $this->getActivePassthroughTotal();
    }

    public function getServiceTotal(): float
    {
        return $this->getStartupServiceTotal() + $this->getActiveServiceTotal();
    }

    public function getPassthroughTotal(): float
    {
        return $this->getStartupPassthroughTotal() + $this->getActivePassthroughTotal();
    }

    public function getGrandTotal(): float
    {
        return $this->getStartupTotal() + $this->getActiveTotal();
    }

    // ---- Rounding for ballpark estimate ----

    public function getRoundedStartupService(): float
    {
        return round($this->getStartupServiceTotal(), -3);
    }

    public function getRoundedStartupPassthrough(): float
    {
        return round($this->getStartupPassthroughTotal(), -3);
    }

    public function getRoundedActiveService(): float
    {
        return round($this->getActiveServiceTotal(), -3);
    }

    public function getRoundedActivePassthrough(): float
    {
        return round($this->getActivePassthroughTotal(), -3);
    }

    public function getRoundedGrandTotal(): float
    {
        return $this->getRoundedStartupService()
            + $this->getRoundedStartupPassthrough()
            + $this->getRoundedActiveService()
            + $this->getRoundedActivePassthrough();
    }

    // ---- Export ----

    public function toArray(): array
    {
        return [
            'country' => $this->country,
            'startup' => [
                'service' => $this->startupService,
                'service_total' => $this->getRoundedStartupService(),
                'passthrough' => $this->startupPassthrough,
                'passthrough_total' => $this->getRoundedStartupPassthrough(),
                'total' => $this->getRoundedStartupService() + $this->getRoundedStartupPassthrough(),
            ],
            'active' => [
                'service' => $this->activeService,
                'service_total' => $this->getRoundedActiveService(),
                'passthrough' => $this->activePassthrough,
                'passthrough_total' => $this->getRoundedActivePassthrough(),
                'total' => $this->getRoundedActiveService() + $this->getRoundedActivePassthrough(),
            ],
            'totals' => [
                'service' => $this->getRoundedStartupService() + $this->getRoundedActiveService(),
                'passthrough' => $this->getRoundedStartupPassthrough() + $this->getRoundedActivePassthrough(),
                'grand_total' => $this->getRoundedGrandTotal(),
            ],
        ];
    }
}
