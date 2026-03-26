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

namespace Bgomesweb\PagaLeveApi\Model\Command\PagaleveInstallment;

use Bgomesweb\PagaLeveApi\Api\Command\PagaleveInstallment\GetInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterfaceFactory;
use Bgomesweb\PagaLeveApi\Model\ResourceModel\PagaleveInstallmentResource;

class Get implements GetInterface
{
    /**
     * @param PagaleveInstallmentResource $resource
     * @param PagaleveInstallmentInterfaceFactory $installmentFactory
     */
    public function __construct(
        private readonly PagaleveInstallmentResource $resource,
        private readonly PagaleveInstallmentInterfaceFactory $installmentFactory,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(int|string $value, string $field = 'entity_id'): ?PagaleveInstallmentInterface
    {
        $installment = $this->installmentFactory->create();

        $this->resource->load($installment, $value, $field);

        if (!$installment->getEntityId()) {
            return null;
        }

        return $installment;
    }
}
