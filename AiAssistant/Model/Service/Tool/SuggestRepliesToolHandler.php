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
 * Backend tool handler that captures suggested reply chips proposed by the LLM.
 *
 * The LLM calls suggest_replies({ replies: [...] }) to surface follow-up action
 * buttons in the chat UI. The ToolExecutionLoop accumulates these and the
 * ChatProcessor passes them to the frontend via the suggested_replies field.
 */
class SuggestRepliesToolHandler implements ToolHandlerInterface
{
    private const int MAX_REPLIES = 4;

    /**
     * @param array $args
     * @return array|int[]
     */
    public function execute(array $args): array
    {
        $raw = $args['replies'] ?? [];
        if (!is_array($raw)) {
            return ['accepted' => 0];
        }

        $accepted = 0;
        $normalised = [];
        foreach (array_slice($raw, 0, self::MAX_REPLIES) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $label = trim((string)($item['label'] ?? $item['text'] ?? ''));
            $text = trim((string)($item['text'] ?? $item['label'] ?? ''));
            if ($label === '' || $text === '') {
                continue;
            }
            $normalised[] = ['label' => $label, 'text' => $text];
            $accepted++;
        }

        $textMessage = trim((string)($args['message'] ?? ''));

        return [
            'accepted' => $accepted,
            '_replies' => $normalised,
            '_text_message' => $textMessage,
            'message' => sprintf('%d suggested reply(ies) saved.', $accepted),
        ];
    }
}
