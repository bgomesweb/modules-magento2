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

namespace Bgomesweb\PagaLeveApi\Cron;

use Bgomesweb\CoreApi\Api\Command\Http\Request\RequestInterface;
use Bgomesweb\PagaLeveApi\Api\Repository\PagaleveInstallmentRepositoryInterface;
use Bgomesweb\PagaLeveApi\Api\Command\PagaleveInstallment\SaveInterface;
use Bgomesweb\PagaLeveApi\Api\Builder\PagaleveInstallment\PagaleveInstallmentBuilderInterface;
use Bgomesweb\PagaLeveApi\Api\Data\InstallmentValuePagaleveInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Bgomesweb\PagaLeveApi\Helper\Data as PagaleveHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class RetryInstallmentFetch
{
    /**
     * @param PagaleveInstallmentRepositoryInterface $repository
     * @param RequestInterface $request
     * @param SaveInterface $save
     * @param PagaleveHelper $pagaleveHelper
     * @param PagaleveInstallmentBuilderInterface $installmentBuilder
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly PagaleveInstallmentRepositoryInterface $repository,
        private readonly RequestInterface $request,
        private readonly SaveInterface $save,
        private readonly PagaleveHelper $pagaleveHelper,
        private readonly PagaleveInstallmentBuilderInterface $installmentBuilder,
        private readonly LoggerInterface $logger,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Retries fetching and updates installment data for flagged orders via cron.
     *
     * @return void
     */
    public function execute(): void
    {
        if (!$this->pagaleveHelper->isPagaLeveApiEnabled()) {
            return;
        }

        $items = $this->repository->getListWithRetryFlag();

        foreach ($items as $item) {
            try {
                $orderId = (int)$item->getOrderId();
                $order = $this->orderRepository->get($orderId);
                $storeId = (int)$order->getStoreId();
                $this->storeManager->setCurrentStore($storeId);

                $pagaleveCheckoutId = $order->getData('pagaleve_checkout_id');

                if (!$pagaleveCheckoutId) {
                    continue;
                }

                $response = $this->request->execute(['query' => [
                    'page' => '0',
                    'size' => '100',
                    'checkout_id' => $pagaleveCheckoutId
                ]]);

                if (!isset($response['total_count']) || $response['total_count'] == 0) {
                    continue;
                }

                $installment = $response['items'][0]['number_of_installments'] ?? null;
                if (!$installment) {
                    continue;
                }

                $installmentValues = [];
                foreach ($response['items'] as $installmentData) {
                    $installmentValues[] = [
                        InstallmentValuePagaleveInterface::INSTALLMENT_NUMBER => $installmentData['number'] ?? null,
                        InstallmentValuePagaleveInterface::AMOUNT => (float)($installmentData['amount'] ?? 0),
                    ];
                }

                $updatedModel = $this->installmentBuilder->build([
                    PagaleveInstallmentInterface::ENTITY_ID => $item->getEntityId(),
                    PagaleveInstallmentInterface::PAGALEVE_INSTALLMENT => $installment,
                    PagaleveInstallmentInterface::INSTALLMENT_VALUES => json_encode($installmentValues),
                    PagaleveInstallmentInterface::NEEDS_RETRY => false
                ]);

                $this->save->execute($updatedModel);

            } catch (\Throwable $e) {
                $this->logger->error("Cron update failed for order #{$item->getOrderId()}: " . $e->getMessage());
            }
        }
    }
}
