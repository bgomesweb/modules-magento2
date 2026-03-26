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

use PHPUnit\Framework\TestCase;
use Bgomesweb\PagaLeveApi\Model\Handler\Request\PagaleveAuthenticateRequestBody;
use Bgomesweb\CoreApi\Api\Config\ConfigInterface;

class PagaleveAuthenticateRequestBodyTest extends TestCase
{
    /** @var ConfigInterface */
    private ConfigInterface $configMock;

    /** @var PagaleveAuthenticateRequestBody */
    private PagaleveAuthenticateRequestBody $pagaleveAuthenticateRequestBody;

    /**
     * Set Up
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(ConfigInterface::class);
        $this->pagaleveAuthenticateRequestBody = new PagaleveAuthenticateRequestBody($this->configMock);
    }

    /**
     * Tests handle() in sandbox mode.
     *
     * @return void
     */
    public function testHandleForSandboxEnvironment(): void
    {
        $this->configMock->method('getValue')
            ->willReturnMap([
                ['environment', null, true],
                ['base_url_test', null, 'https://sandbox.example.com'],
                ['credentials', 'username', 'sandbox_user'],
                ['credentials', 'password', 'sandbox_password'],
            ]);

        $result = $this->pagaleveAuthenticateRequestBody->handle();

        $this->assertSame([
            "base_uri" => 'https://sandbox.example.com',
            "json" => [
                "username" => 'sandbox_user',
                "password" => 'sandbox_password',
            ]
        ], $result);
    }

    /**
     * Tests handle() in production mode.
     *
     * @return void
     */
    public function testHandleForProductionEnvironment(): void
    {
        $this->configMock->method('getValue')
            ->willReturnMap([
                ['environment', null, false],
                ['base_url', null, 'https://prod.example.com'],
                ['credentials', 'username', 'prod_user'],
                ['credentials', 'password', 'prod_password'],
            ]);

        $result = $this->pagaleveAuthenticateRequestBody->handle();

        $this->assertSame([
            "base_uri" => 'https://prod.example.com',
            "json" => [
                "username" => 'prod_user',
                "password" => 'prod_password',
            ]
        ], $result);
    }
}
