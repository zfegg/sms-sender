<?php

namespace Zfegg\SmsSender;

/**
 * Class Module
 *
 * @package Zfegg\SmsSender
 */
class Module
{

    public function getConfig()
    {
        return include __DIR__  . '/../config/module.config.php';
    }
}
