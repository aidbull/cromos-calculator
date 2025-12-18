<?php

declare(strict_types=1);

namespace BallparkCalculator\Model;

/**
 * Contains all calculated costs for a country.
 */
class CostBreakdown
{
    /** @var string */
    public $country;
    
    /** @var array<string, float> */
    private $startupService = array();
    
    /** @var array<string, float> */
    private $startupPassthrough = array();
    
    /** @var array<string, float> */
    private $activeService = array();
    
    /** @var array<string, float> */
    private $activePassthrough = array();

    /**
     * @param string $country
     */
    public function __construct($country)
    {
        $this->country = $country;
    }

    /**
     * @param string $item
     * @param float $cost
     * @return $this
     */
    public function addStartupService($item, $cost)
    {
        $this->startupService[$item] = (float)$cost;
        return $this;
    }

    /**
     * @param string $item
     * @param float $cost
     * @return $this
     */
    public function addStartupPassthrough($item, $cost)
    {
        $this->startupPassthrough[$item] = (float)$cost;
        return $this;
    }

    /**
     * @param string $item
     * @param float $cost
     * @return $this
     */
    public function addActiveService($item, $cost)
    {
        $this->activeService[$item] = (float)$cost;
        return $this;
    }

    /**
     * @param string $item
     * @param float $cost
     * @return $this
     */
    public function addActivePassthrough($item, $cost)
    {
        $this->activePassthrough[$item] = (float)$cost;
        return $this;
    }

    /** @return array<string, float> */
    public function getStartupService()
    {
        return $this->startupService;
    }

    /** @return array<string, float> */
    public function getStartupPassthrough()
    {
        return $this->startupPassthrough;
    }

    /** @return array<string, float> */
    public function getActiveService()
    {
        return $this->activeService;
    }

    /** @return array<string, float> */
    public function getActivePassthrough()
    {
        return $this->activePassthrough;
    }

    /** @return float */
    public function getStartupServiceTotal()
    {
        return array_sum($this->startupService);
    }

    /** @return float */
    public function getStartupPassthroughTotal()
    {
        return array_sum($this->startupPassthrough);
    }

    /** @return float */
    public function getStartupTotal()
    {
        return $this->getStartupServiceTotal() + $this->getStartupPassthroughTotal();
    }

    /** @return float */
    public function getActiveServiceTotal()
    {
        return array_sum($this->activeService);
    }

    /** @return float */
    public function getActivePassthroughTotal()
    {
        return array_sum($this->activePassthrough);
    }

    /** @return float */
    public function getActiveTotal()
    {
        return $this->getActiveServiceTotal() + $this->getActivePassthroughTotal();
    }

    /** @return float */
    public function getServiceTotal()
    {
        return $this->getStartupServiceTotal() + $this->getActiveServiceTotal();
    }

    /** @return float */
    public function getPassthroughTotal()
    {
        return $this->getStartupPassthroughTotal() + $this->getActivePassthroughTotal();
    }

    /** @return float */
    public function getGrandTotal()
    {
        return $this->getStartupTotal() + $this->getActiveTotal();
    }

    /** @return float */
    public function getRoundedStartupService()
    {
        return round($this->getStartupServiceTotal(), -3);
    }

    /** @return float */
    public function getRoundedStartupPassthrough()
    {
        return round($this->getStartupPassthroughTotal(), -3);
    }

    /** @return float */
    public function getRoundedActiveService()
    {
        return round($this->getActiveServiceTotal(), -3);
    }

    /** @return float */
    public function getRoundedActivePassthrough()
    {
        return round($this->getActivePassthroughTotal(), -3);
    }

    /** @return float */
    public function getRoundedGrandTotal()
    {
        return $this->getRoundedStartupService()
            + $this->getRoundedStartupPassthrough()
            + $this->getRoundedActiveService()
            + $this->getRoundedActivePassthrough();
    }

    /** @return array */
    public function toArray()
    {
        return array(
            'country' => $this->country,
            'startup' => array(
                'service' => $this->startupService,
                'service_total' => $this->getRoundedStartupService(),
                'passthrough' => $this->startupPassthrough,
                'passthrough_total' => $this->getRoundedStartupPassthrough(),
                'total' => $this->getRoundedStartupService() + $this->getRoundedStartupPassthrough(),
            ),
            'active' => array(
                'service' => $this->activeService,
                'service_total' => $this->getRoundedActiveService(),
                'passthrough' => $this->activePassthrough,
                'passthrough_total' => $this->getRoundedActivePassthrough(),
                'total' => $this->getRoundedActiveService() + $this->getRoundedActivePassthrough(),
            ),
            'totals' => array(
                'service' => $this->getRoundedStartupService() + $this->getRoundedActiveService(),
                'passthrough' => $this->getRoundedStartupPassthrough() + $this->getRoundedActivePassthrough(),
                'grand_total' => $this->getRoundedGrandTotal(),
            ),
        );
    }
}
