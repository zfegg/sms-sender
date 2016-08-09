<?php

namespace ZfeggTest\SmsSender\Provider;

use Zfegg\SmsSender\Provider\ProviderInterface;

/**
 * Class ExampleProvider
 *
 * @package ZfeggTest\Provider
 */
class ExampleProvider implements ProviderInterface
{

    public function send($phoneNumber, $content)
    {
        return true;
    }
}
