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

namespace Bgomesweb\GeminiProvider\Model;

use Laminas\Filter\StripTags;
use Psr\Log\LoggerInterface;

class ResponseSanitizer
{
    private const array ALLOWED_TAGS = [
        'section', 'h2', 'h3', 'p', 'a', 'br',
        'strong', 'em', 'ul', 'ol', 'li', 'div', 'span',
        'table', 'thead', 'tbody', 'tr', 'th', 'td'
    ];

    private const array ALLOWED_ATTRIBUTES = [
        'href', 'title', 'target', 'rel', 'class', 'id', 'colspan', 'rowspan', 'scope'
    ];

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Extracts and sanitizes the generated HTML from a successful Gemini API response body.
     *
     * @param  string $responseBody
     * @return string
     */
    public function sanitize(string $responseBody): string
    {
        $data = json_decode($responseBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Failed to decode Gemini API response: ' . json_last_error_msg());
        }

        $candidate = $data['candidates'][0] ?? null;

        if ($candidate === null) {
            throw new \RuntimeException('Gemini API response is missing candidates[0]');
        }

        $finishReason = $candidate['finishReason'] ?? null;

        if ($finishReason !== null && !in_array($finishReason, ['STOP', 'MAX_TOKENS'], true)) {
            $safetyRatings = $candidate['safetyRatings'] ?? [];
            $message = sprintf(
                'Gemini response blocked. Finish reason: %s. Safety ratings: %s',
                $finishReason,
                json_encode($safetyRatings)
            );
            $this->logger->warning('[GeminiProvider] ' . $message);
            throw new \RuntimeException($message);
        }

        $parts = $candidate['content']['parts'] ?? [];

        if (array_column($parts, 'functionCall')) {
            return '';
        }

        $rawHtml = $parts[0]['text'] ?? null;

        if ($rawHtml === null) {
            if ($finishReason === 'MAX_TOKENS') {
                throw new \RuntimeException(
                    __(
                        'The Gemini response hit the output token limit before producing any content. '
                        . 'Try again with a shorter document.'
                    )->render()
                );
            }

            throw new \RuntimeException('Gemini API response missing candidates[0].content.parts[0].text');
        }

        $raw = trim((string)$rawHtml);

        // JSON responses must not go through HTML sanitization.
        if (str_starts_with($raw, '{') || str_starts_with($raw, '[')) {
            return $raw;
        }

        return $this->sanitizeHtml($raw);
    }

    /**
     * Strips markup down to the allowed tag/attribute whitelist.
     *
     * @param  string $html
     * @return string
     */
    private function sanitizeHtml(string $html): string
    {
        $allowedTagsMap = array_fill_keys(self::ALLOWED_TAGS, []);

        return new StripTags(
            [
            'allowTags' => $allowedTagsMap,
            'allowAttribs' => self::ALLOWED_ATTRIBUTES,
            ]
        )?->filter($this->stripEmbeddedCode($html));
    }

    /**
     * Removes <style>/<script> blocks together with their bodies.
     *
     * StripTags only drops the tag markup, never the text between it
     *
     * @param  string $html
     * @return string
     */
    private function stripEmbeddedCode(string $html): string
    {
        return (string)preg_replace(
            '#<\s*(style|script)\b[^>]*>.*?<\s*/\s*\1\s*>#is',
            '',
            $html
        );
    }
}
