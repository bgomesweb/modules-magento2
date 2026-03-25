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

namespace Bgomesweb\PagaLeveApi\Model\Builder\PagaleveInstallment;

use Bgomesweb\PagaLeveApi\Api\Builder\PagaleveInstallment\PagaleveInstallmentValuesBuilderInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PaymentPagaleveInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PaymentPagaleveInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

class PagaleveInstallmentValuesBuilder implements PagaleveInstallmentValuesBuilderInterface
{
    /**
     * @param DataObjectHelper $dataObjectHelper
     * @param PaymentPagaleveInterfaceFactory $paymentPagaleveFactory
     */
    public function __construct(
        private readonly DataObjectHelper $dataObjectHelper,
        private readonly PaymentPagaleveInterfaceFactory $paymentPagaleveFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function build(PagaleveInstallmentInterface $data): PaymentPagaleveInterface
    {
        $paymentPagaleve = $this->paymentPagaleveFactory->create();

        $installments = json_decode(
            $data->getInstallmentValues() ?: '{}',
            true
        );

        if (json_last_error() !== JSON_ERROR_NONE) {
            $installments = [];
        }

        $this->dataObjectHelper->populateWithArray(
            $paymentPagaleve,
            [
                PaymentPagaleveInterface::QTY_INSTALLMENT => $data->getPagaleveInstallment() !== null
                    ? (int) $data->getPagaleveInstallment()
                    : null,
                PaymentPagaleveInterface::INSTALLMENTS => $installments,
            ],
            PaymentPagaleveInterface::class
        );

        return $paymentPagaleve;
    }
}
