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

namespace Bgomesweb\AffiliateRakuten\Service;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class TransactionSender
{
    private const BASE_URL = 'https://track.linksynergy.com/ep';
    private const MID = '54082';
    private const CURRENCY = 'BRL';

    /**
     * Initializes the service with HTTP client and logger dependencies.
     *
     * @param Curl $curl
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Curl $curl,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Send the postback for the completed purchase.
     *
     * @param OrderInterface $order
     * @return void
     */
    public function sendTransaction(OrderInterface $order): void
    {
        try {
            $params = $this->buildParams($order);
            $url = self::BASE_URL . '?' . http_build_query($params);

            $this->curl->get($url);

            $this->logger->info('[Rakuten Postback] URL enviada: ' . $url);
            $this->logger->info('[Rakuten Postback] Response: ' . $this->curl->getBody());
        } catch (\Throwable $e) {
            $this->logger->error('[Rakuten Postback] Erro ao enviar postback: ' . $e->getMessage());
        }
    }

    /**
     * Prepare the postback parameters.
     *
     * @param OrderInterface $order
     * @return array
     */
    private function buildParams(OrderInterface $order): array
    {
        $items = $order->getAllVisibleItems();

        $skus = [];
        $names = [];
        $qtys = [];
        $prices = [];

        foreach ($items as $item) {
            $skus[] = $item->getSku();
            $names[] = $item->getName();
            $qtys[] = (string) (int) $item->getQtyOrdered();
            $price = $item->getBaseRowTotalInclTax() - $item->getBaseDiscountAmount();
            $prices[] = (string) ((int) round($price * 100));
        }

        $tr = $_COOKIE['atrv'] ?? '';
        $land = $_COOKIE['ald'] ?? '';

        return [
            'mid'      => self::MID,
            'ord'      => $order->getIncrementId(),
            'tr'       => $tr,
            'land'     => str_replace(':', '', substr($land, 0, 13)),
            'date'     => date('Ymd_Hi'),
            'amtlist'  => implode('|', $prices),
            'skulist'  => implode('|', $skus),
            'qlist'    => implode('|', $qtys),
            'cur'      => self::CURRENCY,
            'namelist' => implode('|', $names)
        ];
    }
}
