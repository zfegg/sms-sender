<?php

namespace Zfegg\SmsSender;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zfegg\SmsSender\Provider\ProviderInterface;

/**
 * Class Sms
 *
 * @package Zfegg\Service
 */
class SmsSender implements EventManagerAwareInterface
{
    use EventManagerAwareTrait;

    /**
     * @var SmsEvent
     */
    protected $event;

    /**
     * SmsEvent prototype
     * @var SmsEvent
     */
    protected $eventPrototype;

    /** @var ProviderInterface  */
    protected $provider;

    public function __construct(ProviderInterface $provider)
    {
        $this->setProvider($provider);
        $this->eventPrototype = new SmsEvent(null, $this);
    }

    /**
     * Send SMS
     *
     * @param String $phoneNumber
     * @param String $content
     *
     * @return bool
     */
    public function send($phoneNumber, $content)
    {
        $event = $this->event = clone $this->eventPrototype;
        $event->setPhoneNumber($phoneNumber);
        $event->setContent($content);
        $event->setTarget($this);

        /** @var \Zend\EventManager\EventManager $events */
        $events = $this->getEventManager();

        $event->setName(SmsEvent::EVENT_PRE_SEND);
        $events->triggerEvent($event);

        if ($event->getError()) {
            $event->setName(SmsEvent::EVENT_INVALID);
            $events->triggerEvent($event);
            return false;
        }

        try {
            $result = $this->getProvider()->send($phoneNumber, $content);
            $event->setParam('result', $result);
            $event->setName(SmsEvent::EVENT_POST_SEND);
            $events->triggerEvent($event);
            return true;
        } catch (\Exception $e) {
            $event->setParam('exception', $e);
            $event->setError($e->getMessage());
            $event->setName(SmsEvent::EVENT_INVALID);
            $events->triggerEvent($event);
            return false;
        }
    }

    /**
     * Limit send SMS
     *
     * @param $phoneNumber
     * @param $message
     *
     * @return bool
     */
    public function limitSend($phoneNumber, $message)
    {
        $this->eventPrototype->setParam('__FUNCTION__', __FUNCTION__);
        $result = $this->send($phoneNumber, $message);
        $this->eventPrototype->setParam('__FUNCTION__', null);

        return $result;
    }

    /**
     * Get SMS event
     * @return SmsEvent
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return ProviderInterface
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param ProviderInterface $provider
     * @return $this
     */
    public function setProvider(ProviderInterface $provider)
    {
        $this->provider = $provider;
        return $this;
    }
}
