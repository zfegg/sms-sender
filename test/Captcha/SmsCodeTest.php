<?php
namespace ZfeggTest\Captcha;

use Zend\Cache\StorageFactory;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\ConfigProvider;
use Zfegg\SmsSender\Captcha\SmsCode;
use Zfegg\SmsSender\Module;

class SmsCodeTest extends \PHPUnit_Framework_TestCase
{

    public function testGenerate()
    {
        $wordlen = 6;
        $smsCode = $this->getSmsCode($wordlen);

        $code = $smsCode->generate('15000000000');

        $this->assertEquals($wordlen, strlen($code[0]));
        $this->assertTrue(is_numeric($code[0]));
        $this->assertTrue(is_numeric($code[1]));
    }

    public function testIsValid()
    {
        $phoneNumber = '15000000000';
        $smsCode     = $this->getSmsCode();
        $smsCode->setInputName('phone');
        $smsCode->setPhoneNumber($phoneNumber);
        $code = $smsCode->generate();

        $this->assertTrue(
            $smsCode->isValid(
                $code[0],
                [
                'phone' => $phoneNumber,
                ]
            )
        );
        //Invalid second
        $this->assertFalse(
            $smsCode->isValid(
                $code[0],
                [
                'phone' => $phoneNumber,
                ]
            )
        );

        //Test valid failure
        $smsCode->generate();
        $this->assertFalse(
            $smsCode->isValid(
                'fail',
                [
                'phone' => $phoneNumber,
                ]
            )
        );
        $this->assertEquals(2, $smsCode->getAllowValidationTimes());
        $this->assertFalse(
            $smsCode->isValid(
                'fail',
                [
                'phone' => $phoneNumber,
                ]
            )
        );
        $this->assertEquals(1, $smsCode->getAllowValidationTimes());
        $this->assertFalse(
            $smsCode->isValid(
                'fail',
                [
                'phone' => $phoneNumber,
                ]
            )
        );
        $this->assertEquals(0, $smsCode->getAllowValidationTimes());
    }

    /**
     * Test factory
     */
    public function testFactory()
    {
        $configs  = (new Module())->getConfig();
        $smConfig = $configs['service_manager'];
        $smConfig = ArrayUtils::merge($smConfig, (new ConfigProvider())->getDependencyConfig());
        $sm       = new ServiceManager($smConfig);
        $sm->setService('TestCache', StorageFactory::factory($this->getCacheConfig()));

        /** @var \Zend\Validator\ValidatorPluginManager $validators */
        $validators = $sm->get('ValidatorManager');
        $validators->configure($configs['validators']);
        /*
         $sm       = new ServiceManager(new Config($smConfig));
        $sm->setService('TestCache', StorageFactory::factory($this->getCacheConfig()));

        $validators = $sm->get('ValidatorManager');
        foreach ($configs['validators']['factories'] as $name => $factory) {
            $validators->setFactory($name, $factory);
        }
         */

        /** @var SmsCode $validator */
        $validator = $validators->get(SmsCode::class, ['cache' => 'TestCache']);
        $this->assertInstanceOf(SmsCode::class, $validator);

        /** @var SmsCode $validator */
        $validator = $validators->get(SmsCode::class, ['cache' => $this->getCacheConfig()]);
        $this->assertInstanceOf(SmsCode::class, $validator);
    }

    public function getSmsCode($wordlen = 4)
    {
        return new SmsCode(
            [
                'wordlen' => $wordlen,
                'cache'   => $this->getCacheConfig()
            ]
        );
    }

    private function getCacheConfig()
    {
        return [
            'adapter' => 'Memory',
            'options' => [
                'namespace' => 'Sms',
            ],
        ];
    }
}
