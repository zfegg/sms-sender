<?php


namespace Zfegg\SmsSender\Provider;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class NullProvider implements ProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    public function send($phoneNumber, $content)
    {
        $this->logger->info("Send SMS: $phoneNumber ($content)");

        return true;
    }
}
