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

namespace Bgomesweb\PagaLeveApi\Api\Repository;

use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Magento\Framework\Exception\LocalizedException;

interface PagaleveInstallmentRepositoryInterface
{
    /**
     * Get a Pagaleve installment by order ID
     *
     * @param int $orderId
     * @return PagaleveInstallmentInterface
     * @throws LocalizedException
     */
    public function getByOrderId(int $orderId): PagaleveInstallmentInterface;

    /**
     * Save a Pagaleve installment
     *
     * @param PagaleveInstallmentInterface $installment
     * @return void
     * @throws LocalizedException
     */
    public function save(PagaleveInstallmentInterface $installment): void;

    /**
     * Get a list of installments where needs_retry = 1
     *
     * @return PagaleveInstallmentInterface[]
     */
    public function getListWithRetryFlag(): array;
}
