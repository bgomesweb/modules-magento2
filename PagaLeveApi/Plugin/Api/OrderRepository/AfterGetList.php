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

use Bgomesweb\PagaLeveApi\Api\Builder\PagaleveInstallment\PagaleveInstallmentBuilderInterface;
use Bgomesweb\PagaLeveApi\Api\Builder\PagaleveInstallment\PagaleveInstallmentValuesBuilderInterface;
use Bgomesweb\PagaLeveApi\Api\Command\PagaleveInstallment\GetInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Bgomesweb\PagaLeveApi\Helper\Data;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Api\SearchResultsInterface;

class AfterGetList
{
    /**
     * @param GetInterface $get
     * @param Data $data
     */
    public function __construct(
        private readonly GetInterface $get,
        private readonly Data $data,
        private readonly PagaleveInstallmentValuesBuilderInterface $installmentBuilder,
    ) {
    }

    /**
     * Adds 'installments_pagaleve' to the extension attributes of each order in the result set if not already set.
     *
     * @param OrderRepositoryInterface $subject
     * @param SearchResultsInterface $result
     * @return SearchResultsInterface
     */
    public function afterGetList(OrderRepositoryInterface $subject, SearchResultsInterface $result)
    {
        if (!$this->data->isPagaLeveApiEnabled()) {
            return $result;
        }

        foreach ($result->getItems() as $order) {
            $extensionAttributes = $order->getExtensionAttributes();

            $installmentsPagaleve = $this->get->execute(
                (int) $order->getEntityId(),
                PagaleveInstallmentInterface::ORDER_ID
            );

            if (!$installmentsPagaleve) {
                continue;
            }

            if ($extensionAttributes) {
                $extensionAttributes->setPaymentPagaleve(
                    $this->installmentBuilder->build($installmentsPagaleve)
                );
                $order->setExtensionAttributes($extensionAttributes);
            }
        }
        return $result;
    }
}
