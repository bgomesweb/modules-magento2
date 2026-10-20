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

use Magento\Framework\Session\SessionManagerInterface;

class ConversationManager
{
    private const string ROLE_USER = 'user';
    private const string ROLE_ASSISTANT = 'assistant';
    private const string SESSION_KEY = 'bgomesweb_aiassistant_conversation';
    private const int MAX_MESSAGES = 12;
    private const int MAX_PRODUCTS = 12;
    private readonly SessionManagerInterface $session;
    private ?array $sessionCache = null;

    /**
     * @param SessionManagerInterface $session
     */
    public function __construct(
        SessionManagerInterface $session
    )
    {
        $this->session = $session;
    }

    /**
     * @return array[]
     */
    public function getConversation(): array
    {
        if ($this->sessionCache !== null) {
            return $this->sessionCache;
        }

        $this->session->start();
        $conversation = $this->session->getData(self::SESSION_KEY);

        if (!is_array($conversation)) {
            $conversation = $this->getDefaultConversation();
        }

        $conversation['messages'] = $this->normalizeArray($conversation, 'messages');
        $conversation['products'] = $this->normalizeArray($conversation, 'products');
        $conversation['context'] = $this->normalizeArray($conversation, 'context', []);

        $this->sessionCache = $conversation;
        return $this->sessionCache;
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->session->start();
        $this->sessionCache = $this->getDefaultConversation();
        $this->session->setData(self::SESSION_KEY, $this->sessionCache);
    }

    /**
     * @param string $role
     * @param string $content
     * @param array $extraData
     * @return void
     */
    private function addMessage(string $role, string $content, array $extraData = []): void
    {
        $message = array_merge([
            'role' => $role,
            'content' => trim($content),
            'created_at' => date('c'),
        ], $extraData);

        $this->appendMessage($message);
    }

    /**
     * @param string $content
     * @param string|null $topicContext
     * @param array $extraData
     * @return void
     */
    public function addUserMessage(string $content, ?string $topicContext = null, array $extraData = []): void
    {
        if ($topicContext !== null && $topicContext !== '') {
            $extraData['topic_context'] = $topicContext;
        }

        $this->addMessage(self::ROLE_USER, $content, $extraData);
    }

    /**
     * @param string $content
     * @param array $products
     * @param array|null $comparison
     * @param array $extraData
     * @return void
     */
    public function addAssistantMessage(
        string $content,
        array  $products = [],
        ?array $comparison = null,
        array  $extraData = []
    ): void
    {
        if ($products) {
            $extraData['products'] = $products;
        }
        if ($comparison !== null) {
            $extraData['comparison'] = $comparison;
        }

        if ($products) {
            $conversation = $this->getConversation();
            $bySku = [];
            foreach (array_merge($conversation['products'], $products) as $product) {
                $sku = (string)($product['sku'] ?? '');
                if (is_array($product) && $sku !== '') {
                    $bySku[$sku] = $product;
                }
            }
            $conversation['products'] = array_values(array_slice($bySku, -self::MAX_PRODUCTS));
            $this->save($conversation);
        }

        $message = array_merge([
            'role' => self::ROLE_ASSISTANT,
            'content' => trim($content),
            'created_at' => date('c'),
        ], $extraData);

        $this->appendMessage($message);
    }

    /**
     * @return string|null
     */
    public function getActiveTopic(): ?string
    {
        $conversation = $this->getConversation();

        foreach (array_reverse($conversation['messages']) as $msg) {
            if (!empty($msg['topic_context'])) {
                return (string)$msg['topic_context'];
            }
        }

        return null;
    }

    /**
     * @param string $userPrompt
     * @return array
     */
    public function buildNativeContents(string $userPrompt): array
    {
        $conversation = $this->getConversation();
        $rawMessages = array_slice($conversation['messages'], -8);
        $contents = [];

        foreach ($rawMessages as $msg) {
            if (!is_array($msg)) {
                continue;
            }

            $role = ($msg['role'] ?? '') === self::ROLE_ASSISTANT ? 'model' : 'user';

            if (!empty($msg['native_turns']) && is_array($msg['native_turns'])) {
                $firstNativeTurn = reset($msg['native_turns']);
                if (empty($contents)
                    && is_array($firstNativeTurn)
                    && ($firstNativeTurn['role'] ?? '') === 'model'
                ) {
                    continue;
                }

                foreach ($msg['native_turns'] as $nativeTurn) {
                    if (!is_array($nativeTurn)
                        || !in_array($nativeTurn['role'] ?? '', [self::ROLE_USER, 'model'], true)
                        || empty($nativeTurn['parts'])
                        || !is_array($nativeTurn['parts'])
                    ) {
                        continue;
                    }

                    $contents[] = [
                        'role' => $nativeTurn['role'],
                        'parts' => $nativeTurn['parts'],
                    ];
                }
                continue;
            }

            $text = $this->formatMessageText($msg, $role);

            if ($text === '') {
                continue;
            }

            $this->appendOrMerge($contents, $role, $text);
        }

        $contents = $this->ensureStartsWithUser($contents);

        $userPrompt = trim($userPrompt);
        if ($userPrompt !== '') {
            $lastIndex = count($contents) - 1;
            if ($lastIndex >= 0 && $contents[$lastIndex]['role'] === 'user') {
                $contents[$lastIndex]['parts'][] = ['text' => $userPrompt];
            } else {
                $this->appendOrMerge($contents, 'user', $userPrompt);
            }
        }

        return array_values($contents);
    }

    /**
     * @param array $msg
     * @param string $role
     * @return string
     */
    private function formatMessageText(array $msg, string $role): string
    {
        $text = trim((string)($msg['content'] ?? ''));

        if ($role === 'model' && !empty($msg['products']) && is_array($msg['products'])) {
            $text .= $this->buildProductSummary($msg['products']);
        }

        return $text;
    }

    /**
     * @param array $products
     * @return string
     */
    private function buildProductSummary(array $products): string
    {
        $summaries = [];
        foreach ($products as $p) {
            $sku = (string)($p['sku'] ?? '');
            $name = (string)($p['name'] ?? '');
            $price = isset($p['formatted_price'])
                ? trim(strip_tags((string)$p['formatted_price']))
                : (isset($p['price']) ? number_format((float)$p['price'], 2, '.', ',') : '');
            if ($name !== '') {
                $summaries[] = "{$name} (SKU: {$sku}" . ($price !== '' ? ", price: {$price}" : '') . ')';
            }
        }

        return $summaries
            ? "\n[Products shown: " . implode('; ', $summaries) . "]"
            : '';
    }

    /**
     * @param array $contents
     * @param string $role
     * @param string $text
     * @return void
     */
    private function appendOrMerge(array &$contents, string $role, string $text): void
    {
        $lastIndex = count($contents) - 1;
        if ($lastIndex >= 0
            && $contents[$lastIndex]['role'] === $role
            && isset($contents[$lastIndex]['parts'][0]['text'])
        ) {
            $contents[$lastIndex]['parts'][0]['text'] .= "\n" . $text;
        } else {
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $text]]
            ];
        }
    }

    /**
     * @param array $contents
     * @return array
     */
    private function ensureStartsWithUser(array $contents): array
    {
        while (!empty($contents) && $contents[0]['role'] !== 'user') {
            array_shift($contents);
        }
        return $contents;
    }

    /**
     * @param array $conversation
     * @return void
     */
    private function save(array $conversation): void
    {
        $this->sessionCache = $conversation;
        $this->session->setData(self::SESSION_KEY, $conversation);
    }

    /**
     * @param array $message
     * @return void
     */
    private function appendMessage(array $message): void
    {
        $conversation = $this->getConversation();
        $conversation['messages'][] = $message;

        if (count($conversation['messages']) > self::MAX_MESSAGES) {
            $conversation['messages'] = array_slice($conversation['messages'], -self::MAX_MESSAGES);
        }

        $this->save($conversation);
    }

    /**
     * @return array[]
     */
    private function getDefaultConversation(): array
    {
        return [
            'messages' => [],
            'products' => [],
            'context' => [],
        ];
    }

    /**
     * @param array $data
     * @param string $key
     * @param array|null $default
     * @return array
     */
    private function normalizeArray(array $data, string $key, ?array $default = null): array
    {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            return $default ?? [];
        }

        return $default !== null ? $data[$key] : array_values($data[$key]);
    }
}
