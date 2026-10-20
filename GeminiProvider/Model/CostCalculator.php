<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 *
 * @author      Bruno Gomes <bgomesweb@gmail.com>
 * @copyright   2025 Bruno Gomes (<https://www.bgomesweb.com.br/>)
 * @license     <https://www.bgomesweb.com.br> Copyright
 * @link        <https://www.bgomesweb.com.br/>
 */

declare(strict_types=1);

namespace Bgomesweb\GeminiProvider\Model;

use Bgomesweb\GeminiProvider\Api\ConfigInterface;
use Bgomesweb\GeminiProvider\Api\CostCalculatorInterface;
use Psr\Log\LoggerInterface;

class CostCalculator implements CostCalculatorInterface
{
    /**
     * @param ConfigInterface $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Calculates the USD cost for a request based on token usage.
     *
     * @param  string   $model
     * @param  int      $tokensInput
     * @param  int      $tokensOutput
     * @param  int|null $storeId
     * @return float
     */
    public function calculateUsd(string $model, int $tokensInput, int $tokensOutput, ?int $storeId = null): float
    {
        if ($tokensInput <= 0 && $tokensOutput <= 0) {
            return 0.0;
        }

        $pricing = $this->config->getModelPricing($model, $storeId);
        if ($pricing === null) {
            $this->logger->warning(
                sprintf(
                    '[GeminiProvider] Pricing configuration missing for model "%s", returning 0.0 USD cost fallback.',
                    $model
                )
            );
            return 0.0;
        }

        $cost = ($tokensInput / 1_000_000) * $pricing['input']
            + ($tokensOutput / 1_000_000) * $pricing['output'];

        return round($cost, 8);
    }

    /**
     * Calculates the BRL cost for a request based on token usage.
     *
     * @param  string   $model
     * @param  int      $tokensInput
     * @param  int      $tokensOutput
     * @param  int|null $storeId
     * @return float
     */
    public function calculateBrl(string $model, int $tokensInput, int $tokensOutput, ?int $storeId = null): float
    {
        $costUsd = $this->calculateUsd($model, $tokensInput, $tokensOutput, $storeId);
        return $this->convertUsdToBrl($costUsd, $storeId);
    }

    /**
     * Converts a USD amount to BRL using the configured exchange rate.
     *
     * @param  float    $costUsd
     * @param  int|null $storeId
     * @return float
     */
    public function convertUsdToBrl(float $costUsd, ?int $storeId = null): float
    {
        $exchangeRate = $this->config->getExchangeRateBrl($storeId);
        return round($costUsd * $exchangeRate, 8);
    }
}
