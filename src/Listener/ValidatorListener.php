<?php

namespace Zfegg\SmsSender\Listener;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputFilter;
use Zfegg\SmsSender\SmsEvent;

/**
 * Class ValidatorListener
 * @package Zfegg\SmsSender
 * @author moln.xie@gmail.com
 */
class ValidatorListener extends AbstractListenerAggregate
{
    /** @var  InputFilter */
    protected $inputFilter;

    /**
     * ValidatorListener constructor.
     *
     * @param InputFilter|array $inputFilter
     */
    public function __construct($inputFilter = null)
    {
        $this->setInputFilter($inputFilter);
    }

    public static function getDefaultInputFilter()
    {
        return (new Factory())->createInputFilter(
            [
                [
                    'name' => 'phoneNumber',
                    'filters' => [
                        ['name' => 'StringTrim']
                    ],
                    'validators' => [
                        [
                            'name' => 'PhoneNumber',
                        ]
                    ]
                ],
                [
                    'name' => 'content',
                    'filters' => [
                        ['name' => 'StringTrim']
                    ],
                    'validators' => [
                        [
                            'name' => 'StringLength',
                            'options' => [
                                'max' => 255
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * @return InputFilter
     */
    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $this->inputFilter = self::getDefaultInputFilter();
        }

        return $this->inputFilter;
    }

    /**
     * @param InputFilter|array $inputFilter
     * @return $this
     */
    public function setInputFilter($inputFilter)
    {
        if (is_array($inputFilter)) {
            $inputFilter = (new Factory())->createInputFilter($inputFilter);
        }

        $this->inputFilter = $inputFilter;
        return $this;
    }

    /**
     * @{@inheritdoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $events->attach(SmsEvent::EVENT_PRE_SEND, [$this, 'onValid'], $priority);
    }

    /**
     * Prepare send validation
     *
     * @param SmsEvent $event
     */
    public function onValid(SmsEvent $event)
    {
        $phoneNumber = $event->getPhoneNumber();
        $content = $event->getContent();

        $inputFilter = $this->getInputFilter();
        $inputFilter->setData(
            [
                'phoneNumber' => $phoneNumber,
                'content' => $content
            ]
        );

        if (!$inputFilter->isValid()) {
            foreach ($inputFilter->getInvalidInput() as $input) {
                $event->setError(current($input->getMessages()));
                break;
            }

            $event->setParam('InputFilter', $inputFilter);
            $event->stopPropagation(true);
            return ;
        }

        return ;
    }
}
