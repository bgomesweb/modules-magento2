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

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Topics extends AbstractFieldArray
{
    private readonly CmsBlockColumn $cmsBlockRenderer;

    public function __construct(
        CmsBlockColumn      $cmsBlockRenderer,
        Context             $context,
        array               $data = [],
        ?SecureHtmlRenderer $secureRenderer = null,
    )
    {
        parent::__construct(
            $context,
            $data,
            $secureRenderer
        );
        $this->cmsBlockRenderer = $cmsBlockRenderer;
    }

    /**
     * @return void
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('topic_name', ['label' => __('Topic Name'), 'class' => 'required-entry', 'style' => 'width: 250px']);
        $this->addColumn('query', ['label' => __('Suggested Query'), 'class' => 'required-entry', 'style' => 'width: 350px']);
        $this->addColumn('cms_block', [
            'label' => __('CMS Block'),
            'renderer' => $this->cmsBlockRenderer,
            'style' => 'width: 250px'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Topic');
    }

    /**
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $cmsBlock = $row->getData('cms_block');
        if ($cmsBlock !== null) {
            $row->setData('option_extra_attrs', [
                'option_' . $this->cmsBlockRenderer->calcOptionHash($cmsBlock) => 'selected="selected"',
            ]);
        }
    }
}
