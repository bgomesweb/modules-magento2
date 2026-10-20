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

namespace Bgomesweb\AiAssistant\Model\Service;

use Bgomesweb\AiAssistant\Model\Config as AiConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Bgomesweb\AiAssistant\Api\ToolExecutionLoopInterface;
use Bgomesweb\AiAssistant\Api\ToolRegistryInterface;

class ChatProcessor
{
    private const int MAX_SUMMARY_LENGTH = 500;
    private const string SUMMARY_LABEL = 'Summary';

    private readonly ProductSearch $productSearch;
    private readonly AiConfig $aiConfig;
    private readonly ConversationManager $conversationManager;
    private readonly TopicContextService $topicContextService;
    private readonly ToolRegistryInterface $toolRegistry;
    private readonly ToolExecutionLoopInterface $toolExecutionLoop;
    private readonly TextSanitizer $textSanitizer;

    /**
     * @param ProductSearch $productSearch
     * @param AiConfig $aiConfig
     * @param ConversationManager $conversationManager
     * @param TopicContextService $topicContextService
     * @param ToolRegistryInterface $toolRegistry
     * @param ToolExecutionLoopInterface $toolExecutionLoop
     * @param TextSanitizer $textSanitizer
     */
    public function __construct(
        ProductSearch              $productSearch,
        AiConfig                   $aiConfig,
        ConversationManager        $conversationManager,
        TopicContextService        $topicContextService,
        ToolRegistryInterface      $toolRegistry,
        ToolExecutionLoopInterface $toolExecutionLoop,
        TextSanitizer              $textSanitizer
    )
    {
        $this->productSearch = $productSearch;
        $this->aiConfig = $aiConfig;
        $this->conversationManager = $conversationManager;
        $this->topicContextService = $topicContextService;
        $this->toolRegistry = $toolRegistry;
        $this->toolExecutionLoop = $toolExecutionLoop;
        $this->textSanitizer = $textSanitizer;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function processMessage(
        string  $userPrompt,
        ?int    $storeId = null,
        ?string $topicContext = null,
        array   $frontendTools = []
    ): array
    {
        $userPrompt = trim($userPrompt);
        $systemInstruction = $this->buildSystemInstruction($storeId, $topicContext);

        $mergedTools = array_merge($this->toolRegistry->getBackendTools(), $frontendTools);

        try {
            $loopResult = $this->toolExecutionLoop->execute(
                $userPrompt,
                $systemInstruction,
                $mergedTools,
                $storeId
            );
        } catch (\Exception $e) {
            return $this->buildErrorResponse($e->getMessage());
        }

        $loopGroups = is_array($loopResult['products'] ?? null) ? $loopResult['products'] : [];
        $suggestedReplies = is_array($loopResult['suggested_replies'] ?? null) ? $loopResult['suggested_replies'] : [];
        $toolCalls = is_array($loopResult['tool_calls'] ?? null) ? $loopResult['tool_calls'] : [];

        if (!empty($loopGroups)) {
            return $this->buildProductResponse($loopResult, $loopGroups, $suggestedReplies, $toolCalls, $storeId);
        }

        return $this->buildTextResponse($loopResult, $toolCalls, $suggestedReplies);
    }

    /**
     * @throws NoSuchEntityException
     */
    private function buildSystemInstruction(?int $storeId, ?string $topicContext): string
    {
        $systemInstruction = $this->aiConfig->getGenerationPrompt($storeId);
        if ($systemInstruction === '') {
            $systemInstruction = (string)__('You are an intelligent, polite Virtual Shopping Assistant specialized in e-commerce.');
        }

        if (!$topicContext) {
            $topicContext = $this->conversationManager->getActiveTopic();
        }

        if ($topicContext) {
            $blockContent = $this->topicContextService->getBlockContent($topicContext, $storeId);
            if ($blockContent !== '') {
                $systemInstruction .= "\n\nBASE DE CONHECIMENTO DA LOJA (Use isso para responder as dúvidas do cliente baseando-se estritamente nestas regras):\n" . $blockContent;
            }
        }

        return $systemInstruction;
    }

    /**
     * @param array $loopResult
     * @param array $toolCalls
     * @param array $suggestedReplies
     * @return array
     */
    private function buildTextResponse(
        array $loopResult,
        array $toolCalls,
        array $suggestedReplies
    ): array
    {
        $responseText = $this->extractResponseMessage($loopResult);

        return $this->buildResponseArray($responseText, $toolCalls, $suggestedReplies, $loopResult);
    }

    /**
     * @param string $errorMessage
     * @return array
     */
    private function buildErrorResponse(string $errorMessage): array
    {
        return $this->buildResponseArray(
            (string)__('Sorry, an error occurred while processing your request. Please try again.'),
            [],
            [['label' => (string)__('Try again'), 'text' => (string)__('Try again')]],
            ['error' => $errorMessage]
        );
    }

    /**
     * @throws NoSuchEntityException
     */
    private function buildProductResponse(
        array $loopResult,
        array $loopGroups,
        array $suggestedReplies,
        array $toolCalls,
        ?int  $storeId
    ): array
    {
        $allSkus = $this->collectSkusFromGroups($loopGroups);
        $productSummaries = is_array($loopResult['product_summaries'] ?? null) ? $loopResult['product_summaries'] : [];
        $fullBySkus = $this->resolveFullProducts($allSkus, $productSummaries, $storeId);

        $enrichedGroups = $this->resolveGroupsFromLoop($loopGroups, $fullBySkus);
        $enrichedGroups = $this->filterValidGroups($enrichedGroups);

        if ($enrichedGroups) {
            $enrichedGroups = $this->enrichGroupsWithComparison($enrichedGroups);
        }

        $flatProducts = $this->flattenGroups($enrichedGroups);
        $responseText = $this->extractResponseMessage($loopResult);

        return $this->buildResponseArray($responseText, $toolCalls, $suggestedReplies, $loopResult, $flatProducts, $enrichedGroups);
    }

    /**
     * @param array $loopGroups
     * @return array
     */
    private function collectSkusFromGroups(array $loopGroups): array
    {
        $allSkus = [];
        foreach ($loopGroups as $group) {
            foreach ($group['products'] ?? [] as $p) {
                $sku = (string)($p['sku'] ?? '');
                if ($sku !== '') {
                    $allSkus[] = $sku;
                }
            }
        }

        return array_values(array_unique($allSkus));
    }

    /**
     * @param string[] $allSkus
     * @return array<string, array>
     * @throws NoSuchEntityException
     */
    private function resolveFullProducts(array $allSkus, array $productSummaries, ?int $storeId): array
    {
        $fullBySkus = [];
        if (!$allSkus) {
            return $fullBySkus;
        }

        $fullProducts = $this->productSearch->findBySkus($allSkus, $storeId);
        foreach ($fullProducts as $fp) {
            $sku = (string)($fp['sku'] ?? '');
            if (isset($productSummaries[$sku]) && trim((string)$productSummaries[$sku]) !== '') {
                $fp['comparison_summary'] = trim((string)$productSummaries[$sku]);
            }
            $fullBySkus[$sku] = $fp;
        }

        return $fullBySkus;
    }

    /**
     * @param array $loopGroups
     * @param array $fullBySkus
     * @return array
     */
    private function resolveGroupsFromLoop(array $loopGroups, array $fullBySkus): array
    {
        $enrichedGroups = [];
        foreach ($loopGroups as $group) {
            $resolved = [];
            foreach ($group['products'] ?? [] as $p) {
                $sku = (string)($p['sku'] ?? '');
                $full = $fullBySkus[$sku] ?? null;
                if ($full !== null) {
                    $resolved[] = $full;
                }
            }
            if ($resolved) {
                $enrichedGroups[] = [
                    'query' => (string)($group['query'] ?? ''),
                    'products' => $resolved,
                ];
            }
        }

        return $enrichedGroups;
    }

    /**
     * @param array $groups
     * @return array
     */
    private function filterValidGroups(array $groups): array
    {
        $valid = [];
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $products = $this->filterValidProducts($group['products'] ?? []);
            if (!$products) {
                continue;
            }

            $group['products'] = $products;
            $valid[] = $group;
        }

        return $valid;
    }

    /**
     * @param array $products
     * @return array
     */
    private function filterValidProducts(array $products): array
    {
        $valid = [];
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }

            $sku = trim((string)($product['sku'] ?? ''));
            $name = trim((string)($product['name'] ?? ''));
            if ($sku === '' || $name === '') {
                continue;
            }

            $product['image_url'] = trim((string)($product['image_url'] ?? ''));
            $product['url'] = trim((string)($product['url'] ?? ''));
            $product['add_to_cart_url'] = trim((string)($product['add_to_cart_url'] ?? ''));
            $valid[] = $product;
        }

        return $valid;
    }

    /**
     * @param array $groups
     * @return array
     */
    private function enrichGroupsWithComparison(array $groups): array
    {
        foreach ($groups as $groupIndex => $group) {
            $products = $group['products'] ?? [];
            if (!is_array($products) || empty($products)) {
                continue;
            }

            $masterLabels = $this->buildMasterLabels($products);
            $groups[$groupIndex]['products'] = $this->enrichProductsWithDifferences($products, $masterLabels);
        }

        return $groups;
    }

    /**
     * @param array $products
     * @return string[]
     */
    private function buildMasterLabels(array $products): array
    {
        $masterLabels = [self::SUMMARY_LABEL];
        foreach ($products as $product) {
            if (!is_array($product)) {
                continue;
            }
            $attributes = is_array($product['attributes'] ?? null) ? $product['attributes'] : [];
            foreach ($attributes as $label => $val) {
                $labelStr = (string)$label;
                if ($labelStr !== '' && !in_array($labelStr, $masterLabels, true)) {
                    $masterLabels[] = $labelStr;
                }
            }
        }

        return $masterLabels;
    }

    /**
     * @param array $products
     * @param array $masterLabels
     * @return array
     */
    private function enrichProductsWithDifferences(array $products, array $masterLabels): array
    {
        $summaryLabel = self::SUMMARY_LABEL;

        foreach ($products as $productIndex => $product) {
            if (!is_array($product)) {
                continue;
            }

            $summary = $this->buildProductSummary($product);
            $product['comparison_summary'] = $summary;

            $attributes = is_array($product['attributes'] ?? null) ? $product['attributes'] : [];

            $differences = [];
            foreach ($masterLabels as $label) {
                if ($label === $summaryLabel) {
                    $differences[] = [
                        'label' => __(self::SUMMARY_LABEL),
                        'text' => $summary !== '' ? $summary : '-',
                    ];
                } else {
                    $val = isset($attributes[$label]) ? trim((string)$attributes[$label]) : '';
                    $differences[] = [
                        'label' => $label,
                        'text' => ($val !== '' && $val !== '0') ? $val : '-',
                    ];
                }
            }

            $product['comparison_differences'] = $differences;
            $products[$productIndex] = $product;
        }

        return $products;
    }

    /**
     * @param array $product
     * @return string
     */
    private function buildProductSummary(array $product): string
    {
        $summary = trim((string)($product['comparison_summary'] ?? ''));
        if ($summary === '') {
            $formattedPrice = (string)($product['formatted_price'] ?? '');
            $name = (string)($product['name'] ?? '');
            $summary = $name . ($formattedPrice !== '' ? ' (' . $formattedPrice . ')' : '');
        }

        return mb_substr($summary, 0, self::MAX_SUMMARY_LENGTH);
    }

    /**
     * @param array $groups
     * @return array
     */
    private function flattenGroups(array $groups): array
    {
        $products = [];
        foreach ($groups as $group) {
            foreach (($group['products'] ?? []) as $product) {
                if (!is_array($product)) {
                    continue;
                }
                $product['product_group'] = (string)($group['query'] ?? '');
                $products[] = $product;
            }
        }

        return $products;
    }

    /**
     * @param array $loopResult
     * @return string
     */
    private function extractResponseMessage(array $loopResult): string
    {
        return $this->textSanitizer->extractSafeMessage(
            (string)($loopResult['message'] ?? '')
        );
    }

    /**
     * @param string $responseText
     * @param array $toolCalls
     * @param array $suggestedReplies
     * @param array $loopResult
     * @param array|null $products
     * @param array|null $groups
     * @return array
     */
    private function buildResponseArray(
        string $responseText,
        array  $toolCalls,
        array  $suggestedReplies,
        array  $loopResult,
        ?array $products = null,
        ?array $groups = null
    ): array
    {
        return [
            'message' => trim($responseText),
            'products' => $products ?? [],
            'comparison' => null,
            'groups' => $groups ?? [],
            'actions' => [],
            'tool_calls' => $toolCalls,
            'suggested_replies' => $suggestedReplies,
            'native_turns' => $loopResult['native_turns'] ?? [],
        ];
    }
}
