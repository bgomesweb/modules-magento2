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

namespace Bgomesweb\ScheduledImportExport\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;

final class OperationBeforeSave
{
    /**
     * @param RequestInterface $request
     */
    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @param Operation $subject
     * @return void
     */
    public function beforeSave(Operation $subject): void
    {
        $data = $this->request->getPostValue();

        $successData = $data['export_success']
            ?? $data['import_success']
            ?? null;

        if (!$successData || !is_array($successData)) {
            return;
        }

        $subject->addData([
            'success_email_receiver'     => $this->cleanValue($successData['receiver'] ?? null),
            'success_email_sender'       => $this->cleanValue($successData['sender'] ?? null),
            'success_email_template'     => $this->cleanTemplateValue($successData['template'] ?? null),
            'success_email_copy'         => $this->cleanValue($successData['copy_to'] ?? null),
            'success_email_copy_method'  => $this->cleanValue($successData['copy_method'] ?? null),
        ]);
    }

    /**
     * @param string|null $value
     * @return string|null
     */
    private function cleanTemplateValue(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $labelToValueMap = [
            'Import Success Notification'            => 'bgomesweb_import_success_template',
            'Import Success Notification - Custom'   => 'bgomesweb_import_success_template',
            'Import Success (Default)'               => 'magento_scheduledimportexport_import_success',
            'Import Success'                         => 'magento_scheduledimportexport_import_success',

            'Export Success Notification'           => 'bgomesweb_export_success_template',
            'Export Success Notification - Custom'  => 'bgomesweb_export_success_template',
            'Export Success (Default)'               => 'magento_scheduledimportexport_export_success',
            'Export Success'                         => 'magento_scheduledimportexport_export_success',
        ];

        return $labelToValueMap[$value] ?? $value;
    }

    /**
     * @param string|null $value
     * @return string|null
     */
    private function cleanValue(?string $value): ?string
    {
        return empty($value) ? null : trim($value);
    }
}
