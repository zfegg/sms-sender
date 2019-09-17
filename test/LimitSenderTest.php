<?php

namespace ZfeggTest\SmsSender;

use Psr\SimpleCache\CacheInterface;
use Zfegg\SmsSender\LimitSender;
use PHPUnit\Framework\TestCase;

class LimitSenderTest extends SetUpContainer
{

    public function testSend()
    {

        $phoneNumber = '13000000000';
        $content     = 'Content';

        /** @var LimitSender $smsSender */
        $smsSender = $this->container->get(LimitSender::class);

        //Test send success
        $this->assertTrue($smsSender->send($phoneNumber, $content)->isOk());

        //Send wait times.
        $result = $smsSender->send($phoneNumber, $content);
        $this->assertFalse($result->isOk());
        $this->assertStringStartsWith('请等待', $result->getError());

        $smsSender->clearLock($phoneNumber);

        /** @var CacheInterface $cache */
        $cache = $this->container->get(CacheInterface::class);
        $cache->set($phoneNumber . 'Num', 20);

        $result = $smsSender->send($phoneNumber, $content);
        $this->assertFalse($result->isOk());
        $this->assertStringStartsWith('一个手机号每天只能发送', $result->getError());
    }

    public function testClearLock()
    {
        $phoneNumber = '13000000000';
        $content     = 'Content';

        /** @var LimitSender $smsSender */
        $smsSender = $this->container->get(LimitSender::class);

        $this->assertTrue($smsSender->send($phoneNumber, $content)->isOk());

        $smsSender->clearLock($phoneNumber);

        $this->assertTrue($smsSender->send($phoneNumber, $content)->isOk());
    }
}
