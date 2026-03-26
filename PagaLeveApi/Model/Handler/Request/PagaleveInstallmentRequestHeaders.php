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

namespace Bgomesweb\PagaLeveApi\Model\Handler\Request;

use Bgomesweb\CoreApi\Api\Command\Http\Request\RequestInterface;
use Bgomesweb\CoreApi\Api\CredentialRepositoryInterface;
use Bgomesweb\CoreApi\Api\Handler\Request\HeadersInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class PagaleveInstallmentRequestHeaders implements HeadersInterface
{
    /**
     * @param CredentialRepositoryInterface $credentialRepository
     * @param RequestInterface $request
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly CredentialRepositoryInterface $credentialRepository,
        private readonly RequestInterface $request,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(): array
    {
        $credential = $this->credentialRepository->getByIdentifier('pagaleve_authorization');
        $token = '';
        $currentTimestamp = $this->timezone->date()->getTimestamp();

        if (!$credential) {
            $token = $this->request->execute();
        }

        if ($credential && $currentTimestamp >= strtotime($credential->getExpiredAt())) {
            $token = $this->request->execute();
        }

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $token ?? $credential?->getCredential()
        ];
    }
}
