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

namespace Bgomesweb\GeminiProvider\Cron;

use Bgomesweb\GeminiProvider\Api\ConfigInterface;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class UpdateExchangeRate
{
    private const string XML_PATH_EXCHANGE_RATE = 'bgomesweb_gemini_provider/pricing/exchange_rate_brl';
    private const string API_URL = 'https://economia.awesomeapi.com.br/json/last/USD-BRL';

    /**
     * @param HttpClient          $httpClient
     * @param WriterInterface     $configWriter
     * @param TypeListInterface   $cacheTypeList
     * @param ConfigInterface     $config
     * @param LoggerInterface     $logger
     * @param Json                $jsonSerializer
     */
    public function __construct(
        private readonly HttpClient        $httpClient,
        private readonly WriterInterface   $configWriter,
        private readonly TypeListInterface $cacheTypeList,
        private readonly ConfigInterface   $config,
        private readonly LoggerInterface   $logger,
        private readonly Json              $jsonSerializer,
    ) {
    }

    /**
     * Fetches the current USD/BRL rate from AwesomeAPI and persists it to the default scope.
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        $body = $this->fetchRateBody();
        if ($body === null) {
            return;
        }

        $newRate = $this->extractBid($body);
        if ($newRate === null) {
            return;
        }

        $this->saveRate($newRate);
    }

    /**
     * Calls the exchange rate API and returns the raw response body, or null on failure.
     *
     * @return string|null
     */
    private function fetchRateBody(): ?string
    {
        try {
            $response = $this->httpClient->get(
                self::API_URL,
                [
                    'timeout' => 10,
                    'http_errors' => false,
                ]
            );
        } catch (GuzzleException $e) {
            $this->logger->warning('[GeminiProvider] Exchange rate request failed: ' . $e->getMessage());
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->warning(
                sprintf('[GeminiProvider] Exchange rate API returned HTTP %d.', $response->getStatusCode())
            );
            return null;
        }

        return (string) $response->getBody();
    }

    /**
     * Parses and validates the API response body, returning the bid rate, or null on failure.
     *
     * @param string $body
     * @return float|null
     */
    private function extractBid(string $body): ?float
    {
        try {
            $data = $this->jsonSerializer->unserialize($body);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('[GeminiProvider] Exchange rate response is not valid JSON: ' . $body);
            return null;
        }

        $bid = $data['USDBRL']['bid'] ?? null;

        if (!is_numeric($bid) || (float) $bid <= 0) {
            $this->logger->warning('[GeminiProvider] Exchange rate response missing a valid bid: ' . $body);
            return null;
        }

        return (float) $bid;
    }

    /**
     * Persists the new rate to config, cleans the config cache, and logs the change.
     *
     * @param float $newRate
     * @return void
     */
    private function saveRate(float $newRate): void
    {
        $oldRate = $this->config->getExchangeRateBrl();

        $this->configWriter->save(
            self::XML_PATH_EXCHANGE_RATE,
            $newRate,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        $this->cacheTypeList->cleanType('config');

        $this->logger->info(
            sprintf('[GeminiProvider] Updated USD/BRL exchange rate: %.4f -> %.4f', $oldRate, $newRate)
        );
    }
}
