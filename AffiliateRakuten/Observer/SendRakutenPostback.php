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
use Bgomesweb\AffiliateRakuten\Service\TransactionSender;

class SendRakutenPostback implements ObserverInterface
{
    /**
     * Injects the transaction sender service.
     *
     * @param TransactionSender $transactionSender
     */
    public function __construct(
        private readonly TransactionSender $transactionSender
    ) {
    }

    /**
     * Executes the observer and triggers the transaction postback.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        $this->transactionSender->sendTransaction($order);
    }
}
