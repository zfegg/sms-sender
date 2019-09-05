<?php

namespace Zfegg\SmsSender\Provider;

use Zfegg\SmsSender\Result;

/**
 * Class ProviderInterface
 *
 * @package Zfegg\SmsSender\Provider
 */
interface ProviderInterface
{

    /**
     * @param string $phoneNumber
     * @param string $content
     * @return Result
     */
    public function send(string $phoneNumber, string $content): Result;
}
