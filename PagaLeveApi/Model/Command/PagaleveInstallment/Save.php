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

use Bgomesweb\PagaLeveApi\Api\Command\PagaleveInstallment\SaveInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Bgomesweb\PagaLeveApi\Model\ResourceModel\PagaleveInstallmentResource;
use Magento\Framework\Exception\CouldNotSaveException;

class Save implements SaveInterface
{
    /**
     * @param PagaleveInstallmentResource $resource
     */
    public function __construct(
        private readonly PagaleveInstallmentResource $resource
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(PagaleveInstallmentInterface $data): PagaleveInstallmentInterface
    {
        try {
            $this->resource->save($data);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save Pagaleve Installment: %s', $e->getMessage()));
        }

        return $data;
    }
}
