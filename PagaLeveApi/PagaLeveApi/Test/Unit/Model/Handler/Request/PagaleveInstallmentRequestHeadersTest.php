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

namespace Bgomesweb\PagaLeveApi\Test\Unit\Model\Handler\Request;

use Bgomesweb\PagaLeveApi\Model\Handler\Request\PagaleveInstallmentRequestHeaders;
use Bgomesweb\CoreApi\Api\Command\Http\Request\RequestInterface;
use Bgomesweb\CoreApi\Api\CredentialRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\TestCase;

class PagaleveInstallmentRequestHeadersTest extends TestCase
{
    /** @var CredentialRepositoryInterface */
    private $credentialRepositoryMock;

    /** @var RequestInterface */
    private $requestMock;

    /** @var TimezoneInterface */
    private $timezoneMock;

    /** @var PagaleveInstallmentRequestHeaders */
    private $headersHandler;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->credentialRepositoryMock = $this->createMock(CredentialRepositoryInterface::class);
        $this->requestMock = $this->createMock(RequestInterface::class);
        $this->timezoneMock = $this->createMock(TimezoneInterface::class);

        $this->headersHandler = new PagaleveInstallmentRequestHeaders(
            $this->credentialRepositoryMock,
            $this->requestMock,
            $this->timezoneMock
        );
    }

    /**
     * Test handling of headers when no credentials exist or are expired.
     *
     * @return void
     */
    public function testHandleWhenNoCredentialOrExpired(): void
    {
        $this->credentialRepositoryMock->method('getByIdentifier')
            ->willReturn(null);

        $this->requestMock->method('execute')
            ->willReturn('newToken');

        $this->timezoneMock->method('date')
            ->willReturn(new \DateTimeImmutable('2024-12-19'));

        $result = $this->headersHandler->handle();

        $this->assertSame([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer newToken'
        ], $result);
    }

    /**
     * Test handling of headers when valid credentials exist.
     *
     * @return void
     */
    public function testHandleWithValidCredential(): void
    {
        $credentialMock = $this->createMock(\Bgomesweb\CoreApi\Api\CredentialInterface::class);
        $credentialMock->method('getCredential')
            ->willReturn('validToken');
        $credentialMock->method('getExpiredAt')
            ->willReturn('2024-12-19 23:59:59');

        $this->credentialRepositoryMock->method('getByIdentifier')
            ->willReturn($credentialMock);

        $this->requestMock->method('execute')
            ->willReturn('newToken');

        $this->timezoneMock->method('date')
            ->willReturn(new \DateTimeImmutable('2024-12-19'));

        $result = $this->headersHandler->handle();

        $this->assertSame([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer validToken'
        ], $result);
    }
}
