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
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Clear implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private readonly JsonFactory $jsonFactory;
    private readonly ConversationManager $conversationManager;
    private readonly AiConfig $aiConfig;
    private readonly StoreManagerInterface $storeManager;
    private readonly FormKey $formKey;

    /**
     * @param JsonFactory $jsonFactory
     * @param ConversationManager $conversationManager
     * @param AiConfig $aiConfig
     * @param StoreManagerInterface $storeManager
     * @param FormKey $formKey
     */
    public function __construct(
        JsonFactory           $jsonFactory,
        ConversationManager   $conversationManager,
        AiConfig              $aiConfig,
        StoreManagerInterface $storeManager,
        FormKey               $formKey
    )
    {
        $this->jsonFactory = $jsonFactory;
        $this->conversationManager = $conversationManager;
        $this->aiConfig = $aiConfig;
        $this->storeManager = $storeManager;
        $this->formKey = $formKey;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return new InvalidRequestException(
            $this->jsonFactory->create()->setData([
                'error' => true,
                'message' => __('Invalid form key. Please refresh the page.')
            ])
        );
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): bool
    {
        $data = json_decode((string)$request->getContent(), true);
        $formKey = (is_array($data) ? $data['form_key'] ?? null : null) ?: $request->getParam('form_key');
        return $formKey !== null && hash_equals((string)$this->formKey->getFormKey(), (string)$formKey);
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

        $this->conversationManager->clear();

        return $result->setData([
            'success' => true,
            'message' => __('New conversation started.'),
        ]);
    }
}
