<?php

namespace ZfeggTest\SmsSender\Provider;

use Zfegg\SmsSender\Provider\NullProvider;
use PHPUnit\Framework\TestCase;

class NullProviderTest extends TestCase
{

    public function testSend()
    {
        $this->assertTrue(
            (new NullProvider())->send('13000000000', '123')->isOk()
        );
    }
}
