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

namespace Bgomesweb\AiAssistant\Api;

interface ToolExecutionLoopInterface
{
    public const int MAX_ITERATIONS = 5;

    /**
     * @param string $userPrompt
     * @param string $systemInstruction
     * @param array $mergedTools
     * @param int|null $storeId
     * @return array
     */
    public function execute(
        string $userPrompt,
        string $systemInstruction,
        array  $mergedTools,
        ?int   $storeId = null
    ): array;
}
