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

namespace Bgomesweb\PagaLeveApi\Plugin\Pagaleve\Payment\Helper\Data;

use Bgomesweb\CoreApi\Api\Command\Http\Request\RequestInterface;
use Bgomesweb\PagaLeveApi\Api\Builder\PagaleveInstallment\PagaleveInstallmentBuilderInterface;
use Bgomesweb\PagaLeveApi\Api\Command\PagaleveInstallment\SaveInterface;
use Bgomesweb\PagaLeveApi\Api\Data\InstallmentValuePagaleveInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Bgomesweb\PagaLeveApi\Api\Repository\PagaleveInstallmentRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Pagaleve\Payment\Helper\Data;
use Bgomesweb\PagaLeveApi\Helper\Data as PagaleveHelper;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class UpdatePayment
{
    /**
     * @param RequestInterface $request
     * @param SaveInterface $save
     * @param PagaleveInstallmentBuilderInterface $installmentBuilder
     * @param PagaleveHelper $pagaleveHelper
     * @param PagaleveInstallmentRepositoryInterface $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly SaveInterface $save,
        private readonly PagaleveInstallmentBuilderInterface $installmentBuilder,
        private readonly PagaleveHelper $pagaleveHelper,
        private readonly PagaleveInstallmentRepositoryInterface $repository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Handles Pagaleve installment data after invoice creation.
     *
     * @param Data $subject
     * @param null $result
     * @param OrderInterface $order
     * @param array $checkoutData
     *
     * @return void
     * @throws LocalizedException
     * @throws JsonException
     */
    public function afterCreateInvoice($subject, $result, $order, $checkoutData): void
    {
        if (!$this->pagaleveHelper->isPagaLeveApiEnabled() || !$order->getData('pagaleve_payment_id')) {
            return;
        }

        $pagaleveCheckoutId = $order->getData('pagaleve_checkout_id');
        $pagalevePaymentId = $order->getData('pagaleve_payment_id');

        if (empty($pagaleveCheckoutId) || empty($pagalevePaymentId)) {
            return;
        }

        $response = $this->request->execute(['query' => [
            'page' => '0',
            'size' => '100',
            'checkout_id' => $pagaleveCheckoutId
        ]]);

        if (isset($response['total_count']) && $response['total_count'] == 0) {
            $pagaleveInstallment = $this->installmentBuilder->build([
                PagaleveInstallmentInterface::ORDER_ID => $order->getEntityId(),
                PagaleveInstallmentInterface::NEEDS_RETRY => true,
                PagaleveInstallmentInterface::INSTALLMENT_VALUES => null
            ]);

            try {
                $this->save->execute($pagaleveInstallment);
            } catch (CouldNotSaveException $e) {
                $this->logger->error('Could not save needs_retry flag: ' . $e->getMessage());
            }

            return;
        }

        $installmentValues = [];

        foreach ($response['items'] as $installment) {
            $installmentValues[] = [
                InstallmentValuePagaleveInterface::INSTALLMENT_NUMBER => $installment['number'] ?? null,
                InstallmentValuePagaleveInterface::AMOUNT => (float)$installment['amount'] ?? null,
            ];
        }

        $installment = $response['items'][0]['number_of_installments'] ?? null;

        if (!$installment) {
            return;
        }

        $pagaleveInstallment = $this->installmentBuilder->build([
            PagaleveInstallmentInterface::ORDER_ID => $order->getEntityId(),
            PagaleveInstallmentInterface::PAGALEVE_INSTALLMENT => $installment,
            PagaleveInstallmentInterface::INSTALLMENT_VALUES => json_encode(
                $installmentValues,
                JSON_UNESCAPED_UNICODE
            )
        ]);

        try {
            $this->save->execute($pagaleveInstallment);
        } catch (CouldNotSaveException $e) {
            return;
        }
    }
}
