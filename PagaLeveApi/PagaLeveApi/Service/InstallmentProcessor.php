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

namespace Bgomesweb\PagaLeveApi\Service;

use Bgomesweb\CoreApi\Api\Command\Http\Request\RequestInterface;
use Bgomesweb\PagaLeveApi\Api\Builder\PagaleveInstallment\PagaleveInstallmentBuilderInterface;
use Bgomesweb\PagaLeveApi\Api\Command\PagaleveInstallment\SaveInterface;
use Bgomesweb\PagaLeveApi\Api\Data\InstallmentValuePagaleveInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class InstallmentProcessor
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly SaveInterface $save,
        private readonly PagaleveInstallmentBuilderInterface $installmentBuilder,
        private readonly LoggerInterface $logger
    ) {
    }

    public function process(OrderInterface $order): void
    {
        $checkoutId = $order->getData('pagaleve_checkout_id');
        $paymentId = $order->getData('pagaleve_payment_id');

        if (empty($paymentId) || empty($checkoutId)) {
            return;
        }

        try {
            $response = $this->request->execute([
                'query' => [
                    'page' => '0',
                    'size' => '100',
                    'checkout_id' => $checkoutId
                ]
            ]);

            if (!isset($response['total_count']) || $response['total_count'] === 0) {
                return;
            }

            $installmentValues = [];
            foreach ($response['items'] as $item) {
                $installmentValues[] = [
                    InstallmentValuePagaleveInterface::INSTALLMENT_NUMBER => $item['number'] ?? null,
                    InstallmentValuePagaleveInterface::AMOUNT => (float)($item['amount'] ?? 0),
                ];
            }

            $installmentCount = $response['items'][0]['number_of_installments'] ?? null;
            if (!$installmentCount) {
                return;
            }

            $installmentEntity = $this->installmentBuilder->build([
                PagaleveInstallmentInterface::ORDER_ID => $order->getEntityId(),
                PagaleveInstallmentInterface::PAGALEVE_INSTALLMENT => $installmentCount,
                PagaleveInstallmentInterface::INSTALLMENT_VALUES => json_encode(
                    $installmentValues,
                    JSON_UNESCAPED_UNICODE
                ),
            ]);

            $this->save->execute($installmentEntity);
        } catch (Throwable $e) {
            $this->logger->error('Pagaleve Installment Error: ' . $e->getMessage());
        }
    }
}
