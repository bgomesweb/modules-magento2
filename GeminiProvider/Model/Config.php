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
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

class Config implements ConfigInterface
{
    private const string XML_PATH_ENABLED = 'bgomesweb_gemini_provider/general/enabled';
    private const string XML_PATH_API_KEY = 'bgomesweb_gemini_provider/general/api_key';
    private const string XML_PATH_MODEL_LIST = 'bgomesweb_gemini_provider/general/models';
    private const string XML_PATH_FALLBACK_ENABLED = 'bgomesweb_gemini_provider/general/fallback_enabled';
    private const string XML_PATH_TIMEOUT = 'bgomesweb_gemini_provider/general/timeout';
    private const string XML_PATH_MAX_OUTPUT_TOKENS = 'bgomesweb_gemini_provider/general/max_output_tokens';
    private const string XML_PATH_ENDPOINT = 'bgomesweb_gemini_provider/general/endpoint';
    private const string XML_PATH_EXCHANGE_RATE = 'bgomesweb_gemini_provider/pricing/exchange_rate_brl';
    private const string XML_PATH_MODELS = 'bgomesweb_gemini_provider/pricing/models';
    private const array DEFAULT_MODELS = [
        'gemini-2.5-flash-lite',
        'gemini-2.5-flash',
        'gemini-2.5-pro',
        'gemini-3.1-flash-lite',
        'gemini-3.5-flash',
    ];
    private const int DEFAULT_TIMEOUT = 60;
    private const int DEFAULT_MAX_OUTPUT_TOKENS = 8192;
    private const float DEFAULT_EXCHANGE_RATE = 5.30;
    private const string DEFAULT_ENDPOINT
        = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface   $encryptor
     * @param Json                 $jsonSerializer
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface   $encryptor,
        private readonly Json $jsonSerializer,
    ) {
    }

    /**
     * Whether the Gemini AI Provider is enabled.
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function isEnabled(int|null $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns the decrypted API key.
     *
     * @param  int|null $storeId
     * @return string
     */
    public function getApiKey(int|null $storeId = null): string
    {
        $encrypted = (string) $this->scopeConfig->getValue(
            self::XML_PATH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $encrypted !== '' ? $this->encryptor->decrypt($encrypted) : '';
    }

    /**
     * Returns the configured models in fallback-priority order (first entry is primary),
     * falling back to the default chain.
     *
     * @param  int|null $storeId
     * @return string[]
     */
    public function getModels(int|null $storeId = null): array
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_MODEL_LIST, ScopeInterface::SCOPE_STORE, $storeId);

        if ($value) {
            try {
                $data = is_string($value) ? $this->jsonSerializer->unserialize($value) : $value;
                if (is_array($data)) {
                    $models = array_values(array_filter(array_map(
                        static fn(array $row): string => (string)($row['model'] ?? ''),
                        $data
                    )));
                    if ($models !== []) {
                        return $models;
                    }
                }
            } catch (\Exception $e) {
                // fall through to defaults
            }
        }

        return self::DEFAULT_MODELS;
    }

    /**
     * Returns the configured timeout, falling back to the default.
     *
     * @param  int|null $storeId
     * @return int
     */
    public function getTimeout(int|null $storeId = null): int
    {
        $timeout = (int) $this->scopeConfig->getValue(self::XML_PATH_TIMEOUT, ScopeInterface::SCOPE_STORE, $storeId);
        return $timeout > 0 ? $timeout : self::DEFAULT_TIMEOUT;
    }

    /**
     * Returns the configured max output tokens, falling back to the default.
     *
     * @param  int|null $storeId
     * @return int
     */
    public function getMaxOutputTokens(int|null $storeId = null): int
    {
        $maxOutputTokens = (int) $this->scopeConfig->getValue(
            self::XML_PATH_MAX_OUTPUT_TOKENS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $maxOutputTokens > 0 ? $maxOutputTokens : self::DEFAULT_MAX_OUTPUT_TOKENS;
    }

    /**
     * Returns the API endpoint template (%s placeholders for model, then API key),
     * falling back to the default.
     *
     * @param  int|null $storeId
     * @return string
     */
    public function getEndpoint(int|null $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_ENDPOINT, ScopeInterface::SCOPE_STORE, $storeId)
            ?: self::DEFAULT_ENDPOINT;
    }

    /**
     * Whether fallback models are enabled.
     *
     * @param  int|null $storeId
     * @return bool
     */
    public function isFallbackEnabled(int|null $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_FALLBACK_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Returns the BRL exchange rate, falling back to the default.
     *
     * @param  int|null $storeId
     * @return float
     */
    public function getExchangeRateBrl(int|null $storeId = null): float
    {
        $rate = (float) $this->scopeConfig->getValue(
            self::XML_PATH_EXCHANGE_RATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $rate > 0 ? $rate : self::DEFAULT_EXCHANGE_RATE;
    }

    /**
     * Get pricing for a specific model
     *
     * @param  string   $modelName
     * @param  int|null $storeId
     * @return array|null ['input' => float, 'output' => float] or null if missing
     */
    public function getModelPricing(string $modelName, int|null $storeId = null): ?array
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_MODELS, ScopeInterface::SCOPE_STORE, $storeId);
        if (!$value) {
            return null;
        }

        try {
            $data = is_string($value) ? $this->jsonSerializer->unserialize($value) : $value;
            if (is_array($data)) {
                foreach ($data as $row) {
                    if (isset($row['model']) && $row['model'] === $modelName) {
                        return [
                            'input'  => (float)($row['cost_input'] ?? 0),
                            'output' => (float)($row['cost_output'] ?? 0)
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}
