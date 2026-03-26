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

namespace Bgomesweb\PagaLeveApi\Helper;

use Bgomesweb\PagaLeveApi\Api\Command\PagaleveInstallment\GetInterface;
use Bgomesweb\PagaLeveApi\Api\Data\PagaleveInstallmentInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Data extends AbstractHelper
{
    const XML_PATH_ACTIVE = 'paga_leve_api/general/feature_flag';
    const XML_PATH_ENVIRONMENT = 'paga_leve_api/api_settings/environment';
    const XML_PATH_BASE_URL = 'paga_leve_api/api_settings/base_url';
    const XML_PATH_BASE_URL_TEST = 'paga_leve_api/api_settings/base_url_test';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly GetInterface $get
    ) {
        parent::__construct($context);
    }

    /**
     * Checks if the Feature Flag is activated.
     *
     * @return bool
     */
    public function isPagaLeveApiEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ACTIVE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Checks if the Paga Leve API is running in Sandbox mode.
     *
     * @return bool
     */
    public function isPagaLeveSandbox(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_ENVIRONMENT, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Checks if the Paga Leve API is running in Sandbox mode.
     *
     * @return bool
     */
    public function getPagaLeveBaseUrl(): string
    {
        $isSandboxActive = (bool) $this->scopeConfig->getValue(self::XML_PATH_ENVIRONMENT, ScopeInterface::SCOPE_STORE);

        if ($isSandboxActive) {
            return (string) $this->scopeConfig->getValue(self::XML_PATH_BASE_URL_TEST, ScopeInterface::SCOPE_STORE);
        }
        return (string) $this->scopeConfig->getValue(self::XML_PATH_BASE_URL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Retrieves the number of installments for a given order ID using the Pagaleve installment service.
     *
     * @param int $orderId
     * @return int
     */
    public function getInstallmentsPagaleve(int $orderId): int
    {
        $installment = $this->get->execute($orderId, PagaleveInstallmentInterface::ORDER_ID)?->getPagaleveInstallment();
        return $installment !== null ? (int) $installment : 0;
    }

    /**
     * Return username api Pagaleve
     *
     * @return mixed
     */
    public function getUsername()
    {
        return $this->scopeConfig->getValue('paga_leve_api/credentials/username', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Return password api Pagaleve
     *
     * @return mixed
     */
    public function getPassword()
    {
        return $this->scopeConfig->getValue('paga_leve_api/credentials/password', ScopeInterface::SCOPE_STORE);
    }
}
