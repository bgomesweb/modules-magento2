<?php
/**
 * @author      Bruno Gomes <bgomesweb@gmail.com>
 * @copyright   2025 Bruno Gomes (<https://www.bgomesweb.com.br/>)
 * @license     <https://www.bgomesweb.com.br> Copyright
 * @link        <https://www.bgomesweb.com.br/>
 */
declare(strict_types=1);

namespace Bgomesweb\GeminiProvider\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class Pricing extends AbstractFieldArray
{
    /**
     * Prepares the per-model pricing grid columns.
     *
     * @return void
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('model', [
            'label' => __('Model Name (e.g. gemini-2.5-flash-lite)'), 'class' => 'required-entry'
        ]);
        $this->addColumn('cost_input', [
            'label' => __('Input Cost / 1M (USD)'), 'class' => 'required-entry validate-number'
        ]);
        $this->addColumn('cost_output', [
            'label' => __('Output Cost / 1M (USD)'), 'class' => 'required-entry validate-number'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Model Pricing');
    }
}
