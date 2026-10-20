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

namespace Bgomesweb\AiAssistant\Controller\Chat;

use Bgomesweb\AiAssistant\Model\Config as AiConfig;
use Bgomesweb\AiAssistant\Model\Service\ConversationManager;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Load implements HttpGetActionInterface
{
    private readonly JsonFactory $jsonFactory;
    private readonly ConversationManager $conversationManager;
    private readonly AiConfig $aiConfig;
    private readonly StoreManagerInterface $storeManager;

    public function __construct(
        JsonFactory           $jsonFactory,
        ConversationManager   $conversationManager,
        AiConfig              $aiConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->conversationManager = $conversationManager;
        $this->aiConfig = $aiConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @return Json
     * @throws NoSuchEntityException
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $storeId = (int)$this->storeManager->getStore()->getId();

        if (!$this->aiConfig->isEnabled($storeId)) {
            return $result->setData([
                'error' => true,
                'message' => __('Virtual assistant disabled in this store.'),
            ]);
        }

        $conversation = $this->conversationManager->getConversation();
        $messages = $conversation['messages'];

        array_walk($messages, static function (array &$message): void {
            unset($message['suggested_replies']);
        });

        return $result->setData([
            'success' => true,
            'messages' => $messages,
        ]);
    }
}
