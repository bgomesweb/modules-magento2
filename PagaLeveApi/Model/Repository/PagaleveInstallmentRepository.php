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

namespace Bgomesweb\PagaLeveApi\Model\Repository;

use Bgomesweb\PagaLeveApi\Api\Repository\PagaleveInstallmentRepositoryInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Bgomesweb\PagaLeveApi\Model\ResourceModel\PagaleveInstallmentResource as ResourceModel;
use Bgomesweb\PagaLeveApi\Model\ResourceModel\PagaleveInstallment\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class PagaleveInstallmentRepository implements PagaleveInstallmentRepositoryInterface
{
    public function __construct(
        private readonly ResourceModel $resource,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function getByOrderId(int $orderId): PagaleveInstallmentInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('order_id', $orderId);
        $item = $collection->getFirstItem();

        if (!$item || !$item->getEntityId()) {
            throw new NoSuchEntityException(__('No installment found for order ID %1', $orderId));
        }

        return $item;
    }

    public function save(PagaleveInstallmentInterface $installment): void
    {
        try {
            $this->resource->save($installment);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Could not save installment: %1', $e->getMessage()));
        }
    }

    /**
     * Returns an array of Pagaleve installments where needs_retry = 1
     *
     * @return PagaleveInstallmentInterface[]
     */
    public function getListWithRetryFlag(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('needs_retry', 1);

        return $collection->getItems();
    }
}
