<?php
namespace ZfeggTest\Listener;

use Zend\Cache\StorageFactory;
use Zend\EventManager\EventManager;
use Zfegg\SmsSender\Listener\LimitSendListener;
use Zfegg\SmsSender\SmsEvent;

class LimitSendListenerTest extends \PHPUnit_Framework_TestCase
{

    public function testOnPreSend()
    {
        $events = new EventManager();
        $listener = new LimitSendListener([
            'cache' => $cache = $this->getCache(),
        ]);
        $listener->attach($events);

        $e = new SmsEvent(SmsEvent::EVENT_PRE_SEND);
        //Test success
        $e->setPhoneNumber(15000000000);
        $e->setContent('testContent');

        //Test ignore.
        $events->triggerEvent($e);
        $this->assertFalse((bool)$e->getError());

        $e->setParam(LimitSendListener::FUNCTION_NAME, true);

        //Test waiting time limit.
        $e->setError(null);
        $cache->setItem($e->getPhoneNumber(), time()-10);
        $events->triggerEvent($e);
        $this->assertTrue((bool)$e->getError());

        //Test waiting time limit 2.
        $e->setError(null);
        $cache->setItem($e->getPhoneNumber(), time()-61);
        $events->triggerEvent($e);
        $this->assertFalse((bool)$e->getError());

        //Test day send times
        $e->setError(null);
        $listener->clearLock($e->getPhoneNumber());
        $cache->setItem($e->getPhoneNumber() . 'Times', 20);
        $events->triggerEvent($e);
        $this->assertTrue((bool)$e->getError());
    }

    public function testClear()
    {
        $phoneNumber = '15000000000';
        $events = new EventManager();
        $listener = new LimitSendListener([
            'cache' => $cache = $this->getCache(),
        ]);
        $listener->attach($events);

        $e = new SmsEvent(SmsEvent::EVENT_PRE_SEND);
        $e->setPhoneNumber($phoneNumber);
        $e->setContent('testContent');
        $events->triggerEvent($e);

        $listener->clearLock($phoneNumber);
        $this->assertNull($listener->getCache()->getItem($phoneNumber));
    }

    public function getCache()
    {
        return StorageFactory::factory([
            'adapter' => 'Memory',
            'options' => [
                'namespace' => 'Sms',
            ],
        ]);
    }
}
