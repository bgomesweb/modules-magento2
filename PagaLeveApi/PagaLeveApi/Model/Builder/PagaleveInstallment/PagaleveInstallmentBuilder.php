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

use Bgomesweb\PagaLeveApi\Api\Builder\PagaleveInstallment\PagaleveInstallmentBuilderInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

class PagaleveInstallmentBuilder implements PagaleveInstallmentBuilderInterface
{
    /**
     * @param PagaleveInstallmentInterfaceFactory $factory
     * @param DataObjectHelper $objectHelper
     */
    public function __construct(
        private readonly PagaleveInstallmentInterfaceFactory $factory,
        private readonly DataObjectHelper $objectHelper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function build(array $data): PagaleveInstallmentInterface
    {
        $installment = $this->factory->create();

        $this->objectHelper->populateWithArray(
            $installment,
            $data,
            PagaleveInstallmentInterface::class
        );

        return $installment;
    }
}
