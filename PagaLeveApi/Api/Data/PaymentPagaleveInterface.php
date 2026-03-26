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

namespace Bgomesweb\PagaLeveApi\Api\Data;

interface PaymentPagaleveInterface
{
    public const QTY_INSTALLMENT = "qty_installments";
    public const INSTALLMENTS = "installments";

    /**
     * @return int
     */
    public function getQtyInstallments(): int;

    /**
     * @param int $qtyInstallment
     * @return PaymentPagaleveInterface
     */
    public function setQtyInstallments(int $qtyInstallment): PaymentPagaleveInterface;

    /**
     * @return \Bgomesweb\PagaLeveApi\Api\Data\InstallmentValuePagaleveInterface[]
     */
    public function getInstallments(): array;

    /**
     * @param \Bgomesweb\PagaLeveApi\Api\Data\InstallmentValuePagaleveInterface[] $installments
     * @return PaymentPagaleveInterface
     */
    public function setInstallments(array $installments): PaymentPagaleveInterface;
}
