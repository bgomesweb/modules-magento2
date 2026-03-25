<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 *
 * @author      Digital Hub Core Team <contato@Bgomesweb.com.br>
 * @copyright   2024 Digital Hub (https://www.Bgomesweb.com.br/)
 * @license     https://www.Bgomesweb.com.br Copyright
 * @link        https://www.Bgomesweb.com.br/
 */

declare(strict_types=1);

namespace Bgomesweb\PagaLeveApi\Api\Command\PagaleveInstallment;

use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;

interface GetInterface
{
    /**
     * Retrieves a PagaleveInstallmentInterface instance based on a given value and field.
     *
     * @param int|string $value
     * @param string $field
     * @return PagaleveInstallmentInterface|null
     */
    public function execute(int|string $value, string $field = 'entity_id'): ?PagaleveInstallmentInterface;
}
