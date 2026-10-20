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

namespace Bgomesweb\GeminiProvider\Api;

use Bgomesweb\GeminiProvider\Api\Data\GenerationResultInterface;

interface GeminiClientInterface
{
    /**
     * Generates content via the Gemini API, trying fallback models on recoverable failures.
     *
     * @param  string   $prompt  System instruction sent to the model.
     * @param  string   $content User content to run the prompt against.
     * @param  int|null $storeId Store scope to resolve config from; null for default scope.
     * @param  array    $meta    Free-form context forwarded to the bgomesweb_ai_request_completed
     *                           event (e.g. 'feature', 'module', 'request_id'); may also carry
     *                           per-call 'timeout' / 'max_output_tokens' overrides.
     * @return GenerationResultInterface
     * @throws \RuntimeException When the provider is disabled, misconfigured, or every model
     *                            (primary and fallbacks) fails.
     */
    public function generateContent(
        string $prompt,
        string $content,
        ?int $storeId = null,
        array $meta = []
    ): GenerationResultInterface;
}
