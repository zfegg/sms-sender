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

## Usage / 使用

### 短信发送

#### 基本使用：

~~~php

use Zfegg\SmsSender\Listener\LimitSendListener;
use Zfegg\SmsSender\Listener\ValidatorListener;
use Zfegg\SmsSender\Provider\ProviderInterface;
use Zfegg\SmsSender\SmsSender;

require __DIR__ . '/../vendor/autoload.php';

class ExampleProvider implements ProviderInterface
{
    public function __construct(array $options)
    {

    }

    /**
     * @param $phoneNumber
     * @param $content
     * @return mixed
     */
    public function send($phoneNumber, $content)
    {
        if ($content == 'send error test') {
            throw new \RuntimeException('An exception.');
        }

        return true;
    }
}

$smsSender = new SmsSender(new ExampleProvider(['app_id' => 'test']));

$result = $smsSender->send('13000000000', 'TestContent');
var_dump($result); //bool(true)

$result = $smsSender->send('13000000000', 'send error test');
var_dump(
    $result,                            //bool(false)
    $smsSender->getEvent()->getError(), //'An exception.'
    get_class($smsSender->getEvent()->getParam('exception'))  //RuntimeException
);


//Usage ValidatorListener
$validatorListener = new ValidatorListener();
$validatorListener->attach($smsSender->getEventManager());

$result = $smsSender->send('ErrorPhoneNumberFormat', 'TestContent');
var_dump(
    $result,                           //bool(false)
    $smsSender->getEvent()->getError() //"The input does not match a phone number format"
);


//Usage LimitSendListener
$limitSendListener = new LimitSendListener([
    'waitingTime' => 1,  //每个号码每秒限发送一次
    'cache'          => [
        'adapter' => 'Memory',
    ]
]);
$limitSendListener->attach($smsSender->getEventManager());

$result = $smsSender->limitSend('13000000000', 'TestContent');
var_dump($result); //bool(true)

$result = $smsSender->limitSend('13000000000', 'TestContent');
var_dump(
    $result, //bool(false)
    $smsSender->getEvent()->getError() //请等待1秒后在试
);
sleep(2);
$result = $smsSender->limitSend('13000000000', 'TestContent');
var_dump($result); //bool(true)

~~~

#### 在zend-mvc 中使用：

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
use Zfegg\SmsSender\Listener\LimitSendListener;
use Zfegg\SmsSender\Listener\ValidatorListener;

return [
'sms_sender_config' => [
    'provider'  => [
        'name'    => ExampleProvider::class,
        'options' => [
        ]
    ],
    'listeners' => [
        [
            'name'    => ValidatorListener::class,
            'options' => [
                [
                    'name'       => 'phoneNumber',
                    'filters'    => [
                        ['name' => 'StringTrim']
                    ],
                    'validators' => [
                        [
                            'name' => 'PhoneNumber',
                        ]
                    ],
                ],
                [
                    'name'       => 'content',
                    'filters'    => [
                        ['name' => 'StringTrim']
                    ],
                    'validators' => [
                        [
                            'name'    => 'StringLength',
                            'options' => [
                                'max' => 255
                            ]
                        ]
                    ]
                ]
            ],
        ],
        [
            'name'    => LimitSendListener::class,
            'options' => [
                'waiting_time'   => 10,
                'day_send_times' => 5,
                'cache'          => [
                    'adapter' => 'Memory',
                    'options' => array(
                        'namespace' => 'Sms',
                    ),
                ]
            ]
        ],
    ]
]
];
~~~

控制器使用：

~~~php
class SendSmsController extend AbstractActionController
    protected $smsSender;
    public function __construct(SmsSender $smsSender) 
    {
        $this->smsSender = $smsSender;
    }
     
    public function sendAction() {
        $this->smsSender->send('13000000000', 'TestContent');
    }
}
~~~

### 短信验证码

在`module.config.php` 中 `InputFilter` 配置

~~~php
use Zfegg\SmsSender\Captcha\SmsCode;

return [
    'caches' => [
        'SmsCache' => [
            'adapter' => 'Memcache'
        ]
    ],
    'input_filter_specs' => [
        'test' => [
            [
                'name' => 'captcha',
                'validators' => [
                    [
                        'name' => SmsCode::class,
                        'options' => [
                            'input_name' => 'phone',
                            'cache' => 'SmsCache'
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
        ]
    ]
];
~~~

~~~php
use Zfegg\SmsSender\Captcha\SmsCode;

class SendSmsController extend AbstractActionController
    protected $validators;
    protected $smsSender;
    protected $inputFilters;
    public function __construct(SmsSender $smsSender, 
                                ValidatorManager $validators,
                                InputFilterPluginManager $inputFilters) 
    {
        $this->smsSender = $smsSender;
        $this->validators = $validators;
        $this->inputFilters = $inputFilters;
    }
     
    public function sendAction() {
        $smsCode = $this->validators->get(SmsCode::class, [
           'cache' => 'SmsCache'
        ]);

        $this->smsSender->send('13000000000', 'TestCaptcha: ' . $smsCode);
    }
    
    public function validationAction()
    {
        $inputFilter = $this->inputFilters->get('test');
        $inputFilter->setData($_POST);
        var_dump($inputFilter->isValid()); //验证
    }
}
~~~

## Interfaces / 接口说明

* `Zfegg/SmsSender/Provider/ProviderInterface` 短信供应商实现接口