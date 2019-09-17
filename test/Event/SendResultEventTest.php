<?php

namespace ZfeggTest\SmsSender\Event;

use Zfegg\SmsSender\Event\SendResultEvent;
use PHPUnit\Framework\TestCase;
use Zfegg\SmsSender\Result;

class SendResultEventTest extends TestCase
{

    public function testNew()
    {
        $rs = new Result();
        $e = new SendResultEvent('15000000000', 'test', $rs);

        $this->assertEquals('15000000000', $e->getPhoneNumber());
        $this->assertEquals('test', $e->getContent());
        $this->assertEquals($rs, $e->getResult());
    }
}
