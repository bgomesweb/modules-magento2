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

namespace Bgomesweb\GeminiProvider\Model\Data;

use Bgomesweb\GeminiProvider\Api\Data\GenerationResultInterface;
use Magento\Framework\DataObject;

class GenerationResult extends DataObject implements GenerationResultInterface
{
    /**
     * @return string
     */
    public function getText(): string
    {
        return (string)$this->getData(self::KEY_TEXT);
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return (string)$this->getData(self::KEY_MODEL);
    }

    /**
     * @return int
     */
    public function getTokensInput(): int
    {
        return (int)$this->getData(self::KEY_TOKENS_INPUT);
    }

    /**
     * @return int
     */
    public function getTokensOutput(): int
    {
        return (int)$this->getData(self::KEY_TOKENS_OUTPUT);
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return (string)$this->getData(self::KEY_STATUS);
    }

    /**
     * @return float
     */
    public function getCostUsd(): float
    {
        return (float)$this->getData(self::KEY_COST_USD);
    }
}
