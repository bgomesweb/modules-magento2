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

namespace Bgomesweb\PagaLeveApi\Api\Command\PagaleveInstallment;

use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Magento\Framework\Exception\CouldNotSaveException;

interface SaveInterface
{
    /**
     * Saves the provided PagaleveInstallmentInterface instance to the database.
     *
     * @param PagaleveInstallmentInterface $data
     * @return PagaleveInstallmentInterface
     * @throws CouldNotSaveException
     */
    public function execute(PagaleveInstallmentInterface $data): PagaleveInstallmentInterface;
}
