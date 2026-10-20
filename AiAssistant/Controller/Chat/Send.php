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
use Bgomesweb\AiAssistant\Model\Service\ChatProcessor;
use Bgomesweb\AiAssistant\Model\Service\ConversationManager;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Send implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private const int MAX_MESSAGE_LENGTH = 2000;

    private readonly RequestInterface $request;
    private readonly JsonFactory $jsonFactory;
    private readonly ChatProcessor $chatProcessor;
    private readonly AiConfig $aiConfig;
    private readonly StoreManagerInterface $storeManager;
    private readonly ConversationManager $conversationManager;
    private readonly FormKey $formKey;
    private readonly LoggerInterface $logger;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param ChatProcessor $chatProcessor
     * @param AiConfig $aiConfig
     * @param StoreManagerInterface $storeManager
     * @param ConversationManager $conversationManager
     * @param FormKey $formKey
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface      $request,
        JsonFactory           $jsonFactory,
        ChatProcessor         $chatProcessor,
        AiConfig              $aiConfig,
        StoreManagerInterface $storeManager,
        ConversationManager   $conversationManager,
        FormKey               $formKey,
        LoggerInterface       $logger
    )
    {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->chatProcessor = $chatProcessor;
        $this->aiConfig = $aiConfig;
        $this->storeManager = $storeManager;
        $this->conversationManager = $conversationManager;
        $this->formKey = $formKey;
        $this->logger = $logger;
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
     * @return Json|ResultInterface|ResponseInterface
     * @throws NoSuchEntityException
     */
    public function execute(): Json|ResultInterface|ResponseInterface
    {
        $result = $this->jsonFactory->create();

        $rawContent = (string)$this->request->getContent();
        $content = json_decode($rawContent, true);
        if (!is_array($content)) {
            return $result->setData([
                'error' => true,
                'status' => 400,
                'message' => __('Invalid request payload.'),
            ]);
        }

        $storeId = (int)$this->storeManager->getStore()->getId();

        if (!$this->aiConfig->isEnabled($storeId)) {
            return $result->setData([
                'error' => true,
                'message' => __('Virtual assistant disabled in this store.'),
            ]);
        }

        $rawMessage = trim((string)($content['message'] ?? ''));
        $truncated = mb_strlen($rawMessage) > self::MAX_MESSAGE_LENGTH;
        $userMessage = mb_substr($rawMessage, 0, self::MAX_MESSAGE_LENGTH);
        $topicContext = trim((string)($content['topic_context'] ?? '')) ?: null;
        $frontendTools = is_array($content['tools'] ?? null) ? $content['tools'] : [];
        $isSilent = !empty($content['silent']);
        $status = in_array($content['status'] ?? null, ['success', 'error'], true)
            ? $content['status']
            : null;

        if ($userMessage === '') {
            return $result->setData([
                'error' => true,
                'message' => __('Empty message.'),
            ]);
        }

        $products = [];
        $groups = [];
        $comparison = null;
        $responseText = '';
        $hadError = false;
        $response = null;

        try {
            $response = $this->chatProcessor->processMessage($userMessage, $storeId, $topicContext, $frontendTools);
            $groups = is_array($response['groups'] ?? null) ? array_values($response['groups']) : [];
            $products = is_array($response['products'] ?? null) ? $response['products'] : [];
            $comparison = is_array($response['comparison'] ?? null) ? $response['comparison'] : null;
            $responseText = (string)($response['message'] ?? '');
            $suggestedReplies = is_array($response['suggested_replies'] ?? null) ? $response['suggested_replies'] : [];
            $toolCalls = is_array($response['tool_calls'] ?? null) ? $response['tool_calls'] : [];

            $data = [
                'success' => true,
                'message' => $responseText,
                'products' => $products,
                'groups' => $groups,
                'suggested_replies' => $suggestedReplies,
            ];

            if ($truncated) {
                $data['warning'] = __('Message was truncated to %1 characters.', self::MAX_MESSAGE_LENGTH);
            }

            if (!empty($toolCalls)) {
                $data['tool_calls'] = $toolCalls;
            }

            return $result->setData($data);
        } catch (\Throwable $e) {
            $hadError = true;
            $this->logger->error('AiAssistant Chat Error: ' . $e->getMessage(), ['exception' => $e]);

            return $result->setData([
                'error' => true,
                'message' => __('Sorry, an error occurred while processing your message. Please try again.'),
                'suggested_replies' => [['label' => __('Try again')->render(), 'text' => __('Try again')->render()]],
            ]);
        } finally {
            $userExtraData = [];
            if ($isSilent && $status !== null) {
                $userExtraData['type'] = 'status';
                $userExtraData['status'] = $status;
            }
            $this->conversationManager->addUserMessage($userMessage, $topicContext, $userExtraData);

            if (!$hadError && $responseText !== '') {
                $extraData = $groups ? ['groups' => $groups] : [];
                $nativeTurns = is_array($response['native_turns'] ?? null)
                    ? $response['native_turns']
                    : [];
                if ($nativeTurns) {
                    $extraData['native_turns'] = $nativeTurns;
                }
                $this->conversationManager->addAssistantMessage(
                    $responseText,
                    $products,
                    $comparison,
                    $extraData
                );
            }
        }
    }
}
