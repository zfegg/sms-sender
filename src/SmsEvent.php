<?php

namespace Zfegg\SmsSender;

use Zend\EventManager\Event;

/**
 * Class SmsEvent
 *
 * @package Zfegg\SmsSender
 */
class SmsEvent extends Event
{
    const EVENT_PRE_SEND  = 'sms.send.pre';
    const EVENT_POST_SEND = 'sms.send.post';
    const EVENT_INVALID   = 'sms.invalid';

    protected $phoneNumber, $content, $error;

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string $error
     * @return $this
     */
    public function setError($error)
    {
        $this->error = $error;
        return $this;
    }
}
