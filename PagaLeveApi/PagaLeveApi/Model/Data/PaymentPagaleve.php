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

use Bgomesweb\PagaLeveApi\Api\Data\PaymentPagaleveInterface;
use Magento\Framework\DataObject;

class PaymentPagaleve extends DataObject implements PaymentPagaleveInterface
{
    /**
     * @inheritDoc
     */
    public function getQtyInstallments(): int
    {
        return (int)$this->getData(static::QTY_INSTALLMENT);
    }

    /**
     * @inheritDoc
     */
    public function setQtyInstallments(int $qtyInstallment): PaymentPagaleveInterface
    {
        $this->setData(static::QTY_INSTALLMENT, $qtyInstallment);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInstallments(): array
    {
        return (array) $this->getData(static::INSTALLMENTS) ?: [];
    }

    /**
     * @inheritDoc
     */
    public function setInstallments(array $installments): PaymentPagaleveInterface
    {
        $this->setData(static::INSTALLMENTS, $installments);

        return $this;
    }
}
