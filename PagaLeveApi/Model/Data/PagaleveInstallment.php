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

use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Bgomesweb\PagaLeveApi\Model\ResourceModel\PagaleveInstallmentResource;
use Magento\Framework\Model\AbstractModel;

class PagaleveInstallment extends AbstractModel implements PagaleveInstallmentInterface
{
    /** @var string */
    protected $_eventPrefix = 'Bgomesweb_pagaleve_installment_model';

    /**
     * Initialize magento model.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(PagaleveInstallmentResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): int
    {
        return (int) $this->getData(static::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setEntityId($entityId): PagaleveInstallmentInterface
    {
        $this->setData(static::ENTITY_ID, $entityId);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOrderId(): int
    {
        return (int) $this->getData(static::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId(int $orderId): PagaleveInstallmentInterface
    {
        $this->setData(static::ORDER_ID, $orderId);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPagaleveInstallment(): int
    {
        return (int) $this->getData(static::PAGALEVE_INSTALLMENT);
    }

    /**
     * @inheritDoc
     */
    public function setPagaleveInstallment(int $pagaleveInstallment): PagaleveInstallmentInterface
    {
        $this->setData(static::PAGALEVE_INSTALLMENT, $pagaleveInstallment);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getInstallmentValues(): string
    {
        return (string)$this->getData(static::INSTALLMENT_VALUES);
    }

    /**
     * @inheritDoc
     */
    public function setInstallmentValues(string $installmentValues): PagaleveInstallmentInterface
    {
        $this->setData(static::INSTALLMENT_VALUES, $installmentValues);
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getNeedsRetry(): bool
    {
        return (bool) $this->getData(static::NEEDS_RETRY);
    }

    /**
     * @inheritDoc
     */
    public function setNeedsRetry(bool $needsRetry): PagaleveInstallmentInterface
    {
        $this->setData(static::NEEDS_RETRY, $needsRetry);
        return $this;
    }

}

