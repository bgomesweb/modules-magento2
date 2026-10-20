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

use Magento\Framework\View\Element\Template;

class DragHandle extends Template
{
    /**
     * Renders a static drag handle cell, used as a column renderer inside the Models grid.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        return '<span class="drag-handle" data-role="draggable-handle" style="cursor:move;" title="'
            . $this->escapeHtmlAttr(__('Drag to reorder')) . '">&#9776;</span>';
    }
}
