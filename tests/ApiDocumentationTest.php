<?php

namespace Dedoc\Scramble\Tests;

use Dedoc\Scramble\Tests\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiDocumentationTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    public function testJsonDocsEndpointReturnsSuccessfulResponse()
    {
        $client = static::createClient();

        $client->request('GET', '/docs/api.json');

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
    }
}
