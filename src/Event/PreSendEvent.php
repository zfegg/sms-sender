<?php


namespace Zfegg\SmsSender\Event;


class PreSendEvent
{
    use SmsEventTrait;

    public function __construct(string $phoneNumber, string $content)
    {
        $this->phoneNumber = $phoneNumber;
        $this->content = $content;
    }
}
