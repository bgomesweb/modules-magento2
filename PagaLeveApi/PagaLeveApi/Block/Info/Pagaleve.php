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

namespace Bgomesweb\PagaLeveApi\Block\Info;

use Magento\Framework\Exception\LocalizedException;
use Pagaleve\Payment\Block\Info\Pagaleve as OriginalPagaleve;
use Magento\Sales\Model\Order;

class Pagaleve extends OriginalPagaleve
{
    /** @var string */
    protected $_template = 'Bgomesweb_PagaLeveApi::sales/order/info/pagaleve.phtml';

    /**
     * Retrieve order model object
     *
     * @return Order
     * @throws LocalizedException
     */
    public function getOrder(): Order
    {
        return parent::getOrder();
    }
}
