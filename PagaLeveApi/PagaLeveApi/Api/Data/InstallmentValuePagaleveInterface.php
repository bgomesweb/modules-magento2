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

interface InstallmentValuePagaleveInterface
{
    public const INSTALLMENT_NUMBER = 'installment_number';
    public const AMOUNT = 'amount';

    /**
     * @return int|null
     */
    public function getInstallmentNumber(): ?int;

    /**
     * @param int|null $installmentNumber
     *
     * @return InstallmentValuePagaleveInterface
     */
    public function setInstallmentNumber(?int $installmentNumber): InstallmentValuePagaleveInterface;

    /**
     * @return float|null
     */
    public function getAmount(): ?float;

    /**
     * @param float|null $amount
     *
     * @return InstallmentValuePagaleveInterface
     */
    public function setAmount(?float $amount): InstallmentValuePagaleveInterface;
}
