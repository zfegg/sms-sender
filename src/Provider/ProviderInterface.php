<?php

namespace Zfegg\SmsSender\Provider;

/**
 * Class ProviderInterface
 *
 * @package Zfegg\SmsSender\Provider
 */
interface ProviderInterface
{

    /**
     * @param string $phoneNumber
     * @param $content
     * @return mixed
     */
    public function send($phoneNumber, $content);
}
