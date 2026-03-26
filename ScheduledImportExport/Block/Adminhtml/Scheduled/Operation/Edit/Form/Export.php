<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 *
 * @author      Bruno Gomes <bgomesweb@gmail.com>
 * @copyright   2026 Bruno Gomes (<https://www.bgomesweb.com.br/>)
 * @license     <https://www.bgomesweb.com.br> Copyright
 * @link        <https://www.bgomesweb.com.br/>
 */

declare(strict_types=1);

namespace Bgomesweb\ScheduledImportExport\Block\Adminhtml\Scheduled\Operation\Edit\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Model\Config\Source\Email\Identity;
use Magento\Config\Model\Config\Source\Email\Method as EmailCopyMethod;
use Magento\Config\Model\Config\Source\Email\TemplateFactory;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Option\ArrayPool;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Model\Export\ConfigInterface;
use Magento\ImportExport\Model\Source\Export\Format;
use Magento\ScheduledImportExport\Block\Adminhtml\Scheduled\Operation\Edit\Form\Export as CoreExport;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;
use Magento\ScheduledImportExport\Model\Scheduled\Operation\Data as OperationData;

class Export extends CoreExport
{
    /** @var EmailCopyMethod */
    private readonly EmailCopyMethod $emailCopyMethod;

    /** @var Json */
    private readonly Json $jsonSerializer;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param ArrayPool $optionArrayPool
     * @param EmailCopyMethod $emailMethod
     * @param Identity $emailIdentity
     * @param OperationData $operationData
     * @param Yesno $sourceYesno
     * @param StringUtils $string
     * @param TemplateFactory $templateFactory
     * @param Format $sourceExportFormat
     * @param ConfigInterface $exportConfig
     * @param EmailCopyMethod $emailCopyMethod
     * @param Json $jsonSerializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        ArrayPool $optionArrayPool,
        \Magento\Config\Model\Config\Source\Email\Method $emailMethod,
        Identity $emailIdentity,
        OperationData $operationData,
        Yesno $sourceYesno,
        StringUtils $string,
        TemplateFactory $templateFactory,
        Format $sourceExportFormat,
        ConfigInterface $exportConfig,
        EmailCopyMethod $emailCopyMethod,
        Json $jsonSerializer,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $optionArrayPool,
            $emailMethod,
            $emailIdentity,
            $operationData,
            $sourceYesno,
            $string,
            $templateFactory,
            $sourceExportFormat,
            $exportConfig,
            $data
        );

        $this->emailCopyMethod = $emailCopyMethod;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * @return $this
     */
    protected function _prepareForm(): static
    {
        parent::_prepareForm();

        $form = $this->getForm();
        $operation = $this->_coreRegistry->registry('current_operation');

        if (!$operation instanceof Operation) {
            return $this;
        }

        $successConfig = [
            'receiver'    => $operation->getSuccessEmailReceiver(),
            'sender'      => $operation->getSuccessEmailSender(),
            'template'    => $operation->getSuccessEmailTemplate(),
            'copy_to'     => $operation->getSuccessEmailCopy(),
            'copy_method' => $operation->getSuccessEmailCopyMethod(),
        ];

        $this->addSuccessEmailFieldset($form, $successConfig);

        return $this;
    }

    /**
     * @param \Magento\Framework\Data\Form $form
     * @param array $successConfig
     * @return void
     */
    private function addSuccessEmailFieldset(
        \Magento\Framework\Data\Form $form,
        array $successConfig
    ): void {
        $fieldset = $form->addFieldset(
            'export_success_emails',
            [
                'legend' => __('Export Success Emails'),
                'class'  => 'fieldset-wide'
            ]
        );

        $fieldset->addField('export_success_receiver', 'select', [
            'name'   => 'export_success[receiver]',
            'label'  => __('Success Email Receiver'),
            'title'  => __('Success Email Receiver'),
            'values' => $this->_emailIdentity->toOptionArray(),
        ]);

        $fieldset->addField('export_success_sender', 'select', [
            'name'   => 'export_success[sender]',
            'label'  => __('Success Email Sender'),
            'title'  => __('Success Email Sender'),
            'values' => $this->_emailIdentity->toOptionArray(),
        ]);

        $fieldset->addField('export_success_template', 'select', [
            'name'   => 'export_success[template]',
            'label'  => __('Success Email Template'),
            'title'  => __('Success Email Template'),
            'values' => $this->getSuccessTemplates(),
        ]);

        $fieldset->addField('export_success_copy_to', 'textarea', [
            'name'  => 'export_success[copy_to]',
            'label' => __('Send Success Email Copy To'),
            'title' => __('Send Success Email Copy To'),
            'class' => 'validate-emails',
            'note'  => __('Separate multiple emails with commas'),
            'style' => 'height:60px;',
        ]);

        $fieldset->addField('export_success_copy_method', 'select', [
            'name'   => 'export_success[copy_method]',
            'label'  => __('Send Success Email Copy Method'),
            'title'  => __('Send Success Email Copy Method'),
            'values' => $this->emailCopyMethod->toOptionArray(),
        ]);

        $copyTo = $successConfig['copy_to'] ?? '';
        if (is_array($copyTo)) {
            $copyTo = implode(', ', $copyTo);
        }

        $form->addValues([
            'export_success_receiver'    => (string)$successConfig['receiver'],
            'export_success_sender'      => (string)$successConfig['sender'],
            'export_success_template'    => (string)$successConfig['template'],
            'export_success_copy_to'     => (string)$copyTo,
            'export_success_copy_method' => (string)$successConfig['copy_method'],
        ]);
    }

    /**
     * @return array[]
     */
    private function getSuccessTemplates(): array
    {
        $templates = [
            [
                'value' => 'magento_scheduledimportexport_export_success',
                'label' => __('Export Success (Default)')
            ]
        ];

        try {
            $allTemplates = $this->_templateFactory->create()->toOptionArray();

            foreach ($allTemplates as $template) {
                if (
                    empty($template['value']) ||
                    empty($template['label']) ||
                    $template['value'] === 'magento_scheduledimportexport_export_success'
                ) {
                    continue;
                }

                $templates[] = $template;
            }
        } catch (\Exception) {
        }

        return $templates;
    }
}
