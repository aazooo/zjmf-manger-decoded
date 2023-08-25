<?php

namespace think\tests;

use PHPUnit\Framework\TestCase;
use think\api\Client;

class ClientTest extends TestCase
{
    public function testApi()
    {
        $client = new Client('AppCode');

        $result = $client->calendarMonth()
            ->withYearMonth('2015-1')
            ->request();

        var_dump($result);
    }
}
