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

interface PagaleveInstallmentInterface
{
    public const TABLE_NAME = 'bgomesweb_pagaleve_installment';
    public const ENTITY_ID = 'entity_id';
    public const ORDER_ID = 'order_id';
    public const PAGALEVE_INSTALLMENT = 'pagaleve_installment';
    public const INSTALLMENT_VALUES = 'installment_values';
    public const NEEDS_RETRY = 'needs_retry';

    /**
     * @return int
     */
    public function getEntityId(): int;

    /**
     * @param int $entityId
     * @return PagaleveInstallmentInterface
     */
    public function setEntityId(int $entityId): PagaleveInstallmentInterface;

    /**
     * @return int
     */
    public function getOrderId(): int;

    /**
     * @param int $orderId
     * @return PagaleveInstallmentInterface
     */
    public function setOrderId(int $orderId): PagaleveInstallmentInterface;

    /**
     * @return int
     */
    public function getPagaleveInstallment(): int;

    /**
     * @param int $pagaleveInstallment
     * @return PagaleveInstallmentInterface
     */
    public function setPagaleveInstallment(int $pagaleveInstallment): PagaleveInstallmentInterface;

    /**
     * @return string
     */
    public function getInstallmentValues(): string;

    /**
     * @param string $installmentValues
     * @return PagaleveInstallmentInterface.
     */
    public function setInstallmentValues(string $installmentValues): PagaleveInstallmentInterface;

    /**
     * @return bool
     */
    public function getNeedsRetry(): bool;

    /**
     * @param bool $needsRetry
     * @return PagaleveInstallmentInterface
     */
    public function setNeedsRetry(bool $needsRetry): PagaleveInstallmentInterface;
}
