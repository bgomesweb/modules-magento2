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

namespace Bgomesweb\AiAssistant\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DisplayPages implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'all',
                'label' => __('All Pages')
            ],
            [
                'value' => 'cms_index_index',
                'label' => __('Home Page')
            ],
            [
                'value' => 'cms_page_view',
                'label' => __('CMS Pages')
            ],
            [
                'value' => 'catalog_category_view',
                'label' => __('Category Pages')
            ],
            [
                'value' => 'catalog_product_view',
                'label' => __('Product Pages')
            ],
            [
                'value' => 'catalogsearch_result_index',
                'label' => __('Search Results')
            ],
            [
                'value' => 'checkout_cart_index',
                'label' => __('Shopping Cart')
            ],
            [
                'value' => 'checkout_index_index',
                'label' => __('Checkout')
            ],
            [
                'value' => 'customer_account_index',
                'label' => __('Customer Dashboard')
            ],
            [
                'value' => 'sales_order_history',
                'label' => __('My Orders')
            ],
            [
                'value' => 'contact_index_index',
                'label' => __('Contact Us')
            ]
        ];
    }
}
