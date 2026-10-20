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

namespace Bgomesweb\GeminiProvider\Api;

interface CostCalculatorInterface
{
    /**
     * Calculates the USD cost for a request based on token usage.
     *
     * @param  string   $model
     * @param  int      $tokensInput
     * @param  int      $tokensOutput
     * @param  int|null $storeId
     * @return float
     */
    public function calculateUsd(string $model, int $tokensInput, int $tokensOutput, ?int $storeId = null): float;

    /**
     * Calculates the BRL cost for a request based on token usage.
     *
     * @param  string   $model
     * @param  int      $tokensInput
     * @param  int      $tokensOutput
     * @param  int|null $storeId
     * @return float
     */
    public function calculateBrl(string $model, int $tokensInput, int $tokensOutput, ?int $storeId = null): float;

    /**
     * Converts a USD amount to BRL using the configured exchange rate.
     *
     * @param  float    $costUsd
     * @param  int|null $storeId
     * @return float
     */
    public function convertUsdToBrl(float $costUsd, ?int $storeId = null): float;
}
