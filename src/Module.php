<?php

namespace Zfegg\SmsSender;

class Module
{

    public function getConfig()
    {
        $config = (new ConfigProvider())();
        $config['service_manager'] = $config['dependencies'];
        unset($config['dependencies']);

        return $config;
    }
}
