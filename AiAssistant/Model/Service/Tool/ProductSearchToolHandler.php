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
use Bgomesweb\AiAssistant\Model\Service\ProductSearch;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductSearchToolHandler implements ToolHandlerInterface
{
    private const DEFAULT_LIMIT = 3;
    private const MAX_LIMIT = 6;

    private const KEY_FOUND = 'found';
    private const KEY_PRODUCTS = 'products';
    private const KEY_MESSAGE = 'message';
    private const KEY_SKU = 'sku';
    private const KEY_NAME = 'name';
    private const KEY_PRICE = 'price';
    private const KEY_ATTRIBUTES = 'attributes';
    private const KEY_DESCRIPTION = 'description';
    private const KEY_IS_CONFIGURABLE = 'is_configurable';
    private const KEY_URL = 'url';
    private const KEY_FORMATTED_PRICE = 'formatted_price';

    private const PROMO_KEYWORDS = [
        'promocao', 'promoção', 'promocoes', 'promoções',
        'oferta', 'ofertas', 'desconto', 'descontos', 'sale',
    ];

    public function __construct(
        private readonly ProductSearch $productSearch
    ) {
    }

    /**
     * @throws NoSuchEntityException
     */
    public function execute(array $args): array
    {
        $query = trim((string)($args['query'] ?? ''));
        $onlyOnSale = !empty($args['only_on_sale']);

        $cleanQuery = mb_strtolower($query);

        if (in_array($cleanQuery, self::PROMO_KEYWORDS, true)) {
            $onlyOnSale = true;
            $query = '';
        } else {
            foreach (self::PROMO_KEYWORDS as $keyword) {
                if (str_contains($cleanQuery, $keyword)) {
                    $onlyOnSale = true;
                    break;
                }
            }
        }

        $maxPrice = isset($args['max_price']) && is_numeric($args['max_price'])
            ? (float)$args['max_price']
            : null;
        $minPrice = isset($args['min_price']) && is_numeric($args['min_price'])
            ? (float)$args['min_price']
            : null;
        $limit = isset($args['limit']) && is_numeric($args['limit'])
            ? max(1, min(self::MAX_LIMIT, (int)$args['limit']))
            : self::DEFAULT_LIMIT;
        $sortBy = trim((string)($args['sort_by'] ?? 'relevance'));

        if ($query === '' && !$onlyOnSale) {
            return [
                self::KEY_FOUND => 0,
                self::KEY_PRODUCTS => [],
                self::KEY_MESSAGE => __('No query provided for product search.'),
            ];
        }

        $products = $this->productSearch->search($query, $maxPrice, $limit, $minPrice, $sortBy, $onlyOnSale);

        if (empty($products)) {
            return [
                self::KEY_FOUND => 0,
                self::KEY_PRODUCTS => [],
                self::KEY_MESSAGE => __('No products found for "%1"%2.', $query, $maxPrice !== null ? ' up to ' . number_format($maxPrice, 2, '.', ',') : ''),
            ];
        }

        $lightProducts = array_map(static fn (array $p): array => [
            self::KEY_SKU => $p[self::KEY_SKU] ?? '',
            self::KEY_NAME => $p[self::KEY_NAME] ?? '',
            self::KEY_PRICE => $p[self::KEY_FORMATTED_PRICE] ?? ($p[self::KEY_PRICE] ?? ''),
            self::KEY_ATTRIBUTES => $p[self::KEY_ATTRIBUTES] ?? [],
            self::KEY_DESCRIPTION => $p[self::KEY_DESCRIPTION] ?? '',
            self::KEY_IS_CONFIGURABLE => !empty($p[self::KEY_IS_CONFIGURABLE]),
            self::KEY_URL => $p[self::KEY_URL] ?? '',
        ], $products);

        return [
            self::KEY_FOUND => count($products),
            self::KEY_PRODUCTS => $lightProducts,
            self::KEY_MESSAGE => __('Found %1 product(s) for "%2".', count($products), $query !== '' ? $query : 'promotions'),
        ];
    }
}
