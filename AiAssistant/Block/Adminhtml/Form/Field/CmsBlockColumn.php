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

namespace Bgomesweb\AiAssistant\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Context;
use Bgomesweb\AiAssistant\Model\Config\Source\CmsBlock;

class CmsBlockColumn extends Select
{
    private readonly CmsBlock $cmsBlockSource;

    /**
     * @param Context $context
     * @param CmsBlock $cmsBlockSource
     * @param array $data
     */
    public function __construct(
        Context  $context,
        CmsBlock $cmsBlockSource,
        array    $data = []
    )
    {
        parent::__construct($context, $data);
        $this->cmsBlockSource = $cmsBlockSource;
    }

    /**
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $options = $this->cmsBlockSource->toOptionArray();
            array_unshift($options, ['value' => '', 'label' => __('None (No Context)')]);
            $this->setOptions($options);
        }
        $this->setClass('admin__control-select');
        $this->setExtraParams('style="width: 100%; min-width: 200px;"');
        return parent::_toHtml();
    }
}
