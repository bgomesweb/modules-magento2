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

namespace Bgomesweb\ScheduledImportExport\Model\Email;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;
use Magento\Framework\App\Area;
use Magento\Backend\Model\Url as BackendUrl;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;

class SuccessNotifier
{
    /**
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param BackendUrl $backendUrlBuilder
     * @param Filesystem $filesystem
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private readonly TransportBuilder $transportBuilder,
        private readonly StoreManagerInterface $storeManager,
        private readonly BackendUrl $backendUrlBuilder,
        private readonly Filesystem $filesystem,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * @param Operation $operation
     * @return void
     */
    public function send(Operation $operation): void
    {
        $recipient = $operation->getSuccessEmailReceiver();
        if (!$recipient) {
            return;
        }

        try {
            $template = $operation->getSuccessEmailTemplate()
                ?: 'bgomesweb_scheduledimportexport_export_success';

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($template)
                ->setTemplateOptions([
                    'area' => Area::AREA_ADMINHTML,
                    'store' => 0,
                ])
                ->setTemplateVars([
                    'operation_name' => $operation->getName(),
                    'execution_date' => $this->formatDate($operation->getLastRunDate()),
                    'status' => 'Success',
                    'file_link' => $this->getFileDownloadLink($operation),
                ])
                ->setFromByScope($operation->getSuccessEmailSender() ?: 'general')
                ->addTo($recipient);

            if ($operation->getSuccessEmailCopy()) {
                $emails = array_map('trim', explode(',', $operation->getSuccessEmailCopy()));

                foreach ($emails as $email) {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        if ($operation->getSuccessEmailCopyMethod() === 'bcc') {
                            $transport->addBcc($email);
                        } else {
                            $transport->addCc($email);
                        }
                    }
                }
            }

            $transport->getTransport()->sendMessage();

        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->critical($e);
            }
        }
    }

    /**
     * @param Operation $operation
     * @return string
     */
    private function getFileDownloadLink(Operation $operation): string
    {
        $fileName = $this->getFileNameFromHistory($operation);

        if (!$fileName) {
            $fileName = $this->getMostRecentExportFile();
        }

        if ($fileName && $this->validateFileName($fileName)) {
            return $this->backendUrlBuilder->getUrl(
                'adminhtml/export_file/download',
                ['filename' => $fileName]
            );
        }

        return '';
    }

    /**
     * @param Operation $operation
     * @return string|null
     */
    private function getFileNameFromHistory(Operation $operation): ?string
    {
        $history = $operation->getHistory();

        if (!$history || !is_array($history)) {
            return null;
        }

        krsort($history);

        foreach ($history as $run) {
            if (isset($run['file']) && $run['file']) {
                $filePath = $run['file'];

                if (strpos($filePath, 'export/') === 0) {
                    return substr($filePath, 7);
                }

                return basename($filePath);
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    private function getMostRecentExportFile(): ?string
    {
        try {
            $exportDirectory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_IMPORT_EXPORT);

            $files = [];
            $allFiles = $exportDirectory->read('export');

            foreach ($allFiles as $file) {
                if ($exportDirectory->isFile($file) && strpos($file, 'export/') === 0) {
                    $stat = $exportDirectory->stat($file);
                    $fileName = basename($file);

                    if ($this->validateFileName($fileName)) {
                        $files[$stat['mtime']] = $fileName;
                    }
                }
            }

            if (empty($files)) {
                return null;
            }

            krsort($files);
            return reset($files);

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $fileName
     * @return bool
     */
    private function validateFileName(string $fileName): bool
    {
        if (empty($fileName)) {
            return false;
        }

        $fileName = basename($fileName);

        $validExtensions = ['csv', 'xml', 'json', 'txt', 'xls', 'xlsx'];
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($extension, $validExtensions)) {
            return false;
        }

        if (preg_match('/[^\w\.\-]/', $fileName)) {
            return false;
        }

        return true;
    }

    /**
     * @param $timestamp
     * @return string
     */
    private function formatDate($timestamp): string
    {
        if (!$timestamp) {
            return '';
        }

        if (is_numeric($timestamp)) {
            return date('d/m/Y - H:i', (int)$timestamp);
        }

        $time = strtotime($timestamp);
        return $time ? date('d/m/Y - H:i', $time) : '';
    }
}
