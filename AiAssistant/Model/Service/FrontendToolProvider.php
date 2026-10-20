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

use Bgomesweb\AiAssistant\Api\FrontendToolProviderInterface;

class FrontendToolProvider implements FrontendToolProviderInterface
{
    private const string FRONTEND_PREFIX = 'front__';

    /**
     * @param string $toolName
     * @return bool
     */
    public function isFrontendTool(string $toolName): bool
    {
        return str_starts_with($toolName, self::FRONTEND_PREFIX);
    }
}
