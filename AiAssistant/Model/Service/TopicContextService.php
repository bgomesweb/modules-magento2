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

use Magento\Cms\Block\Block as CmsBlock;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Bgomesweb\AiAssistant\Model\Config;
use Psr\Log\LoggerInterface;

class TopicContextService
{
    private readonly LayoutInterface $layout;
    private readonly StoreManagerInterface $storeManager;
    private readonly Config $config;
    private readonly LoggerInterface $logger;

    /**
     * @param LayoutInterface $layout
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        LayoutInterface       $layout,
        StoreManagerInterface $storeManager,
        Config                $config,
        LoggerInterface       $logger
    ) {
        $this->layout = $layout;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param string $topicKey
     * @param int|null $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBlockContent(string $topicKey, ?int $storeId = null): string
    {
        $resolvedStoreId = $storeId ?? (int)$this->storeManager->getStore()->getId();
        $identifiersStr = $this->config->getTopicBlockIdentifier($topicKey, $resolvedStoreId);

        if ($identifiersStr === '') {
            return '';
        }

        $identifiers = array_values(array_filter(array_map('trim', explode(',', $identifiersStr))));
        if (empty($identifiers)) {
            return '';
        }

        $contents = [];

        foreach ($identifiers as $identifier) {
            try {
                /** @var CmsBlock $block */
                $block = $this->layout->createBlock(CmsBlock::class);
                $rawHtml = (string)$block->setBlockId($identifier)->toHtml();

                if (trim($rawHtml) === '') {
                    continue;
                }

                $html = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', '', $rawHtml);
                $html = preg_replace('/<\/(p|div|h[1-6]|li|tr|blockquote)>\s*/i', "\n", $html);
                $html = preg_replace('/<br\s*\/?>\s*/i', "\n", $html);

                $plainText = strip_tags($html);
                $plainText = preg_replace('/[ \t]+/', ' ', $plainText);
                $plainText = preg_replace('/\n\s*\n+/', "\n\n", $plainText);

                $trimmed = trim($plainText);
                if ($trimmed !== '') {
                    $contents[] = $trimmed;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('AiAssistant: could not load topic CMS block.', [
                    'identifier' => $identifier,
                    'exception' => $e,
                ]);
            }
        }

        return implode("\n\n", $contents);
    }
}
