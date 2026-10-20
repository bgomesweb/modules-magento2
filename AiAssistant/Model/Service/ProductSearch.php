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

use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Gallery\ReadHandler as GalleryReadHandler;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Magento\Checkout\Helper\Cart as CartHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as FulltextCollectionFactory;

class ProductSearch
{
    private const int MAX_FETCH = 24;
    private const int MAX_DESCRIPTION_LENGTH = 400;
    private const int MAX_ATTRIBUTE_LENGTH = 180;

    private const array EXCLUDED_ATTRIBUTES = [
        'category_ids',
        'image',
        'small_image',
        'thumbnail',
        'gallery',
        'media_gallery',
        'url_key',
        'url_path',
        'bcaspecs23',
        'tabs'
    ];

    private const array SYNONYMS = [
        'celular' => ['smartphone', 'telefone'],
        'smartphone' => ['celular', 'telefone'],
        'telefone' => ['celular', 'smartphone'],
        'notebook' => ['laptop'],
        'laptop' => ['notebook'],
        'computador' => ['desktop', 'pc'],
        'desktop' => ['computador', 'pc'],
        'pc' => ['desktop', 'computador'],
        'teclado' => ['keyboard'],
        'keyboard' => ['teclado'],
        'monitor' => ['display', 'tela'],
        'fone' => ['headset', 'earbuds', 'fone de ouvido'],
        'headset' => ['fone'],
        'impressora' => ['printer'],
        'gamer' => ['gaming', 'game', 'gamer'],
        'gaming' => ['gamer', 'game'],
        'game' => ['gamer', 'gaming'],
    ];

    private const array STOP_WORDS = [
        'quero', 'preciso', 'procuro', 'gostaria', 'de', 'um', 'uma', 'uns', 'umas',
        'para', 'com', 'que', 'tenha', 'tem', 'até', 'ate', 'por', 'favor', 'também',
        'tambem', 'mais', 'menos', 'barato', 'barata', 'baratos', 'baratas', 'oferta',
        'ofertas', 'ofertа', 'estudo', 'estudos', 'bom', 'boa',
        'melhor', 'algum', 'alguma', 'alguns', 'algumas', 'me', 'mostre', 'mostra',
    ];

    private const array BASE_ATTRIBUTES = [
        'name', 'price', 'special_price', 'special_from_date', 'special_to_date',
        'small_image', 'thumbnail', 'url_key', 'sku', 'short_description', 'description',
        'manufacturer', 'modelo', 'part_number',
    ];

    private readonly ProductCollectionFactory $productCollectionFactory;
    private readonly FulltextCollectionFactory $fulltextCollectionFactory;
    private readonly StoreManagerInterface $storeManager;
    private readonly ImageHelper $imageHelper;
    private readonly GalleryReadHandler $galleryReadHandler;
    private readonly CartHelper $cartHelper;
    private readonly PriceCurrencyInterface $priceCurrency;
    private readonly LoggerInterface $logger;
    private readonly StockHelper $stockHelper;
    private readonly EavConfig $eavConfig;

    /**
     * @param ProductCollectionFactory $productCollectionFactory
     * @param FulltextCollectionFactory $fulltextCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param ImageHelper $imageHelper
     * @param GalleryReadHandler $galleryReadHandler
     * @param CartHelper $cartHelper
     * @param PriceCurrencyInterface $priceCurrency
     * @param LoggerInterface $logger
     * @param StockHelper $stockHelper
     * @param EavConfig $eavConfig
     */
    public function __construct(
        ProductCollectionFactory  $productCollectionFactory,
        FulltextCollectionFactory $fulltextCollectionFactory,
        StoreManagerInterface     $storeManager,
        ImageHelper               $imageHelper,
        GalleryReadHandler        $galleryReadHandler,
        CartHelper                $cartHelper,
        PriceCurrencyInterface    $priceCurrency,
        LoggerInterface           $logger,
        StockHelper               $stockHelper,
        EavConfig                 $eavConfig
    )
    {
        $this->productCollectionFactory = $productCollectionFactory;
        $this->fulltextCollectionFactory = $fulltextCollectionFactory;
        $this->storeManager = $storeManager;
        $this->imageHelper = $imageHelper;
        $this->galleryReadHandler = $galleryReadHandler;
        $this->cartHelper = $cartHelper;
        $this->priceCurrency = $priceCurrency;
        $this->logger = $logger;
        $this->stockHelper = $stockHelper;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @throws NoSuchEntityException
     */
    public function search(
        string $queryTerms,
        ?float $maxPrice = null,
        int    $limit = 3,
        ?float $minPrice = null,
        string $sortBy = 'relevance',
        bool   $onlyOnSale = false
    ): array
    {
        $limit = max(1, min($limit, 6));
        $terms = $this->expandTerms($queryTerms);
        return $this->executeQuery($queryTerms, $terms, $maxPrice, $minPrice, $limit, $sortBy, $onlyOnSale);
    }

    /**
     * @param string[] $skus
     * @throws NoSuchEntityException
     */
    public function findBySkus(array $skus, ?int $storeId = null): array
    {
        $skus = array_values(array_unique(array_filter(array_map(
            static fn($sku): string => trim((string)$sku),
            $skus
        ))));

        if (!$skus) {
            return [];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId($storeId ?? (int)$this->storeManager->getStore()->getId());
        $collection->addAttributeToSelect(array_merge(self::BASE_ATTRIBUTES, array_keys($this->getSelectedFrontendAttributes())))
            ->addFinalPrice()
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->addAttributeToFilter('sku', ['in' => $skus])
            ->setVisibility([
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH,
            ]);

        $this->stockHelper->addInStockFilterToCollection($collection);
        $collection->getSelect()->order('e.entity_id DESC');

        $products = [];
        foreach ($collection as $product) {
            try {
                if (!$product->isSalable()) {
                    continue;
                }

                $products[] = $this->mapProduct($product, true);
            } catch (\Throwable $e) {
                $this->logger->warning('AiAssistant: Product skipped during findBySkus.', [
                    'sku' => $product->getSku(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $orderBySku = array_flip($skus);
        usort($products, static function (array $a, array $b) use ($orderBySku): int {
            return ($orderBySku[$a['sku']] ?? PHP_INT_MAX) <=> ($orderBySku[$b['sku']] ?? PHP_INT_MAX);
        });

        return $products;
    }

    /**
     * @throws NoSuchEntityException
     */
    private function executeQuery(
        string $query,
        array  $terms,
        ?float $maxPrice,
        ?float $minPrice,
        int    $limit,
        string $sortBy = 'relevance',
        bool   $onlyOnSale = false
    ): array
    {
        $fetchSize = min(self::MAX_FETCH, max(12, $limit * 4));
        if ($onlyOnSale || $maxPrice !== null || $minPrice !== null) {
            $fetchSize = min(self::MAX_FETCH * 4, max($fetchSize, $limit * 16));
        }

        $originalQuery = trim($query);
        $queryText = !empty($terms) ? implode(' ', $terms) : $originalQuery;
        $storeId = (int)$this->storeManager->getStore()->getId();

        $collection = $originalQuery !== ''
            ? $this->fulltextCollectionFactory->create()
            : $this->productCollectionFactory->create();

        $collection->setStoreId($storeId);

        if ($originalQuery !== '') {
            if (method_exists($collection, 'addSearchFilter')) {
                $collection->addSearchFilter($queryText);
            } else {
                $this->logger->warning('AiAssistant: Fulltext collection does not support addSearchFilter.');
            }
        }

        $collection->addAttributeToSelect(
            array_merge(self::BASE_ATTRIBUTES, array_keys($this->getSelectedFrontendAttributes()))
        )
            ->addFinalPrice()
            ->addAttributeToFilter('status', Status::STATUS_ENABLED)
            ->setVisibility([
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH,
            ]);

        $this->stockHelper->addInStockFilterToCollection($collection);

        if ($onlyOnSale) {
            $collection->getSelect()->where('price_index.final_price < price_index.price');
        }

        if ($maxPrice !== null && $maxPrice > 0) {
            $collection->addFieldToFilter('price_index.min_price', ['lteq' => $maxPrice]);
        }

        if ($minPrice !== null && $minPrice >= 0) {
            $collection->addFieldToFilter('price_index.max_price', ['gteq' => $minPrice]);
        }

        // Clear default orders to prevent OpenSearch parsing exceptions (e.g. from invalid order enums)
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::ORDER);
        if ($originalQuery === '') {
            $collection->setOrder('entity_id', 'desc');
        } else {
            $collection->setOrder('relevance', 'desc');
        }
        $collection->setPageSize($fetchSize);

        $candidates = [];
        try {
            foreach ($collection as $position => $product) {
                try {
                    if (!$product->isSalable()) {
                        continue;
                    }
                    $attributes = $this->extractTechnicalAttributes($product);
                    $score = $this->scoreProduct($product, $terms, $onlyOnSale, $attributes);
                    $score += max(0, (count($collection) - $position) * 2);
                    $candidates[$product->getSku()] = [
                        'product' => $product,
                        'attributes' => $attributes,
                        'score' => $score,
                        'price' => $this->getCatalogPriceRange($product)['min'],
                        'id' => (int)$product->getId(),
                    ];
                } catch (\Throwable $e) {
                    $this->logger->warning('AiAssistant: Product skipped during candidate evaluation.', [
                        'sku' => $product->getSku(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('AiAssistant: Collection load failed.', ['error' => $e->getMessage()]);
        }

        usort($candidates, static function (array $a, array $b) use ($sortBy): int {
            if ($sortBy === 'price_asc') {
                return $a['price'] <=> $b['price'];
            }
            if ($sortBy === 'price_desc') {
                return $b['price'] <=> $a['price'];
            }
            if ($b['score'] !== $a['score']) {
                return $b['score'] <=> $a['score'];
            }
            return $b['id'] <=> $a['id'];
        });

        $productsData = [];
        foreach (array_slice($candidates, 0, $limit) as $candidate) {
            $productsData[] = $this->mapProduct($candidate['product'], true, $candidate['attributes']);
        }

        return $productsData;
    }

    /**
     * @param string $query
     * @return array
     */
    private function expandTerms(string $query): array
    {
        $query = mb_strtolower(trim($query));
        if ($query === '') {
            return [];
        }

        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terms = [];

        foreach ($tokens as $token) {
            if (mb_strlen($token) < 2 || in_array($token, self::STOP_WORDS, true)) {
                continue;
            }

            $terms[] = $token;

            if (isset(self::SYNONYMS[$token])) {
                foreach (self::SYNONYMS[$token] as $synonym) {
                    $terms[] = $synonym;
                }
            }
        }

        $terms = array_values(array_unique($terms));
        return array_slice($terms, 0, 12);
    }

    /**
     * @param Product $product
     * @param array $terms
     * @param bool $onlyOnSale
     * @param array|null $attributes
     * @return int
     */
    private function scoreProduct(
        Product $product,
        array   $terms,
        bool    $onlyOnSale = false,
        ?array  $attributes = null
    ): int
    {
        $name = mb_strtolower($product->getName());
        $sku = mb_strtolower($product->getSku());
        $shortDesc = mb_strtolower(strip_tags((string)$product->getData('short_description')));
        $attributes ??= $this->extractTechnicalAttributes($product);
        $score = 0;

        foreach ($terms as $index => $term) {
            if ($term === '') {
                continue;
            }

            if ($this->termMatches($name, $term)) {
                $score += ($index === 0 ? 100 : 60);
            }

            if ($this->termMatches($sku, $term)) {
                $score += 80;
            }

            foreach ($attributes as $attrValue) {
                if ($this->termMatches(mb_strtolower((string)$attrValue), $term)) {
                    $score += 40;
                }
            }

            if ($shortDesc !== '' && $this->termMatches($shortDesc, $term)) {
                $score += 25;
            }
        }

        $priceRange = $this->getCatalogPriceRange($product);
        $minPrice = $priceRange['min'];
        $regularPrice = $product->getPrice();

        if ($regularPrice > 0 && $minPrice < $regularPrice) {
            $discountPercent = (($regularPrice - $minPrice) / $regularPrice) * 100;
            if ($onlyOnSale) {
                $score += (int)($discountPercent * 2);
            } else {
                $score += (int)($discountPercent * 0.5);
            }
        }

        if ($minPrice > 0) {
            $score += 5;
        }

        return $score;
    }

    /**
     * @param Product $product
     * @param bool $includeGallery
     * @param array|null $technicalAttributes
     * @return array
     */
    private function mapProduct(Product $product, bool $includeGallery = false, ?array $technicalAttributes = null): array
    {
        $priceRange = $this->getCatalogPriceRange($product);
        $formattedPrice = $this->formatPrice($priceRange['min']);
        $isPriceRange = $priceRange['max'] > $priceRange['min'];

        $typeId = (string)$product->getTypeId();
        $hasOptions = (bool)$product->getHasOptions() || (bool)$product->getRequiredOptions();
        $isConfigurable = $typeId === Configurable::TYPE_CODE
            || $typeId === Grouped::TYPE_CODE
            || $typeId === Type::TYPE_BUNDLE;

        $imageUrl = '';
        if ($includeGallery) {
            try {
                $imageUrl = $this->imageHelper->init($product, 'product_base_image')->getUrl();
            } catch (\Throwable $e) {
                $this->logger->debug('AiAssistant: Could not resolve product base image', [
                    'sku' => $product->getSku(),
                    'exception' => $e
                ]);
            }
        }

        return [
            'product_id' => $product->getId(),
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'type_id' => $typeId,
            'has_options' => $hasOptions,
            'is_configurable' => $isConfigurable,
            'price' => $priceRange['min'],
            'price_max' => $priceRange['max'],
            'formatted_price' => $isPriceRange
                ? (string)__('From %1', $formattedPrice)
                : $formattedPrice,
            'formatted_price_max' => $isPriceRange
                ? $this->formatPrice($priceRange['max'])
                : null,
            'is_price_range' => $isPriceRange,
            'url' => $product->getProductUrl(),
            'image_url' => $imageUrl,
            'gallery_images' => $includeGallery ? $this->getGalleryImages($product) : [],
            'add_to_cart_url' => $this->cartHelper->getAddUrl($product),
            'attributes' => $technicalAttributes ?? $this->extractTechnicalAttributes($product),
            'description' => $this->extractDescription($product),
        ];
    }

    /**
     * @param Product $product
     * @return array
     */
    private function getGalleryImages(Product $product): array
    {
        try {
            $this->galleryReadHandler->execute($product);
            $mediaGallery = $product->getMediaGalleryImages();

            if ($mediaGallery === null) {
                return [];
            }

            $urls = [];
            foreach ($mediaGallery as $image) {
                $url = (string)$image->getUrl();
                if ($url !== '' && !in_array($url, $urls, true)) {
                    $urls[] = $url;
                }
            }

            return $urls;
        } catch (\Throwable $e) {
            $this->logger->debug('AiAssistant: Could not resolve product gallery images', [
                'sku' => $product->getSku(),
                'exception' => $e
            ]);
            return [];
        }
    }

    /**
     * @param Product $product
     * @return string
     */
    private function extractDescription(Product $product): string
    {
        $text = (string)$product->getData('short_description');
        if (trim($text) === '') {
            $text = (string)$product->getData('description');
        }

        $text = trim(strip_tags($text));
        return mb_substr($text, 0, self::MAX_DESCRIPTION_LENGTH);
    }

    private function extractTechnicalAttributes(Product $product): array
    {
        $result = [];
        $visibleAttributes = $this->getSelectedFrontendAttributes();

        foreach ($visibleAttributes as $code => $label) {
            $value = null;
            try {
                $attrText = $product->getAttributeText($code);
                if (is_string($attrText) && trim($attrText) !== '') {
                    $value = $attrText;
                } elseif (is_array($attrText) && !empty($attrText)) {
                    $value = implode(', ', array_filter($attrText));
                }
            } catch (\Throwable $e) {
                $this->logger->debug('AiAssistant: could not read product attribute.', [
                    'attribute' => $code,
                    'exception' => $e,
                ]);
            }

            if ($value === null || trim((string)$value) === '') {
                $raw = $product->getData($code);
                if ($raw !== null && !is_array($raw)) {
                    $value = (string)$raw;
                }
            }

            if ($value === null || is_array($value)) {
                continue;
            }

            $text = trim((string)$value);
            if ($text === '' || $text === '0') {
                continue;
            }

            $result[$label] = mb_substr(strip_tags($text), 0, self::MAX_ATTRIBUTE_LENGTH);
        }

        $desc = (string)$product->getData('short_description');
        if (trim($desc) === '') {
            $desc = (string)$product->getData('description');
        }

        if (trim($desc) !== '') {
            $rawLines = explode("\n", str_replace(["\r\n", "\r", "<br>", "<br/>", "<br />"], "\n", $desc));
            foreach ($rawLines as $line) {
                $line = trim(strip_tags($line));
                if ($line === '' || !str_contains($line, ':')) {
                    continue;
                }

                $parts = explode(':', $line, 2);
                $label = trim($parts[0]);
                $val = trim($parts[1]);

                if ($label !== '' && $val !== '' && mb_strlen($label) <= 40 && mb_strlen($val) <= 200) {
                    if (!isset($result[$label])) {
                        $result[$label] = $val;
                    }
                }
            }
        }
        ksort($result, SORT_LOCALE_STRING);
        return $result;
    }

    /**
     * @param float $amount
     * @return string
     */
    private function formatPrice(float $amount): string
    {
        return $this->priceCurrency->format($amount);
    }

    /**
     * @param Product $product
     * @return array|float[]
     */
    private function getCatalogPriceRange(Product $product): array
    {
        $finalPrice = $product->getPriceInfo()->getPrice('final_price');

        if ($product->getTypeId() === BundleType::TYPE_CODE) {
            return [
                'min' => (float)$finalPrice->getMinimalPrice()->getValue(),
                'max' => (float)$finalPrice->getMaximalPrice()->getValue(),
            ];
        }

        $price = $finalPrice->getAmount()->getValue();
        return [
            'min' => $price,
            'max' => $price,
        ];
    }

    /**
     * @return array
     */
    private function getVisibleFrontendAttributes(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        try {
            $entityType = $this->eavConfig->getEntityType('catalog_product');
            $attributes = $this->eavConfig->getAttributes($entityType->getId());
        } catch (\Throwable $e) {
            $this->logger->error('AiAssistant: could not load EAV attributes.', ['exception' => $e]);
            return [];
        }

        $cache = [];
        foreach ($attributes as $attribute) {
            $code = $attribute->getAttributeCode();
            if (in_array($code, self::EXCLUDED_ATTRIBUTES, true)) {
                continue;
            }
            if (
                (int)$attribute->getIsVisibleOnFront() === 1
                && $attribute->getFrontendLabel()
            ) {
                $cache[$code] = $attribute->getFrontendLabel();
            }
        }

        asort($cache, SORT_LOCALE_STRING);
        return $cache;
    }

    private function getSelectedFrontendAttributes(): array
    {
        return $this->getVisibleFrontendAttributes();
    }

    private function termMatches(string $text, string $term): bool
    {
        $pattern = '#(^|[^\p{L}\p{N}])' . preg_quote($term, '#') . '([^\p{L}\p{N}]|$)#u';
        return (bool)preg_match($pattern, $text);
    }
}
