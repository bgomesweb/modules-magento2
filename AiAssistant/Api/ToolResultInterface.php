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

interface ToolResultInterface
{
    /**
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * @return mixed
     */
    public function getResult(): mixed;

    /**
     * @return string
     */
    public function getError(): string;

    /**
     * @param mixed|null $result
     * @return static
     */
    public static function success(mixed $result = null): static;

    /**
     * @param string $error
     * @return static
     */
    public static function error(string $error): static;

    /**
     * @return array
     */
    public function toArray(): array;
}
