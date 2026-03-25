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

namespace Bgomesweb\AffiliateRakuten\Controller\Gateway;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Psr\Log\LoggerInterface;
use Exception;

class Index extends Action implements HttpGetActionInterface
{
    /**
     * Injects dependencies for managing cookies, logging, and HTTP responses.
     *
     * @param Context $context
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param Http $response
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly Http $response,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Handles Rakuten tracking parameters, logs access, sets cookies, and redirects the user.
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $request = $this->getRequest();

        $ranSiteID = $request->getParam('ranSiteID');
        $ranMID = $request->getParam('ranMID');
        $url = $request->getParam('url');

        $this->logger->debug('Rakuten Gateway Accessed', [
            'ranSiteID' => $ranSiteID ? 'set' : 'missing',
            'ranMID' => $ranMID ?: 'missing',
            'url' => $url ? 'set' : 'missing'
        ]);

        if ($ranSiteID) {
            $this->_setRakutenCookie($ranMID, $ranSiteID);
        }

        return $this->_redirectUser($url);
    }

    /**
     * Set Rakuten server-side cookie
     *
     * @param string|null $ranMID
     * @param string $ranSiteID
     * @return void
     */
    private function _setRakutenCookie(?string $ranMID, string $ranSiteID): void
    {
        try {
            $landingTimestamp = gmdate('Ymd_His');

            if (!$this->isValidRanSiteID($ranSiteID)) {
                $this->logger->warning('Rakuten: Invalid ranSiteID format: ' . $ranSiteID);
                return;
            }

            $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
                ->setDuration(2 * 365 * 24 * 60 * 60)
                ->setPath('/')
                ->setHttpOnly(false)
                ->setSameSite('Lax');

            $cookieValue = http_build_query([
                'amid' => $ranMID,
                'atrv' => $ranSiteID,
                'ald' => $landingTimestamp
            ]);

            $this->cookieManager->setPublicCookie(
                'rmStoreGateway',
                $cookieValue,
                $metadata
            );

            $cookiesToSet = [
                'amid' => $ranMID,
                'atrv' => $ranSiteID,
                'ald' => $landingTimestamp
            ];

            foreach ($cookiesToSet as $name => $value) {
                if ($value) {
                    $this->cookieManager->setPublicCookie($name, $value, $metadata);
                }
            }

            $this->logger->debug('Rakuten Cookies Set Successfully', [
                'ranSiteID' => $ranSiteID,
                'ranMID' => $ranMID,
                'timestamp' => $landingTimestamp
            ]);

        } catch (Exception $e) {
            $this->logger->error('Rakuten cookie setting error: ' . $e->getMessage());
        }
    }

    /**
     * Redirect user based on URL parameter
     *
     * @param string|null $url
     * @return \Magento\Framework\App\ResponseInterface
     */
    private function _redirectUser(?string $url)
    {
        if ($url) {
            $decodedUrl = urldecode($url);
            $this->logger->debug('Rakuten Redirecting to: ' . $decodedUrl);
            return $this->_redirect($decodedUrl);
        } else {
            $this->logger->debug('Rakuten Redirecting to home');
            return $this->_redirect('');
        }
    }

    /**
     * Validate ranSiteID format according to Rakuten specifications
     *
     * @param string $ranSiteID
     * @return bool
     */
    private function isValidRanSiteID(string $ranSiteID): bool
    {
        $pattern = '/^[a-zA-Z0-9\-._\/*]{34}$/';
        return preg_match($pattern, $ranSiteID) === 1;
    }
}
