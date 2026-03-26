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

namespace Bgomesweb\PagaLeveApi\Plugin\Api\OrderRepository;

use Bgomesweb\PagaLeveApi\Api\Builder\PagaleveInstallment\PagaleveInstallmentValuesBuilderInterface;
use Bgomesweb\PagaLeveApi\Api\Command\PagaleveInstallment\GetInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Bgomesweb\PagaLeveApi\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;

class AfterGet
{
    /**
     * @param GetInterface $get
     * @param Data $data
     * @param PagaleveInstallmentValuesBuilderInterface $installmentBuilder
     */
    public function __construct(
        private readonly GetInterface $get,
        private readonly Data $data,
        private readonly PagaleveInstallmentValuesBuilderInterface $installmentBuilder,
    ) {
    }

    /**
     * Adds 'installments_pagaleve' to the order's extension attributes if not already set after retrieval.
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order)
    {
        if (!$this->data->isPagaLeveApiEnabled()) {
            return $order;
        }

        $extensionAttributes = $order->getExtensionAttributes();
        $installmentsPagaleve = $this->get->execute($order->getEntityId(), PagaleveInstallmentInterface::ORDER_ID);

        if (!$installmentsPagaleve) {
            return $order;
        }

        if ($extensionAttributes) {
            $extensionAttributes->setPaymentPagaleve(
                $this->installmentBuilder->build($installmentsPagaleve)
            );
            $order->setExtensionAttributes($extensionAttributes);
        }
        return $order;
    }
}
