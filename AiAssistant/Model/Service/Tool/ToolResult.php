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

use Bgomesweb\AiAssistant\Api\ToolResultInterface;

class ToolResult implements ToolResultInterface
{
    /**
     * @param bool $success
     * @param mixed|null $result
     * @param string $error
     */
    public function __construct(
        private readonly bool  $success,
        private readonly mixed $result = null,
        private readonly string $error = ''
    )
    {
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return mixed
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param mixed|null $result
     * @return static
     */
    public static function success(mixed $result = null): static
    {
        return new static(true, $result);
    }

    /**
     * @param string $error
     * @return static
     */
    public static function error(string $error): static
    {
        return new static(false, null, $error);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'success' => $this->success,
            'result' => $this->result,
        ];

        if (!$this->success) {
            $data['error'] = $this->error;
        }

        return $data;
    }
}
