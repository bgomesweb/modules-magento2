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

interface ToolRegistryInterface
{
    /**
     * @return array
     */
    public function getBackendTools(): array;

    /**
     * @param string $toolName
     * @return bool
     */
    public function hasBackendTool(string $toolName): bool;

    /**
     * @param string $toolName
     * @param array $args
     * @return ToolResultInterface
     */
    public function execute(string $toolName, array $args): ToolResultInterface;

    /**
     * @param string $toolName
     * @return bool
     */
    public function isTerminalTool(string $toolName): bool;
}