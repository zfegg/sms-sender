<?php

namespace ZfeggTest\SmsSender\Event;

use PHPUnit\Framework\TestCase;
use Zfegg\SmsSender\Event\PreSendEvent;
use Zfegg\SmsSender\Result;

class PreSendEventTest extends TestCase
{

    public function testNew()
    {
        $e = new PreSendEvent('15000000000', 'test');

        $this->assertEquals('15000000000', $e->getPhoneNumber());
        $this->assertEquals('test', $e->getContent());

        $e->setPhoneNumber('13000000000');
        $this->assertEquals('13000000000', $e->getPhoneNumber());

        $e->setContent('test2');
        $this->assertEquals('test2', $e->getContent());

        $e->setResult(new Result());
        $this->assertTrue($e->getResult()->isOk());
    }
}
