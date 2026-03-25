<?php

namespace Bgomesweb\PagaLeveApi\Model\ResourceModel\PagaleveInstallment;

use Bgomesweb\PagaLeveApi\Model\Data\PagaleveInstallment;
use Bgomesweb\PagaLeveApi\Model\ResourceModel\PagaleveInstallmentResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'Bgomesweb_pagaleve_installment_collection';

    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(PagaleveInstallment::class, PagaleveInstallmentResource::class);
    }
}
