<?php

declare(strict_types=1);

/*
 * This file is part of the Akeneo PIM Enterprise Edition.
 *
 * (c) 2024 Akeneo SAS (https://www.akeneo.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PimUpsertProductTool;

use Akeneo\Pim\ApiClient\AkeneoPimClient;
use Akeneo\Pim\ApiClient\AkeneoPimClientBuilder;

final class ClientFactory
{
    public static function build(): AkeneoPimClient
    {
        $host = $_ENV['HOST'];
        $clientId = $_ENV['CLIENT_ID'];
        $secret = $_ENV['CLIENT_SECRET'];
        $username = $_ENV['USERNAME'];
        $password = $_ENV['PASSWORD'];
        $builder = new AkeneoPimClientBuilder($host);

        return $builder->buildAuthenticatedByPassword($clientId, $secret, $username, $password);
    }
}
