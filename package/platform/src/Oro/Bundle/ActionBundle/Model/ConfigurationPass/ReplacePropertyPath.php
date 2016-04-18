<?php

namespace Oro\Bundle\ActionBundle\Model\ConfigurationPass;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Component\ConfigExpression\ConfigurationPass\ConfigurationPassInterface;

/**
 * Passes through configuration array and replaces parameter strings ($parameter.name)
 * with appropriate PropertyPath objects
 */
class ReplacePropertyPath implements ConfigurationPassInterface
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @param string|null $prefix
     */
    public function __construct($prefix = null)
    {
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function passConfiguration(array $data)
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = $this->passConfiguration($value);
            } elseif ($this->isStringPropertyPath($value)) {
                $value = $this->parsePropertyPath($value);
            }
        }

        return $data;
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isStringPropertyPath($path)
    {
        return is_string($path)
            && strpos($path, '$') === 0
            && preg_match('/^\$\.?[a-zA-Z_\x7f-\xff][\.a-zA-Z0-9_\x7f-\xff\[\]]*$/', $path);
    }

    /**
     * @param string $value
     * @return PropertyPath
     * @throws \InvalidArgumentException
     */
    protected function parsePropertyPath($value)
    {
        $property = substr($value, 1);

        if (0 === strpos($property, '.')) {
            $property = substr($property, 1);
        } elseif ($this->prefix) {
            $property = $this->prefix . '.' .  $property;
        }

        return new PropertyPath($property);
    }
}
