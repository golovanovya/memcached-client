<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testGetSetValue()
    {
        $client = new Client('localhost', 11211);
        $client->set("key1", "val1");
        $this->assertEquals("val1", $client->get("key1"));
    }

    public function testGetNotSaved()
    {
        $client = new Client('localhost', 11211);
        $this->assertNull($client->get("key2"));
        $this->assertEquals("test", $client->get("key2", fn () => "test"));
    }
}
