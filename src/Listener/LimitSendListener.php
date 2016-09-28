<?php

namespace Zfegg\SmsSender\Listener;

use Zend\Cache\Storage\Adapter\AbstractAdapter;
use Zend\Cache\StorageFactory;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zfegg\SmsSender\SmsEvent;

/**
 * Class LimitSendListener
 *
 * @package Zfegg\SmsSender\Listener
 * @author  moln.xie@gmail.com
 */
class LimitSendListener extends AbstractListenerAggregate
{
    const FUNCTION_NAME = '__FUNCTION__';

    protected $waitingTime = 60;

    protected $daySendTimes = 10;

    protected $cache;

    protected $errorMessageTemplates = [
        'waitingLock' => '请等待%sec%秒后在试',
        'timesLock'   => '一个手机号每天只能发送%daySendTimes%次短信,您的手机号已超出限制,请次日在试.',
    ];

    public function __construct($options = [])
    {
        $this->setOptions($options);
    }

    /**
     * Configure state
     *
     * @param  array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }

        return $this;
    }

    /**
     * Get limit cache
     *
     * @return AbstractAdapter
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set limit cache
     *
     * @param AbstractAdapter|array $cache
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
     * @{@inheritdoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $events->attach(SmsEvent::EVENT_PRE_SEND, [$this, 'onPreSend'], $priority);
    }

    public function onPreSend(SmsEvent $e)
    {
        if ($e->getParam(self::FUNCTION_NAME) != 'limitSend') {
            return;
        }

        $time        = time();
        $phoneNumber = $e->getPhoneNumber();
        $cache       = $this->getCache();
        $limitTime   = $cache->getItem($phoneNumber, $success);
        $waitingTime = $this->getWaitingTime();

        if ($success && $time - $limitTime <= $waitingTime) {
            $error = $this->getMessage('waitingLock', [
                '%sec%'         => $waitingTime - ($time - $limitTime),
                '%phoneNumber%' => $phoneNumber
            ]);
            $e->setError($error);
            $e->stopPropagation(true);
            return;
        }

        $sendTimesCacheId = $phoneNumber . 'Times';
        if (($currentTimes = (int)$cache->getItem($sendTimesCacheId)) && $currentTimes >= $this->getDaySendTimes()) {
            $error = $this->getMessage('timesLock', [
                '%phoneNumber%' => $phoneNumber
            ]);
            $e->setError($error);
            $e->stopPropagation(true);
            return;
        }

        $currentTimes++;

        $defaultTtl = $cache->getOptions()->getTtl();

        $cache->getOptions()->setTtl(strtotime(date('Y-m-d 23:59:59')) - $time);
        $cache->setItem($sendTimesCacheId, $currentTimes);

        $cache->getOptions()->setTtl($waitingTime);
        $cache->setItem($phoneNumber, $time);

        $cache->getOptions()->setTtl($defaultTtl);
    }

    private function getMessage($code, array $variables = [])
    {
        $variables['%daySendTimes%'] = $this->getDaySendTimes();
        $variables['%waitingTime%']     = $this->getWaitingTime();

        return str_replace(array_keys($variables), $variables, $this->errorMessageTemplates[$code]);
    }

    /**
     * Clear number limit.
     *
     * @param $phoneNumber
     */
    public function clearLock($phoneNumber)
    {
        $this->getCache()->removeItem($phoneNumber);
        $this->getCache()->removeItem($phoneNumber . 'Times');
    }

    /**
     * Get waiting time
     *
     * @return int
     */
    public function getWaitingTime()
    {
        return $this->waitingTime;
    }

    /**
     * Set waiting time.
     *
     * @param $waitingTime
     * @return $this
     */
    public function setWaitingTime($waitingTime)
    {
        $this->waitingTime = $waitingTime;
        return $this;
    }

    /**
     * Set how many times a day to send.
     *
     * @param int $daySendTimes
     *
     * @return $this
     */
    public function setDaySendTimes($daySendTimes)
    {
        $this->daySendTimes = $daySendTimes;
        return $this;
    }

    /**
     * Get send times
     *
     * @return int
     */
    public function getDaySendTimes()
    {
        return $this->daySendTimes;
    }
}
