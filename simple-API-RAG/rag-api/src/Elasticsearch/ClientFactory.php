<?php

namespace App\Elasticsearch;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;

class ClientFactory
{
    /**
     * @throws AuthenticationException
     */
    public static function create(string $url): Client
    {
        return ClientBuilder::create()
            ->setHosts([$url])
            ->build();
    }
}
