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
use Bgomesweb\PagaLeveApi\Api\Data\PaymentPagaleveInterface;

interface PagaleveInstallmentValuesBuilderInterface
{
    /**
     * @param PagaleveInstallmentInterface $data
     * @return PaymentPagaleveInterface
     */
    public function build(PagaleveInstallmentInterface $data): PaymentPagaleveInterface;
}
