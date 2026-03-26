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

use Magento\ScheduledImportExport\Model\Scheduled\Operation;
use bgomesweb\ScheduledImportExport\Model\Email\SuccessNotifier;

class SendSuccessEmailAfterRun
{
    /**
     * @param SuccessNotifier $successNotifier
     */
    public function __construct(
        private readonly SuccessNotifier $successNotifier
    ) {
    }

    /**
     * @param Operation $subject
     * @param $result
     * @return mixed
     */
    public function afterRun(Operation $subject, $result)
    {
        if (!$result) {
            return $result;
        }

        if (!in_array($subject->getOperationType(), ['import', 'export'], true)) {
            return $result;
        }

        if (!$subject->getSuccessEmailReceiver()) {
            return $result;
        }

        $this->successNotifier->send($subject);

        return $result;
    }
}
