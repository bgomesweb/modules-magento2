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
use Bgomesweb\GeminiProvider\Api\Data\GenerationResultInterface;
use Bgomesweb\GeminiProvider\Api\GeminiClientInterface;
use Bgomesweb\GeminiProvider\Model\Data\GenerationResultFactory;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Psr\Log\LoggerInterface;

class GeminiClient implements GeminiClientInterface
{
    private const array RECOVERABLE_HTTP_CODES = [404, 429, 500, 503];

    /**
     * @param HttpClient               $httpClient
     * @param ConfigInterface          $config
     * @param LoggerInterface          $logger
     * @param EventManagerInterface    $eventManager
     * @param CostCalculatorInterface  $costCalculator
     * @param GenerationResultFactory  $generationResultFactory
     * @param ResponseSanitizer        $responseSanitizer
     */
    public function __construct(
        private readonly HttpClient               $httpClient,
        private readonly ConfigInterface          $config,
        private readonly LoggerInterface          $logger,
        private readonly EventManagerInterface    $eventManager,
        private readonly CostCalculatorInterface  $costCalculator,
        private readonly GenerationResultFactory  $generationResultFactory,
        private readonly ResponseSanitizer        $responseSanitizer,
    ) {
    }

    /**
     * Generates content via the Gemini API, trying fallback models on recoverable failures.
     *
     * @param  string   $prompt
     * @param  string   $content
     * @param  int|null $storeId
     * @param  array    $meta
     * @return GenerationResultInterface
     */
    public function generateContent(
        string $prompt,
        string $content,
        ?int $storeId = null,
        array $meta = []
    ): GenerationResultInterface {
        if (!$this->config->isEnabled($storeId)) {
            throw new \RuntimeException(
                __(
                    'The Gemini AI Provider is disabled. Enable it in Stores > Configuration '
                    . '> Bgomesweb > Gemini AI Provider.'
                )->render()
            );
        }

        $apiKey = $this->config->getApiKey($storeId);

        if ($apiKey === '') {
            throw new \RuntimeException(
                __(
                    'Google Generative AI API Key is not configured. Please configure it in Stores '
                    . '> Configuration > Bgomesweb > Gemini AI Provider.'
                )->render()
            );
        }

        $modelsToTry = $this->resolveModels($storeId);
        $lastException = null;

        foreach ($modelsToTry as $model) {
            try {
                return $this->callApi($model, $apiKey, $prompt, $content, $meta, $storeId);
            } catch (RecoverableApiException $e) {
                $this->logger->warning(
                    sprintf(
                        '[GeminiProvider] Model "%s" failed with recoverable error: %s. %s',
                        $model,
                        $e->getMessage(),
                        count($modelsToTry) > 1 ? 'Trying next model.' : 'No more models to try.'
                    )
                );
                $lastException = $e;
            }
        }

        throw new \RuntimeException(
            'Gemini API request failed: ' . ($lastException?->getMessage() ?? 'unknown error'),
            0,
            $lastException
        );
    }

    /**
     * Resolves the ordered list of models to try, primary first.
     *
     * @param  int|null $storeId
     * @return array
     */
    private function resolveModels(?int $storeId): array
    {
        $models = $this->config->getModels($storeId);

        if (!$this->config->isFallbackEnabled($storeId)) {
            return [$models[0]];
        }

        return $models;
    }

    /**
     * Calls the Gemini API for a single model and normalizes the response.
     *
     * @param  string   $model
     * @param  string   $apiKey
     * @param  string   $prompt
     * @param  string   $content
     * @param  array    $meta
     * @param  int|null $storeId
     * @return GenerationResultInterface
     */
    private function callApi(
        string $model,
        string $apiKey,
        string $prompt,
        string $content,
        array  $meta = [],
        ?int   $storeId = null
    ): GenerationResultInterface {
        $start = microtime(true);

        $endpoint = sprintf($this->config->getEndpoint($storeId), $model, $apiKey);

        $payload = $this->buildPayload($prompt, $content, $meta, $storeId);
        try {
            $response = $this->httpClient->post(
                $endpoint,
                [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body' => $payload,
                    'timeout' => $this->getTimeout($meta, $storeId),
                    'http_errors' => false,
                ]
            );
        } catch (GuzzleException $e) {
            $this->logger->error('[GeminiProvider] HTTP request failed: ' . $e->getMessage());
            $this->dispatchAiEvent($model, $meta, $storeId, 'error', 0, 0, $start, 0.0, $e->getMessage());
            throw new RecoverableApiException('Gemini API request failed: ' . $e->getMessage(), 0, $e);
        }

        $httpStatus = $response->getStatusCode();
        $responseBody = (string)$response->getBody();

        if ($httpStatus === 200) {
            return $this->buildSuccessResult($responseBody, $model, $meta, $storeId, $start);
        }

        $this->handleErrorResponse($httpStatus, $responseBody, $model, $meta, $storeId, $start);
    }

    /**
     * Validates and normalizes a 200 API response into a GenerationResult.
     *
     * @param  string   $responseBody
     * @param  string   $model
     * @param  array    $meta
     * @param  int|null $storeId
     * @param  float    $start
     * @return GenerationResultInterface
     * @throws RecoverableApiException When the response body fails sanitization/extraction.
     */
    private function buildSuccessResult(
        string $responseBody,
        string $model,
        array  $meta,
        ?int   $storeId,
        float  $start
    ): GenerationResultInterface {
        $data = json_decode($responseBody, true);
        $usage = $data['usageMetadata'] ?? [];
        $candidate = $data['candidates'][0] ?? [];
        $finishReason = $candidate['finishReason'] ?? 'STOP';
        $tokensIn = (int)($usage['promptTokenCount'] ?? 0);

        $tokensOut = (int)($usage['candidatesTokenCount'] ?? 0)
            + (int)($usage['thoughtsTokenCount'] ?? 0);

        $status = GenerationResultInterface::STATUS_SUCCESS;
        if ($finishReason === 'SAFETY') {
            $status = GenerationResultInterface::STATUS_BLOCKED;
        } elseif ($finishReason === 'MAX_TOKENS') {
            $status = GenerationResultInterface::STATUS_TRUNCATED;
        }

        $costUsd = $this->costCalculator->calculateUsd($model, $tokensIn, $tokensOut, $storeId);

        $parts = $candidate['content']['parts'] ?? [];
        $functionCall = array_column($parts, 'functionCall')[0] ?? null;

        try {
            if ($functionCall === null) {
                $text = $this->responseSanitizer->sanitize($responseBody);
            } else {
                $text = '';
            }
        } catch (\Throwable $e) {
            $this->dispatchAiEvent(
                $model,
                $meta,
                $storeId,
                $status,
                $tokensIn,
                $tokensOut,
                $start,
                $costUsd,
                $e->getMessage()
            );
            throw new RecoverableApiException('Failed to sanitize response: ' . $e->getMessage(), 0, $e);
        }

        $this->dispatchAiEvent(
            $model,
            $meta,
            $storeId,
            $status,
            $tokensIn,
            $tokensOut,
            $start,
            $costUsd
        );

        $resultData = [
            GenerationResultInterface::KEY_TEXT => $text,
            GenerationResultInterface::KEY_MODEL => $model,
            GenerationResultInterface::KEY_TOKENS_INPUT => $tokensIn,
            GenerationResultInterface::KEY_TOKENS_OUTPUT => $tokensOut,
            GenerationResultInterface::KEY_STATUS => $status,
            GenerationResultInterface::KEY_COST_USD => $costUsd,
            'parts' => $parts,
        ];

        if ($functionCall !== null) {
            $resultData['function_call'] = $functionCall;
        }

        return $this->generationResultFactory->create(
            [
                'data' => $resultData,
            ]
        );
    }

    /**
     * Builds the JSON request body for a generateContent call.
     *
     * @param  string   $prompt
     * @param  string   $content
     * @param  array    $meta
     * @param  int|null $storeId
     * @return string
     */
    private function buildPayload(string $prompt, string $content, array $meta, ?int $storeId): string
    {
        if (!empty($meta['contents']) && is_array($meta['contents'])) {
            $contents = $meta['contents'];
        } else {
            $contents = [
                ['role' => 'user', 'parts' => [['text' => $content]]]
            ];
        }

        $contents = $this->normalizeContentsForRequest($contents);

        $payload = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [['text' => $prompt]]
            ],

            'generationConfig' => [
                'maxOutputTokens' => $this->getMaxOutputTokens($meta, $storeId),
                'temperature'     => 0.2,
            ],
        ];

        if (!empty($meta['tools']) && is_array($meta['tools'])) {
            $functionDeclarations = [];
            foreach ($meta['tools'] as $tool) {
                $declaration = [
                    'name'        => $tool['name'],
                    'description' => $tool['description'] ?? '',
                ];
                if (!empty($tool['parameters'])) {
                    $declaration['parameters'] = $tool['parameters'];
                }
                $functionDeclarations[] = $declaration;
            }

            $payload['tools'] = [
                ['functionDeclarations' => $functionDeclarations],
            ];

            $payload['toolConfig'] = [
                'functionCallingConfig' => [
                    'mode' => $meta['tool_config_mode'] ?? 'AUTO',
                ],
            ];
        } else {
            $payload['generationConfig']['responseMimeType'] = $meta['response_mime_type'] ?? 'application/json';
        }

        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array $contents
     * @return array
     */
    private function normalizeContentsForRequest(array $contents): array
    {
        $normalized = [];
        foreach ($contents as $item) {
            if (!is_array($item)) {
                $normalized[] = $item;
                continue;
            }

            $normItem = $item;
            if (isset($item['parts']) && is_array($item['parts'])) {
                $turnThoughtSignature = null;
                foreach ($item['parts'] as $part) {
                    if (is_array($part)) {
                        if (!empty($part['thought_signature'])) {
                            $turnThoughtSignature = (string)$part['thought_signature'];
                            break;
                        } elseif (!empty($part['thoughtSignature'])) {
                            $turnThoughtSignature = (string)$part['thoughtSignature'];
                            break;
                        }
                    }
                }

                $normParts = [];
                foreach ($item['parts'] as $part) {
                    if (!is_array($part)) {
                        $normParts[] = $part;
                        continue;
                    }

                    $normPart = [];
                    foreach ($part as $key => $val) {
                        $newKey = $key;
                        if ($key === 'functionCall' || $key === 'function_call') {
                            $newKey = 'function_call';
                        } elseif ($key === 'functionResponse' || $key === 'function_call_response' || $key === 'function_response') {
                            $newKey = 'function_response';
                        } elseif ($key === 'thoughtSignature' || $key === 'thought_signature') {
                            $newKey = 'thought_signature';
                        }

                        if ($newKey === 'function_call' && is_array($val)) {
                            $cleanVal = $val;
                            unset($cleanVal['thought_signature'], $cleanVal['thoughtSignature']);
                            $normPart[$newKey] = $cleanVal;
                        } elseif ($newKey === 'function_response' && is_array($val)) {
                            $cleanVal = $val;
                            unset($cleanVal['thought_signature'], $cleanVal['thoughtSignature']);
                            $normPart[$newKey] = $cleanVal;
                        } else {
                            $normPart[$newKey] = $val;
                        }
                    }

                    if (isset($normPart['function_call']) && empty($normPart['thought_signature']) && $turnThoughtSignature !== null) {
                        $normPart['thought_signature'] = $turnThoughtSignature;
                    }

                    $normParts[] = $normPart;
                }
                $normItem['parts'] = $normParts;
            }
            $normalized[] = $normItem;
        }

        return $normalized;
    }

    /**
     * Logs, dispatches the AI event, and throws for a non-200 API response.
     * Recoverable HTTP codes (see self::RECOVERABLE_HTTP_CODES) throw RecoverableApiException
     * so the caller can retry with a fallback model; everything else is fatal.
     *
     * @param  int      $httpStatus
     * @param  string   $responseBody
     * @param  string   $model
     * @param  array    $meta
     * @param  int|null $storeId
     * @param  float    $start
     * @return never
     * @throws RecoverableApiException
     * @throws \RuntimeException
     */
    private function handleErrorResponse(
        int    $httpStatus,
        string $responseBody,
        string $model,
        array  $meta,
        ?int   $storeId,
        float  $start
    ): never {
        $errorDetail = $this->parseApiError($responseBody);
        $errorMessage = sprintf('HTTP %d: %s', $httpStatus, $errorDetail);

        if (in_array($httpStatus, self::RECOVERABLE_HTTP_CODES, true)) {
            $this->dispatchAiEvent($model, $meta, $storeId, 'error', 0, 0, $start, 0.0, $errorMessage);
            throw new RecoverableApiException($errorMessage, $httpStatus);
        }

        $this->logger->error(
            sprintf('[GeminiProvider] Gemini API returned HTTP %d.', $httpStatus),
            ['error' => $errorDetail]
        );

        $this->dispatchAiEvent($model, $meta, $storeId, 'error', 0, 0, $start, 0.0, $errorMessage);

        throw new \RuntimeException(sprintf('Gemini API returned HTTP %d: %s', $httpStatus, $errorDetail));
    }

    /**
     * Resolves the request timeout, preferring a per-call override.
     *
     * @param  array    $meta
     * @param  int|null $storeId
     * @return int
     */
    private function getTimeout(array $meta, ?int $storeId): int
    {
        $override = isset($meta['timeout']) && is_numeric($meta['timeout']) ? (int)$meta['timeout'] : 0;

        return $override > 0 ? $override : $this->config->getTimeout($storeId);
    }

    /**
     * Resolves the max output tokens, preferring a per-call override.
     *
     * @param  array    $meta
     * @param  int|null $storeId
     * @return int
     */
    private function getMaxOutputTokens(array $meta, ?int $storeId): int
    {
        $override = isset($meta['max_output_tokens']) && is_numeric($meta['max_output_tokens'])
            ? (int)$meta['max_output_tokens']
            : 0;

        return $override > 0 ? $override : $this->config->getMaxOutputTokens($storeId);
    }

    /**
     * Dispatches the AI request-completed event, swallowing any dispatch errors.
     *
     * @param  string      $model
     * @param  array       $meta
     * @param  int|null    $storeId
     * @param  string      $status
     * @param  int         $tokensIn
     * @param  int         $tokensOut
     * @param  float       $start
     * @param  float       $costUsd
     * @param  string|null $errorMessage
     * @return void
     */
    private function dispatchAiEvent(
        string  $model,
        array   $meta,
        ?int    $storeId,
        string  $status,
        int     $tokensIn,
        int     $tokensOut,
        float   $start,
        float   $costUsd,
        ?string $errorMessage = null
    ): void {
        try {
            $latencyMs = (int)round((microtime(true) - $start) * 1000);

            $this->eventManager->dispatch(
                'bgomesweb_ai_request_completed',
                array_merge(
                    $meta,
                    [
                    'provider' => 'gemini',
                    'model' => $model,
                    'store_id' => $storeId ?? (int)($meta['store_id'] ?? 0),
                    'tokens_input' => $tokensIn,
                    'tokens_output' => $tokensOut,
                    'cost_usd' => $costUsd,
                    'latency_ms' => $latencyMs,
                    'status' => $status,
                    'error_message' => $errorMessage,
                    ]
                )
            );
        } catch (\Throwable $e) {
            $this->logger->error('[GeminiProvider] AiStats dispatch error: ' . $e->getMessage());
        }
    }

    /**
     * Extracts a human-readable error message from an API error response.
     *
     * @param  string $responseBody
     * @return string
     */
    private function parseApiError(string $responseBody): string
    {
        $data = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $responseBody;
        }

        return $data['error']['message'] ?? $responseBody;
    }
}
