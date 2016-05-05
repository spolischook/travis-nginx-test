<?php

namespace OroB2B\Bundle\TestingBundle\Generator;

use Symfony\Component\HttpKernel\KernelInterface;

class TestGenerator
{
    /** @var  \Twig_Environment */
    protected $twig;

    /** @var  KernelInterface */
    protected $kernel;

    /** @var  array */
    protected $usedClasses;

    /**
     * TestGenerator constructor.
     * @param \Twig_Environment $twig
     * @param KernelInterface $kernelInterface
     */
    public function __construct(\Twig_Environment $twig, KernelInterface $kernelInterface)
    {
        $this->twig = $twig;
        $this->kernel = $kernelInterface;
        $this->usedClasses = [];
    }

    /**
     * @param string $className
     * @param string $testType
     */
    public function generate($className, $testType)
    {
        $fullTestNameSpace = $this->getNamespaceForTest($className, $testType);
        $parts = explode('\\', $fullTestNameSpace);
        $testClassName = array_pop($parts);
        $partsOfOriginClass = explode('\\', $className);
        $testedClassName = array_pop($partsOfOriginClass);
        $nameSpace = implode('\\', $parts);
        $testPath = $this->getTestPath($fullTestNameSpace);
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();
        $dependencies = $this->getDependencies($constructor);
        $dependenciesData = $this->getDependenciesData($dependencies);
        $methodsData = $this->getMethodsData($class);
        $orderedUses = $this->getOrderedUses(array_merge($this->usedClasses, [$className]));
        $content = $this->twig->render(
            '@OroTesting/Tests/unit_template.php.twig',
            [
                'namespace' => $nameSpace,
                'vendors' => $orderedUses,
                'className' => $testClassName,
                'testedClassName' => $testedClassName,
                'testedClassNameVariable' => lcfirst($testedClassName),
                'dependenciesData' => $dependenciesData,
                'methodsData' => $methodsData
            ]
        );
        $this->createFile($testPath, $content);
    }

    /**
     * @param \ReflectionClass $class
     * @return array
     */
    protected function getMethodsData(\ReflectionClass $class)
    {
        $data = [];
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $methodName = $method->getName();
            if ($methodName !== '__construct') {
                $params = $method->getParameters();
                $arguments = [];
                foreach ($params as $param) {
                    $temp = [];
                    $class = $param->getClass();
                    if ($class && !in_array($class->getName(), $this->usedClasses)) {
                        $this->usedClasses[] = $class->getName();
                        $constructor = $class->getConstructor();
                        if ($constructor && $constructor->getParameters()) {
                            $temp['has_constructor'] = true;
                        } else {
                            $temp['has_constructor'] = false;
                        }
                    }
                    $fullClassName = $class ? $class->getName() : 'non_object';
                    if (strpos($fullClassName, '\\')) {
                        $parts = explode('\\', $fullClassName);
                        $temp['class'] = end($parts);
                        $temp['fullClass'] = $fullClassName;
                    } elseif ($fullClassName !== 'non_object') {
                        $temp['class'] = '\\' . $fullClassName;
                        $temp['fullClass'] = $temp['class'];
                    } else {
                        $temp['class'] = '';
                    }

                    $temp['name'] = $param->getName();
                    $arguments[] = $temp;
                }
                $data[] = [
                    'name' => $methodName,
                    'arguments' => $arguments,
                    'testName' => 'test' . ucfirst($methodName)
                ];
            }
        }

        return $data;
    }

    /**
     * @param array $dependencies
     * @return array
     */
    protected function getDependenciesData($dependencies)
    {
        $data = [];
        foreach ($dependencies as $dependency) {
            $temp = [];
            if ($dependency['class'] !== 'non_object') {
                if (strpos($dependency['class'], '\\')) {
                    $parts = explode('\\', $dependency['class']);
                    $temp['class'] = end($parts);
                    $temp['fullClassName'] = $dependency['class'];
                } else {
                    $temp['class'] = '\\' . $dependency['class'];
                    $temp['fullClassName'] = '\\' . $dependency['class'];
                }
                $class = new \ReflectionClass($dependency['class']);
                $constructor = $class->getConstructor();
                if ($constructor && $constructor->getParameters()) {
                    $temp['has_constructor'] = true;
                } else {
                    $temp['has_constructor'] = false;
                }
            } else {
                $temp['class'] = '';
            }
            $temp['variable'] = $dependency['name'];
            $data[] = $temp;
        }

        return $data;
    }

    /**
     * @param string[] $classes
     * @return array
     */
    protected function getOrderedUses(array  $classes)
    {
        $result = [];
        foreach ($classes as $class) {
            $slashPos = strpos($class, '\\');
            if ($slashPos) {
                $vendor = substr($class, 0, $slashPos);
                $result[$vendor][] = $class;
            }
        }

        return $result;
    }

    /**
     * @param string $className
     * @param string $testType
     * @return string
     */
    protected function getNamespaceForTest($className, $testType)
    {
        $parts = explode('\\', $className);
        $i = count($parts);
        while ($i > 0) {
            $i--;
            if (strpos($parts[$i], 'Bundle')) {
                break;
            }
        }
        $result = [];
        foreach ($parts as $key => $part) {
            $result[] = $part;
            if ($key === $i) {
                $result[] = 'Tests';
                $result[] = ucfirst($testType);
            }
        }

        return implode('\\', $result) . 'Test';
    }

    /**
     * @param string $nameSpace
     * @return string
     */
    protected function getTestPath($nameSpace)
    {
        $root = $this->kernel->getRootDir();
        $srcPath = str_replace('/app', '/src/', $root);

        return $srcPath . str_replace('\\', '/', $nameSpace) . '.php';
    }

    /**
     * @param $path
     * @param $content
     */
    protected function createFile($path, $content)
    {
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        $fp = fopen($path, "w");
        fwrite($fp, $content);
        fclose($fp);
    }

    /**
     * @param $constructor
     * @return array
     */
    protected function getDependencies($constructor)
    {
        $dependencies = [];
        if ($constructor) {
            $params = $constructor->getParameters();
            foreach ($params as $param) {
                $class = $param->getClass();
                if ($class && !in_array($class->getName(), $this->usedClasses)) {
                    $this->usedClasses[] = $class->getName();
                }
                $dependencies[] = ['class' => $class ? $class->getName() : 'non_object', 'name' => $param->getName()];
            }

            return $dependencies;
        }

        return $dependencies;
    }
}
