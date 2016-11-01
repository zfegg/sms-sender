<?php

namespace ZfeggTest\SmsSender;

use Zend\Captcha\Dumb;
use Zend\ServiceManager\ServiceManager;
use Zfegg\SmsSender\Listener\LimitSendListener;
use Zfegg\SmsSender\Listener\ValidatorListener;
use Zfegg\SmsSender\Module;
use Zfegg\SmsSender\Provider\ProviderInterface;
use Zfegg\SmsSender\SmsEvent;
use Zfegg\SmsSender\SmsSender;
use ZfeggTest\SmsSender\Provider\ExampleProvider;

class SmsSenderTest extends \PHPUnit_Framework_TestCase
{
    /** @var  SmsSender */
    protected $smsSender;

    public function setUp()
    {
    }

    /**
     * @param null $exception
     * @return ProviderInterface
     */
    public function getProviderMock($exception = null)
    {
        $provider = $this->createMock(ProviderInterface::class);

        $method = $provider->method('send');
        $exception && $method->willThrowException($exception);

        return $provider;
    }

    public function testSend()
    {
        $phoneNumber = '13000000000';
        $content     = 'Content';

        $provider  = $this->getProviderMock();
        $smsSender = new SmsSender($provider);
        $events = $smsSender->getEventManager();

        //Test send success
        $events->attach(SmsEvent::EVENT_POST_SEND, function (SmsEvent $event) use ($phoneNumber) {
            $this->assertEquals($phoneNumber, $event->getPhoneNumber());
        });
        $this->assertTrue($smsSender->send($phoneNumber, $content));


        $invalidEventName = '';
        $invalidCall      = function (SmsEvent $event) use (&$invalidEventName) {
            $this->assertEquals($invalidEventName, $event->getError());
        };
        $events->attach(SmsEvent::EVENT_INVALID, $invalidCall);


        //Assert pre send invalid
        $invalidEventName = SmsEvent::EVENT_PRE_SEND;
        $callback         = function (SmsEvent $event) {
            $event->setError(SmsEvent::EVENT_PRE_SEND);
        };
        $events->attach(SmsEvent::EVENT_PRE_SEND, $callback);
        $this->assertFalse($smsSender->send($phoneNumber, $content));
        $events->detach($callback, SmsEvent::EVENT_PRE_SEND);

        //Provide send failure
        $invalidEventName = 'ThrowMessage';
        $smsSender->getEvent()->setError(null);
        $smsSender->setProvider($this->getProviderMock(new \Exception('ThrowMessage')));
        $this->assertFalse($smsSender->send($phoneNumber, $content));
    }

    public function testExample()
    {
        $config = (new Module())->getConfig()['service_manager'];
        $sm     = new ServiceManager($config);
        //$sm     = new ServiceManager(new Config($config)); //v2.7
        $sm->setService(
            'config',
            [
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
                                    'options' => [
                                        'namespace' => 'Sms',
                                    ],
                                ]
                            ]
                        ],
                    ]
                ]
            ]
        );

        /** @var SmsSender $smsSender */
        $smsSender = $sm->get(SmsSender::class);
        $this->assertInstanceOf(SmsSender::class, $smsSender);
        $this->assertTrue($smsSender->send(15000000000, 'content'));
        $this->assertFalse($smsSender->send('error', 'content'));
        $this->assertTrue($smsSender->limitSend('15000000000', 'content'));
        $this->assertFalse($smsSender->limitSend('15000000000', 'content'));
    }
}
