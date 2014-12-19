<?php
namespace RedCode\Deploy\Config;

use RedCode\Deploy\Package\PackageManager;
use Zend\InputFilter\BaseInputFilter;
use Zend\InputFilter\Factory;
use Zend\InputFilter\InputInterface;

class InputFilter extends BaseInputFilter
{

    /**
     * @var \Zend\InputFilter\Factory
     */
    private static $factory;

    public function __construct(array $data = [])
    {
        self::$factory = self::$factory ?: new Factory;

        $this->createInputFilter();
        $this->setData($data);
    }

    protected function getFactory()
    {
        return self::$factory;
    }

    public function findFirstMessage()
    {
        $input = $this->getInvalidInput();

        return [key($input) => current($input)->getMessages()];
    }

    public function findFirstMessageFlattened()
    {
        $message = $this->findFirstMessage();
        list($key, $message) = [key($message), current($message)];

        //Flatten message.
        while (is_array($message = current($message)))
            $key .= '.' . key($message);

        return [$key => $message];
    }

    /**
     * Returns an error  message as a string with its code prepended
     *
     * @return string
     */
    public function findMessage()
    {
        $message = '';
        if (!empty($this->invalidInputs)) {
            $message = $this->findFirstMessageFlattened();
            $message = key($message) . ' ' . current($message);
        }

        return $message;
    }

    protected function createInputFilter()
    {
        foreach ($this->getSpecification() as $name => $spec) {
            $this->add(
                $input = $this->getFactory()->createInput($spec),
                $name
            );
        }
    }

    protected function getSpecification()
    {
        return [
            'package' => [
                'type' => 'InputFilter',
                [
                    'name'          => 'type',
                    'required'      => false,
                    'validators'    => [[
                        'name'      => 'InArray',
                        'options'   => ['haystack' => PackageManager::getAllowedPackages()]
                    ]]
                ],
                'include' => [
                    'validators' => [[
                        'name' => 'NotEmpty'
                    ]]
                ],
                'exclude' => [
                    'required' => false,
                    'validators' => []
                ]
            ],
            'version' => [
                'validators' => []
            ],
            'version-strategy' => [
                'required'      => false,
                'validators' => [[
                    'name' => 'Callback',
                    'options' => [
                        'callback' => function($strategy, $context) {
                            return !empty($context['version']) && $context['version'] == 'git' && in_array($strategy, ['merged', 'branch', 'tag']);
                        }
                    ]
                ]]
            ],
            'environment' => [
                'type' => 'Collection',
                'count' => 1,
                'input_filter' => [
                    'name' => [
                        'validators' => [[
                            'name' => 'NotEmpty'
                        ]]
                    ],
                    'host' => [
                        'validators' => [[
                            'name' => 'NotEmpty'
                        ]]
                    ],
                    'path' => [
                        'validators' => [[
                            'name' => 'NotEmpty'
                        ]]
                    ]
                ]
            ],
            'command' => [
                'type' => 'InputFilter',
                'local' => [
                    'type' => 'InputFilter',
                    'before' => [
                        'required'      => false,
                        'validators'    => []
                    ],
                    'after' => [
                        'required'      => false,
                        'validators'    => []
                    ],
                ],
                'server' => [
                    'type' => 'InputFilter',
                    'before' => [
                        'required'      => false,
                        'validators'    => []
                    ],
                    'after' => [
                        'required'      => false,
                        'validators'    => []
                    ],
                ]
            ]
        ];
    }
}

 