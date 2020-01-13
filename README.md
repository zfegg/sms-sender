短信发送抽象接口
==============

[![Build Status](https://travis-ci.org/zfegg/sms-sender.png)](https://travis-ci.org/zfegg/sms-sender)
[![Coverage Status](https://coveralls.io/repos/github/zfegg/sms-sender/badge.svg?branch=master)](https://coveralls.io/github/zfegg/sms-sender?branch=master)
[![Latest Stable Version](https://poser.pugx.org/zfegg/sms-sender/v/stable.png)](https://packagist.org/packages/zfegg/sms-sender)

抽象常用短信业务:

1. 实现短信的限制发送（60s 内限制发送1次，1天上限发送10次）
2. 短信验证码生成与验证功能 

## Installation / 安装

使用 Composer 安装

~~~
$ composer require zfegg/sms-sender
~~~


## Interfaces / 接口说明

* `Zfegg/SmsSender/Provider/ProviderInterface` 短信供应商实现接口

## Usage / 使用

### 基本使用示例代码：

[示例代码](examples/basic.php)

### 在 Expressive 中使用：

在 `config/application.php` 中添加模块加载.

~~~php
return array(
    'modules' => array(
        //... Your modules
        'Zfegg/SmsSender'
    ),
);
~~~

添加短信发送配置 `module.config.php`

~~~php

return [
    'dependencies' => [
        'factories' => [
            ProviderInterface::class => YourSmsProviderFactory::class,
        ],
    ]
    'zfegg' => [
        LimitSender::class => [
            'provider' => ProviderInterface::class, // 设置短信商服务名. (可选), 默认 `ProviderInterface::class`
            'cache' => CacheInterface::class, // 设置缓存服务名 (可选), 默认 `CacheInterface::class`
            'day_send_times' => 10, // 设置每天发送次数上限 (可选), 默认10
            'waiting_time' => 60, //设置每次发送等待时长 (可选), 默认60s
        ],
        PostSmsCaptchaHandler::class  => [
            'types' => [
               'register' => 'Register captcha code: {code}',
               'login' => 'Login captcha code: {code}',
            ]
        ],
    ]
];
~~~

控制器使用：

~~~php

//发送验证码
$app->post('/api/send-sms-captcha', PostSmsCaptchaHandler::class);

//业务验证码验证
$app->post('/register', [
  function ($req, $handler) {
  
    //配置验证器
    $inputFilter = (new \Laminas\InputFilter\Factory)->create([
        [
            'name' => 'captcha',
            'validators' => [
                [
                    'name' => SmsCode::class,
                    'options' => [
                        'inputName' => 'phone',
                    ]
                ]
            ]
        ],
        [
            'name' => 'phone',
            'validators' => [
                [
                    'name' => 'PhoneNumber',
                ]
            ]
        ],
    ])
  
    if (! $inputFilter->isValid()) {
        //验证失败响应
        return new JsonResponse(['messages' => $inputFilter->getMessages()], 403);
    }

    //验证成功继续注册
    return $handler->handle($req);
  },
  YourRegisterHandler::class,
])
~~~
