<?php
namespace ZfeggTest\Listener;

use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventManager;
use Zfegg\SmsSender\Listener\LimitSendListener;
use Zfegg\SmsSender\SendResultEvent;

class LimitSendListenerTest extends TestCase
{

    public function testOnPreSend()
    {
        $cache = $this->getCache();
        $events = new EventManager();
        $listener = new LimitSendListener($cache);
        $listener->attach($events);

        $e = new SendResultEvent(SendResultEvent::EVENT_PRE_SEND);
        //Test success
        $e->setPhoneNumber('15000000000');
        $e->setContent('testContent');

        //Test ignore.
        $events->triggerEvent($e);
        $this->assertFalse((bool)$e->getError());

        $e->setParam(LimitSendListener::FUNCTION_NAME, true);

        //Test waiting time limit.
        $e->setError(null);
        $cache->set($e->getPhoneNumber(), time()-10);
        $events->triggerEvent($e);
        $this->assertTrue((bool)$e->getError());

        //Test waiting time limit 2.
        $e->setError(null);
        $cache->set($e->getPhoneNumber(), time()-61);
        $events->triggerEvent($e);
        $this->assertFalse((bool)$e->getError(), $e->getError() . '');

        //Test day send times
        $e->setError(null);
        $listener->clearLock($e->getPhoneNumber());
        $cache->set($e->getPhoneNumber() . 'Times', 20);
        $events->triggerEvent($e);
        $this->assertTrue((bool)$e->getError());
    }

    public function testClear()
    {
        $cache = $this->getCache();
        $phoneNumber = '15000000000';
        $events = new EventManager();
        $listener = new LimitSendListener($cache);
        $listener->attach($events);

        $e = new SendResultEvent(SendResultEvent::EVENT_PRE_SEND);
        $e->setPhoneNumber($phoneNumber);
        $e->setContent('testContent');
        $events->triggerEvent($e);

        $listener->clearLock($phoneNumber);
        $this->assertNull($cache->get($phoneNumber));
    }

    public function getCache()
    {
        return new ArrayCachePool();
    }
}
