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

namespace Bgomesweb\GeminiProvider\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class Models extends AbstractFieldArray
{
    private ?DragHandle $dragHandle = null;

    /**
     * @param Context             $context
     * @param SecureHtmlRenderer  $secureHtmlRenderer
     * @param array               $data
     */
    public function __construct(
        Context $context,
        private readonly SecureHtmlRenderer $secureHtmlRenderer,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Prepares the model fallback-order grid columns.
     *
     * @return void
     */
    protected function _prepareToRender(): void
    {
        $this->setData('html_id', $this->getElement()->getId());

        $this->addColumn('sort', [
            'label' => __('Order'),
            'renderer' => $this->getDragHandleRenderer(),
        ]);
        $this->addColumn('model', [
            'label' => __('Model Name (e.g. gemini-2.5-flash-lite)'), 'class' => 'required-entry'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Model');
    }

    /**
     * Appends the drag-to-sort init script after the standard grid markup.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        return parent::_toHtml() . $this->getSortableInitScript();
    }

    /**
     * @return DragHandle
     */
    private function getDragHandleRenderer(): DragHandle
    {
        if (!$this->dragHandle) {
            $this->dragHandle = $this->getLayout()->createBlock(
                DragHandle::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->dragHandle;
    }

    /**
     * Builds the CSP-safe inline script that wires up jQuery UI Sortable on the grid rows.
     *
     * @return string
     */
    private function getSortableInitScript(): string
    {
        $rowsContainerId = 'addRow' . $this->getHtmlId();
        $arrayRowGlobalName = 'arrayRow' . $this->getHtmlId();

        $script = <<<JS
require(['Bgomesweb_GeminiProvider/js/model-sortable'], function (initSortable) {
    initSortable('{$rowsContainerId}', '{$arrayRowGlobalName}');
});
JS;

        return $this->secureHtmlRenderer->renderTag('script', [], $script, false);
    }
}
