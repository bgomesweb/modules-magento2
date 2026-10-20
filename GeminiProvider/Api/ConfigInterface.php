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

interface ConfigInterface
{
    /**
     * Whether the Gemini AI Provider is enabled.
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool;

    /**
     * Returns the decrypted API key.
     *
     * @param  int|null $storeId
     * @return string
     */
    public function getApiKey(?int $storeId = null): string;

    /**
     * Returns the configured models in fallback-priority order (first entry is primary).
     *
     * @param  int|null $storeId
     * @return string[]
     */
    public function getModels(?int $storeId = null): array;

    /**
     * Returns the configured timeout, falling back to the default.
     *
     * @param  int|null $storeId
     * @return int
     */
    public function getTimeout(?int $storeId = null): int;

    /**
     * Returns the configured max output tokens, falling back to the default.
     *
     * @param  int|null $storeId
     * @return int
     */
    public function getMaxOutputTokens(?int $storeId = null): int;

    /**
     * Returns the API endpoint template
     *
     * @param  int|null $storeId
     * @return string
     */
    public function getEndpoint(?int $storeId = null): string;

    /**
     * Whether fallback models are enabled.
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function isFallbackEnabled(?int $storeId = null): bool;

    /**
     * Returns the BRL exchange rate, falling back to the default.
     *
     * @param  int|null $storeId
     * @return float
     */
    public function getExchangeRateBrl(?int $storeId = null): float;

    /**
     * Get pricing for a specific model.
     *
     * @param  string   $modelName
     * @param  int|null $storeId
     * @return array{input: float, output: float}|null
     */
    public function getModelPricing(string $modelName, ?int $storeId = null): ?array;
}
