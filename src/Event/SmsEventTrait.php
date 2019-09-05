<?php


namespace Zfegg\SmsSender\Event;


use Zfegg\SmsSender\Result;

trait SmsEventTrait
{
    protected $phoneNumber;

    protected $content;

    protected $result;

    /**
     * Get a content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set a content
     *
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
     * @return Result
     */
    public function getResult(): ?Result
    {
        return $this->result;
    }

    /**
     * @param Result $result
     */
    public function setResult(Result $result): void
    {
        $this->result = $result;
    }
}
