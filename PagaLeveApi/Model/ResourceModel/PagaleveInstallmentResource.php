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

namespace Bgomesweb\PagaLeveApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PagaleveInstallmentResource extends AbstractDb
{
    /** @var string */
    protected $_eventPrefix = 'bgomesweb_pagaleve_installment_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init('bgomesweb_pagaleve_installment', 'entity_id');
        $this->_useIsObjectNew = true;
    }
}
