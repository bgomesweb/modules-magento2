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

namespace Bgomesweb\PagaLeveApi\Api\Builder\PagaleveInstallment;

use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;

interface PagaleveInstallmentBuilderInterface
{
    /**
     * Builds and returns a PagaleveInstallmentInterface instance using the provided data array.
     *
     * @param array $data
     * @return PagaleveInstallmentInterface
     */
    public function build(array $data): PagaleveInstallmentInterface;
}
