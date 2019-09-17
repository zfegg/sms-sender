<?php
namespace Zfegg\SmsSender\Event;

use Zfegg\SmsSender\Result;

class SendResultEvent
{
    use SmsEventTrait;

    public function __construct(string $phoneNumber, string $content, Result $result)
    {
        $this->phoneNumber = $phoneNumber;
        $this->content = $content;
        $this->result = $result;
    }
}
