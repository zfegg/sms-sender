<?php

namespace Zfegg\SmsSender\Captcha;

use Zend\Cache\Storage\Adapter\AbstractAdapter as AbstractCacheAdapter;
use Zend\Cache\StorageFactory;
use Zend\Validator\AbstractValidator;

/**
 * Class SmsCode
 *
 * @package Zfegg\SmsSender\Captcha
 */
class SmsCode extends AbstractValidator
{
    const MISSING_PHONE_NUMBER_INPUT = 'missingPhoneNumberInput';
    const EXPIRE_CODE = 'expireCode';
    const INPUT_ERROR = 'inputError';
    const INPUT_ERROR_AND_RESET = 'inputErrorAndReset';

    protected $messageTemplates = [
        self::MISSING_PHONE_NUMBER_INPUT => "手机号表单未设置",
        self::EXPIRE_CODE => "验证码过期，请重新发送验证码",
        self::INPUT_ERROR => "验证码输入错误,还有%times%次有效输入",
        self::INPUT_ERROR_AND_RESET => "验证码输入错误,请重新发送",
    ];

    protected $cache;

    protected $phoneNumber;

    protected $inputName;

    /**
     * Length of the word to generate
     *
     * @var int
     */
    protected $wordlen = 4;

    /**
     * Allow validation times.
     *
     * @var int
     */
    protected $allowValidationTimes = 3;

    /**
     * @return AbstractCacheAdapter
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param AbstractCacheAdapter|array $cache
     * @return $this
     */
    public function setCache($cache)
    {
        if (is_array($cache)) {
            $cache = StorageFactory::factory($cache);
        }

        $this->cache = $cache;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    /**
     * @return int
     */
    public function getWordlen()
    {
        return $this->wordlen;
    }

    /**
     * @param int $wordlen
     * @return $this
     */
    public function setWordlen($wordlen)
    {
        $this->wordlen = (int)$wordlen;
        return $this;
    }

    /**
     * @return int
     */
    public function getAllowValidationTimes()
    {
        return $this->allowValidationTimes;
    }

    /**
     * @param int $allowValidationTimes
     * @return $this
     */
    public function setAllowValidationTimes($allowValidationTimes)
    {
        $this->allowValidationTimes = (int)$allowValidationTimes;
        return $this;
    }

    /**
     * @return string
     */
    public function getInputName()
    {
        return $this->inputName;
    }

    /**
     * @param string $inputName
     * @return $this
     */
    public function setInputName($inputName)
    {
        $this->inputName = $inputName;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid($value, $context = null)
    {
        if (!$phoneNumber = $this->getPhoneNumber()) {
            if (!(is_array($context) && isset($context[$this->getInputName()]))) {
                $this->error(self::MISSING_PHONE_NUMBER_INPUT);
                return false;
            }

            $phoneNumber = $context[$this->getInputName()];
        }

        $cache = $this->getCache();
        if ($data = $cache->getItem($phoneNumber)) {
            if ($data[0] != $value) {
                $data[1]--;
                $this->setAllowValidationTimes($data[1]);

                if ($data[1]) {
                    $cache->setItem($phoneNumber, $data);

                    $this->abstractOptions['messageVariables']['times'] = 'allowValidationTimes';
                    $this->error(self::INPUT_ERROR);
                } else {
                    $cache->removeItem($phoneNumber);
                    $this->error(self::INPUT_ERROR_AND_RESET);
                }

                return false;
            } else {
                $cache->removeItem($phoneNumber);
                return true;
            }
        } else {
            $this->error(self::EXPIRE_CODE);
            return false;
        }
    }

    /**
     * Generate rand code
     *
     * @param string $phoneNumber
     * @return array [$randCode, $allowValidationTimes]
     */
    public function generate($phoneNumber = null)
    {
        $phoneNumber = $phoneNumber ?: $this->getPhoneNumber();
        $cache = $this->getCache();

        if ($data = $cache->getItem($phoneNumber)) {
            $code = $data;
        } else {
            $min = pow(10, $this->getWordlen() - 1);
            $max = str_repeat(9, $this->getWordlen());
            $code = [mt_rand($min, $max), $this->getAllowValidationTimes()];

            $cache->setItem($phoneNumber, $code);
        }

        return $code;
    }
}
