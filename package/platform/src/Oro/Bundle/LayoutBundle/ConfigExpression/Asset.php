<?php

namespace Oro\Bundle\LayoutBundle\ConfigExpression;

use Symfony\Component\Asset\Packages;

use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Oro\Component\ConfigExpression\Func\AbstractFunction;

class Asset extends AbstractFunction
{
    /** @var Packages */
    protected $packages;

    /** @var mixed */
    protected $path;

    /** @var mixed */
    protected $packageName;

    /**
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'asset';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $params = [$this->path];
        if ($this->packageName !== null) {
            $params[] = $this->packageName;
        }

        return $this->convertToArray($params);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        $params = [$this->path];
        if ($this->packageName !== null) {
            $params[] = $this->packageName;
        }

        return $this->convertToPhpCode($params, $factoryAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $count = count($options);
        if ($count >= 1 && $count <= 2) {
            $this->path = reset($options);
            if (!$this->path) {
                throw new InvalidArgumentException('Path must not be empty.');
            }
            if ($count > 1) {
                $this->packageName = next($options);
            }
        } else {
            throw new InvalidArgumentException(
                sprintf('Options must have 1 or 2 elements, but %d given.', count($options))
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageParameters($context)
    {
        return [
            '{{ path }}'        => $this->resolveValue($context, $this->path),
            '{{ packageName }}' => $this->resolveValue($context, $this->packageName)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doEvaluate($context)
    {
        $path = $this->resolveValue($context, $this->path);
        if ($path === null) {
            return $path;
        }
        if (!is_string($path)) {
            $this->addError(
                $context,
                sprintf(
                    'Expected a string value for the path, got "%s".',
                    is_object($path) ? get_class($path) : gettype($path)
                )
            );

            return $path;
        }

        $packageName = $this->resolveValue($context, $this->packageName);
        if ($packageName !== null && !is_string($packageName)) {
            $this->addError(
                $context,
                sprintf(
                    'Expected null or a string value for the package name, got "%s".',
                    is_object($packageName) ? get_class($packageName) : gettype($packageName)
                )
            );

            return $path;
        }

        return $this->packages->getUrl($this->normalizeAssetsPath($path), $packageName);
    }

    /**
     * Normalizes assets path
     * E.g. '@AcmeTestBundle/Resources/public/images/picture.png' => 'bundles/acmetest/images/picture.png'
     *
     * @param string $path
     *
     * @return string
     */
    private function normalizeAssetsPath($path)
    {
        if ($path && '@' === $path[0]) {
            $trimmedPath = substr($path, 1);
            $normalizedPath = preg_replace_callback(
                '#(\w+)Bundle/Resources/public#',
                function ($matches) {
                    return strtolower($matches[1]);
                },
                $trimmedPath
            );
            if ($normalizedPath && $normalizedPath !== $trimmedPath) {
                $path = 'bundles/' . $normalizedPath;
            }
        }

        return $path;
    }
}
