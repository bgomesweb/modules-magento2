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

use Bgomesweb\AiAssistant\Api\ToolHandlerInterface;

/**
 * Backend tool handler that captures AI product comparison summaries per column.
 *
 * The LLM calls compare_products({ comparisons: [ {"sku": "...", "summary": "..."}, ... ] })
 * after search_products to attach an AI-generated summary to each product column in the UI.
 */
class CompareProductsToolHandler implements ToolHandlerInterface
{
    private const int MAX_COMPARISONS = 6;

    private const string KEY_ACCEPTED = 'accepted';
    private const string KEY_PRODUCT_SUMMARIES = '_product_summaries';
    private const string KEY_MESSAGE = 'message';

    /**
     * @param array $args
     * @return array|int[]
     */
    public function execute(array $args): array
    {
        $raw = $args['comparisons'] ?? [];
        if (!is_array($raw)) {
            return [self::KEY_ACCEPTED => 0];
        }

        $accepted = 0;
        $normalised = [];
        foreach (array_slice($raw, 0, self::MAX_COMPARISONS) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $sku = trim((string)($item['sku'] ?? ''));
            $summary = trim((string)($item['summary'] ?? ''));
            if ($sku === '' || $summary === '') {
                continue;
            }
            $normalised[$sku] = $summary;
            $accepted++;
        }

        return [
            self::KEY_ACCEPTED => $accepted,
            self::KEY_PRODUCT_SUMMARIES => $normalised,
            self::KEY_MESSAGE => __('%1 comparison summary(ies) saved.', $accepted),
        ];
    }
}
