<?php
namespace ZfeggTest\Listener;

use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventManager;
use Zend\InputFilter\InputFilter;
use Zfegg\SmsSender\Listener\ValidatorListener;
use Zfegg\SmsSender\SendResultEvent;

class ValidatorListenerTest extends TestCase
{

    public function testOnValid()
    {
        $events = new EventManager();
        $listener = new ValidatorListener();
        $listener->attach($events);

        $e = new SendResultEvent(SendResultEvent::EVENT_PRE_SEND);

        //Test success
        $e->setPhoneNumber(15000000000);
        $e->setContent('testContent');
        $events->triggerEvent($e);
        $this->assertFalse((bool)$e->getError());

        //Test error.
        $e->setPhoneNumber('x000000000');
        $events->triggerEvent($e);
        $this->assertTrue((bool)$e->getError());
        $this->assertInstanceOf(InputFilter::class, $e->getParam('InputFilter'));

//        var_dump($e->getParam('InputFilter')->getMessages());
    }
}
