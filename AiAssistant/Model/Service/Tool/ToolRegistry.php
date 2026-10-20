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
use Bgomesweb\AiAssistant\Api\ToolRegistryInterface;
use Bgomesweb\AiAssistant\Api\ToolResultInterface;

class ToolRegistry implements ToolRegistryInterface
{
    private array $backendTools;

    /**
     * @param array $backendTools
     */
    public function __construct(
        array $backendTools = []
    )
    {
        $this->backendTools = [];
        foreach ($backendTools as $tool) {
            if (is_array($tool) && !empty($tool['name'])) {
                $this->backendTools[(string)$tool['name']] = $tool;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getBackendTools(): array
    {
        $declarations = [];
        foreach ($this->backendTools as $tool) {
            $declarations[] = [
                'name' => $tool['name'],
                'description' => $tool['description'],
                'parameters' => $tool['parameters'],
            ];
        }
        return $declarations;
    }

    /**
     * @inheritDoc
     */
    public function hasBackendTool(string $toolName): bool
    {
        return isset($this->backendTools[$toolName]);
    }

    /**
     * @inheritDoc
     */
    public function execute(string $toolName, array $args): ToolResultInterface
    {
        $tool = $this->backendTools[$toolName] ?? null;
        if ($tool === null) {
            return ToolResult::error(
                __('Tool not found.')->render()
            );
        }

        if (isset($tool['handler']) && $tool['handler'] instanceof ToolHandlerInterface) {
            try {
                $result = $tool['handler']->execute($args);
                return ToolResult::success($result);
            } catch (\Throwable $e) {
                return ToolResult::error(
                    __('Could not perform this action at the moment.')->render()
                );
            }
        }

        return ToolResult::error(
            __('Tool %1 has no handler configured.', $toolName)->render()
        );
    }

    /**
     * @inheritDoc
     */
    public function isTerminalTool(string $toolName): bool
    {
        $tool = $this->backendTools[$toolName] ?? null;
        if ($tool !== null) {
            return !empty($tool['is_terminal']);
        }
        return false;
    }
}
