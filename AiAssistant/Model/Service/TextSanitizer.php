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

class TextSanitizer
{
    /**
     * @param string $text
     * @return string
     */
    public function extractSafeMessage(string $text): string
    {
        $data = $this->decodeJsonText($text);
        if (is_array($data)) {
            return $this->sanitize((string)($data['message'] ?? ''));
        }

        if (str_contains($text, 'thought_process')) {
            return '';
        }

        return $this->sanitize($text);
    }

    /**
     * @param string $text
     * @return string
     */
    private function sanitize(string $text): string
    {
        return trim($this->stripFences(trim($text)));
    }

    /**
     * @param string $text
     * @return array|null
     */
    private function decodeJsonText(string $text): ?array
    {
        $text = $this->stripFences(trim($text));

        if ($text === '') {
            return null;
        }

        $data = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            return $data;
        }

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $data = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                return $data;
            }
        }

        return null;
    }

    /**
     * @param string $text
     * @return string
     */
    private function stripFences(string $text): string
    {
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```\s*$/i', '', $text) ?? $text;
        return trim($text);
    }
}
