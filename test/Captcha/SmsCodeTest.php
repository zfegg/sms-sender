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

        $code2 = $smsCode->generate('15000000000');
        $this->assertEquals($code[0], $code2[0]);
    }

    public function isValidData()
    {
        $smsCode = $this->getSmsCode();

        return [
            'SuccessWithGetPhoneNumber' => [
                $this->getSmsCode(),
                '15000000000',
                [],
                true,
                true,
            ],
            'SuccessContext' => [
                $smsCode,
                '15000000000',
                [
                    'phone' => '15000000000',
                ],
                false,
                true
            ],
            'InvalidCode' => [
                $smsCode,
                '15000000000',
                [],
                true,
                false,
                true
            ]
        ];
    }

    /**
     * @dataProvider isValidData
     */
    public function testIsValid(SmsCode $smsCode,
                                $phoneNumber,
                                $context,
                                $setPhoneNumber,
                                $result,
                                $testAllowTimes = false)
    {
        $smsCode->setInputName('phone');
        $code = $smsCode->generate($phoneNumber);
        $setPhoneNumber && $smsCode->setPhoneNumber($phoneNumber);

        if ($testAllowTimes) {
            $code[0] = 'fail';
        }

        //getPhoneNumber
        $this->assertEquals($result, $smsCode->isValid($code[0], $context));

        if ($testAllowTimes) {
            //Test valid failure
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
    }

    /**
     * Test factory
     */
    public function testFactory()
    {
        $configs = (new Module())->getConfig();
        $smConfig = $configs['service_manager'];
        $smConfig = ArrayUtils::merge($smConfig, (new ConfigProvider())->getDependencyConfig());
        $sm = new ServiceManager($smConfig);
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
                'cache' => $this->getCacheConfig()
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
