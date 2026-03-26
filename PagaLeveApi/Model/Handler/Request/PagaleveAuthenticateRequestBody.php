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

use Bgomesweb\PagaLeveApi\Helper\Data;
use Bgomesweb\CoreApi\Api\Handler\Request\BodyInterface;

class PagaleveAuthenticateRequestBody implements BodyInterface
{
    /**
     * @param Data $config
     */
    public function __construct(
        private readonly Data $config
    ) {
    }

    /**
     * @inheritDoc
     */
    public function handle(): array
    {
        return [
            "base_uri" => $this->config->getPagaLeveBaseUrl(),
            "json" => [
                "username" => $this->config->getUsername(),
                "password" => $this->config->getPassword()
            ]
        ];
    }
}
