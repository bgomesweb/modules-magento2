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

namespace Bgomesweb\AiAssistant\Model\Service\Tool;

use Bgomesweb\AiAssistant\Model\Service\ConversationManager;
use Bgomesweb\AiAssistant\Api\FrontendToolProviderInterface;
use Bgomesweb\AiAssistant\Api\ToolExecutionLoopInterface;
use Bgomesweb\AiAssistant\Api\ToolRegistryInterface;
use Bgomesweb\GeminiProvider\Api\GeminiClientInterface;
use Psr\Log\LoggerInterface;

class ToolExecutionLoop implements ToolExecutionLoopInterface
{
    /**
     * @param GeminiClientInterface $geminiClient
     * @param ToolRegistryInterface $toolRegistry
     * @param FrontendToolProviderInterface $frontendToolProvider
     * @param ConversationManager $conversationManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly GeminiClientInterface         $geminiClient,
        private readonly ToolRegistryInterface         $toolRegistry,
        private readonly FrontendToolProviderInterface $frontendToolProvider,
        private readonly ConversationManager           $conversationManager,
        private readonly LoggerInterface               $logger
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function execute(
        string $userPrompt,
        string $systemInstruction,
        array  $mergedTools,
        ?int   $storeId = null
    ): array
    {
        $iteration = 0;
        $accumulatedFrontendCalls = [];
        $accumulatedProducts = [];
        $accumulatedReplies = [];
        $accumulatedSummaries = [];
        $executedBackendCalls = [];
        $lastTextMessage = '';
        $nativeTurns = [];

        $contents = $this->conversationManager->buildNativeContents($userPrompt);

        while ($iteration < self::MAX_ITERATIONS) {
            $iteration++;

            try {
                $result = $this->geminiClient->generateContent(
                    $systemInstruction,
                    '',
                    $storeId,
                    [
                        'module' => 'Bgomesweb_AiAssistant',
                        'feature' => 'FunctionCallingLoop',
                        'tools' => $mergedTools,
                        'contents' => $contents,
                    ]
                );
            } catch (\Throwable $e) {
                $this->logger->error('AiAssistant: Tool execution loop error: ' . $e->getMessage(), ['exception' => $e]);
                $fallbackMessage = $lastTextMessage !== ''
                    ? $lastTextMessage
                    : (string)__('Sorry, an error occurred while processing your request. Please try again.');
                return $this->buildResult(
                    $fallbackMessage,
                    $accumulatedFrontendCalls,
                    $accumulatedProducts,
                    $accumulatedReplies,
                    $accumulatedSummaries,
                    $iteration,
                    $nativeTurns
                );
            }

            if (trim($result->getText()) !== '') {
                $lastTextMessage = $result->getText();
            }

            $functionCall = $result->getData('function_call');

            if ($functionCall === null) {
                $rawParts = $result->getData('parts');
                if (!empty($rawParts) && is_array($rawParts)) {
                    $nativeTurns[] = [
                        'role' => 'model',
                        'parts' => $rawParts,
                    ];
                }

                return $this->buildResult(
                    $result->getText() ?: $lastTextMessage,
                    $accumulatedFrontendCalls,
                    $accumulatedProducts,
                    $accumulatedReplies,
                    $accumulatedSummaries,
                    $iteration,
                    $nativeTurns
                );
            }

            $toolName = $functionCall['name'] ?? '';
            $toolArgs = $functionCall['args'] ?? [];

            $rawParts = $result->getData('parts');
            $modelParts = (!empty($rawParts) && is_array($rawParts)) ? $rawParts : [
                [
                    'functionCall' => [
                        'name' => $toolName,
                        'args' => $toolArgs,
                    ]
                ]
            ];

            $contents[] = [
                'role' => 'model',
                'parts' => $modelParts,
            ];
            $nativeTurns[] = [
                'role' => 'model',
                'parts' => $modelParts,
            ];

            if ($this->frontendToolProvider->isFrontendTool($toolName)) {
                $accumulatedFrontendCalls[] = ['name' => $toolName, 'args' => $toolArgs];

                return $this->buildResult(
                    $result->getText() ?: $lastTextMessage,
                    $accumulatedFrontendCalls,
                    $accumulatedProducts,
                    $accumulatedReplies,
                    $accumulatedSummaries,
                    $iteration,
                    $nativeTurns
                );
            }

            if ($this->toolRegistry->hasBackendTool($toolName)) {
                $callKey = $toolName . ':' . md5(serialize($toolArgs));
                if (in_array($callKey, $executedBackendCalls, true)) {
                    $this->logger->warning(sprintf("ToolExecutionLoop: Prevented duplicate execution for backend tool '%s' in step %d.", $toolName, $iteration));
                    break;
                }
                $executedBackendCalls[] = $callKey;

                $toolResult = $this->toolRegistry->execute($toolName, $toolArgs);

                if ($toolResult->isSuccess()) {
                    $resultData = $toolResult->getResult();
                    if (is_array($resultData) && !empty($resultData['products'])) {
                        $accumulatedProducts[] = [
                            'query' => $toolArgs['query'] ?? '',
                            'products' => $resultData['products'],
                        ];
                    }
                    if (is_array($resultData) && !empty($resultData['_replies'])) {
                        $accumulatedReplies = $resultData['_replies'];
                    }
                    if (is_array($resultData) && !empty($resultData['_text_message'])) {
                        $lastTextMessage = (string)$resultData['_text_message'];
                    }
                    if (is_array($resultData) && !empty($resultData['_product_summaries'])) {
                        $accumulatedSummaries = array_merge($accumulatedSummaries, $resultData['_product_summaries']);
                    }
                }

                if ($this->toolRegistry->isTerminalTool($toolName)) {
                    return $this->buildResult(
                        $result->getText() ?: $lastTextMessage,
                        $accumulatedFrontendCalls,
                        $accumulatedProducts,
                        $accumulatedReplies,
                        $accumulatedSummaries,
                        $iteration,
                        $nativeTurns
                    );
                }

                $part = [
                    'functionResponse' => [
                        'name' => $toolName,
                        'response' => [
                            'name' => $toolName,
                            'content' => $toolResult->toArray(),
                        ]
                    ]
                ];

                $contents[] = [
                    'role' => 'user',
                    'parts' => [$part]
                ];
                $nativeTurns[] = $contents[array_key_last($contents)];

                continue;
            }

            $accumulatedFrontendCalls[] = ['name' => $toolName, 'args' => $toolArgs];

            return $this->buildResult(
                $result->getText() ?: $lastTextMessage,
                $accumulatedFrontendCalls,
                $accumulatedProducts,
                $accumulatedReplies,
                $accumulatedSummaries,
                $iteration,
                $nativeTurns
            );
        }

        return $this->buildResult(
            $lastTextMessage,
            $accumulatedFrontendCalls,
            $accumulatedProducts,
            $accumulatedReplies,
            $accumulatedSummaries,
            $iteration,
            $nativeTurns
        );
    }

    /**
     * @param string $message
     * @param array $frontendCalls
     * @param array $products
     * @param array $replies
     * @param array $summaries
     * @param int $iterations
     * @param array $nativeTurns
     * @return array
     */
    private function buildResult(
        string $message,
        array  $frontendCalls,
        array  $products,
        array  $replies,
        array  $summaries,
        int    $iterations,
        array  $nativeTurns = []
    ): array
    {
        return [
            'message' => $message,
            'tool_calls' => $frontendCalls,
            'products' => $products,
            'suggested_replies' => $replies,
            'product_summaries' => $summaries,
            'iterations' => $iterations,
            'native_turns' => $nativeTurns,
        ];
    }
}
