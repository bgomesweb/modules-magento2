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

namespace Bgomesweb\AiAssistant\Model;

use Bgomesweb\AiAssistant\Model\Config\Source\Theme;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Config implements ArgumentInterface
{
    private const string XML_PATH_ENABLED = 'bgomesweb_aiassistant/general/enabled';
    private const string XML_PATH_THEME = 'bgomesweb_aiassistant/general/theme';
    private const string XML_PATH_GENERATION_PROMPT = 'bgomesweb_aiassistant/general/generation_prompt';
    private const string XML_PATH_DISPLAY_PAGES = 'bgomesweb_aiassistant/general/display_pages';
    private const string XML_PATH_TOPICS_LIST = 'bgomesweb_aiassistant/topics/list';

    private readonly ScopeConfigInterface $scopeConfig;
    private readonly RequestInterface $request;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestInterface $request
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RequestInterface     $request
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getTheme(?int $storeId = null): string
    {
        $theme = (string)$this->scopeConfig->getValue(
            self::XML_PATH_THEME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $theme ?: Theme::THEME_BLUE;
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getGenerationPrompt(?int $storeId = null): string
    {
        return trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_GENERATION_PROMPT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));
    }

    /**
     * @param int|null $storeId
     * @return string[]
     */
    public function getDisplayPages(?int $storeId = null): array
    {
        $value = trim((string)$this->scopeConfig->getValue(
            self::XML_PATH_DISPLAY_PAGES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ));

        if ($value === '') {
            return ['all'];
        }

        return explode(',', $value);
    }

    /**
     * @return bool
     */
    public function isWidgetActive(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $allowedPages = $this->getDisplayPages();
        if (in_array('all', $allowedPages, true)) {
            return true;
        }

        $currentAction = $this->request->getFullActionName();
        return in_array($currentAction, $allowedPages, true);
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getTopics(?int $storeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_TOPICS_LIST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (!$value) {
            return [];
        }

        $topics = json_decode($value, true);
        if (is_array($topics)) {
            return $topics;
        }

        return [];
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getActiveTopics(?int $storeId = null): array
    {
        $topics = $this->getTopics($storeId);
        $activeTopics = [];

        if (empty($topics)) {
            return $activeTopics;
        }

        foreach ($topics as $topic) {
            $key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $topic['topic_name'] ?? ''));
            if ($key !== '') {
                $activeTopics[$key] = $topic;
            }
        }

        return $activeTopics;
    }

    /**
     * @param string $topicKey
     * @param int|null $storeId
     * @return string
     */
    public function getTopicBlockIdentifier(string $topicKey, ?int $storeId = null): string
    {
        $activeTopics = $this->getActiveTopics($storeId);

        if (isset($activeTopics[$topicKey]) && !empty($activeTopics[$topicKey]['cms_block'])) {
            return (string)$activeTopics[$topicKey]['cms_block'];
        }

        return '';
    }
}
