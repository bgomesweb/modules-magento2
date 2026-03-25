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

namespace Bgomesweb\PagaLeveApi\Model\Data;

use Magento\Framework\DataObject;
use Bgomesweb\PagaLeveApi\Api\Data\InstallmentValuePagaleveInterface;

class InstallmentValuePagaleve extends DataObject implements InstallmentValuePagaleveInterface
{
    /**
     * @inheritDoc
     */
    public function getInstallmentNumber(): ?int
    {
        return (int) $this->getData(static::INSTALLMENT_NUMBER) ?: null;
    }

    /**
     * @inheritDoc
     */
    public function setInstallmentNumber(?int $installmentNumber): InstallmentValuePagaleveInterface
    {
        $this->setData(static::INSTALLMENT_NUMBER, $installmentNumber);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAmount(): ?float
    {
        return (float) $this->getData(static::AMOUNT) ?: null;
    }

    /**
     * @inheritDoc
     */
    public function setAmount(?float $amount): InstallmentValuePagaleveInterface
    {
        $this->setData(static::AMOUNT, $amount);

        return $this;
    }
}
