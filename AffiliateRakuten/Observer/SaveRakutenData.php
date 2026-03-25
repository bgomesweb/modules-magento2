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

namespace Bgomesweb\AffiliateRakuten\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Exception;

class SaveRakutenData implements ObserverInterface
{
    /**
     * Injects dependencies for cookie handling, order persistence, and logging.
     *
     * @param CookieManagerInterface $cookieManager
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Validates Rakuten tracking cookies and saves the data into the order.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order) {
            $this->logger->warning('Rakuten: No order found in observer event');
            return;
        }

        $ranSiteID = $this->cookieManager->getCookie('atrv');
        $landingTimestamp = $this->cookieManager->getCookie('ald');

        if (!$ranSiteID || !$landingTimestamp) {
            $this->logger->debug('Rakuten: Missing tracking cookies', [
                'order_id' => $order->getIncrementId(),
                'has_ranSiteID' => (bool)$ranSiteID,
                'has_timestamp' => (bool)$landingTimestamp
            ]);
            return;
        }

        try {
            if (!$this->isValidRanSiteID($ranSiteID)) {
                $this->logger->warning('Rakuten: Invalid ranSiteID format for order ' . $order->getIncrementId(), [
                    'ranSiteID' => $ranSiteID
                ]);
                return;
            }

            if (!$this->isValidTimestamp($landingTimestamp)) {
                $this->logger->warning('Rakuten: Invalid timestamp format for order ' . $order->getIncrementId(), [
                    'timestamp' => $landingTimestamp
                ]);
                return;
            }

            $order->setData('rakuten_site_id', $ranSiteID);
            $order->setData('rakuten_landing_timestamp', $landingTimestamp);

            $this->orderRepository->save($order);

            $this->logger->debug('Rakuten: Successfully saved tracking data', [
                'order_id' => $order->getIncrementId(),
                'ranSiteID' => $ranSiteID,
                'timestamp' => $landingTimestamp
            ]);

        } catch (Exception $e) {
            $this->logger->error('Rakuten: Error saving tracking data for order ' . $order->getIncrementId(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Validates the format of the Rakuten tracking ID (ranSiteID).
     *
     * @param string $ranSiteID
     * @return bool
     */
    private function isValidRanSiteID(string $ranSiteID): bool
    {
        return preg_match('/^[a-zA-Z0-9\-._\/*]{34}$/', $ranSiteID) === 1;
    }

    /**
     * Validates the landing timestamp format and ensures it represents a real date.
     *
     * @param string $timestamp
     * @return bool
     */
    private function isValidTimestamp(string $timestamp): bool
    {
        if (preg_match('/^\d{8}_\d{6}$/', $timestamp) !== 1) {
            return false;
        }

        $year = (int) substr($timestamp, 0, 4);
        $month = (int) substr($timestamp, 4, 2);
        $day = (int) substr($timestamp, 6, 2);

        return checkdate($month, $day, $year);
    }
}
