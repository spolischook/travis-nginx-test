<?php

namespace OroB2B\Bundle\TestingBundle\Generator;

use Symfony\Component\HttpKernel\KernelInterface;

class UnitTestGenerator extends AbstractTestGenerator
{
    /**
     * @param string $className
     */
    public function generate($className)
    {
        $fullTestNameSpace = $this->getNamespaceForTest($className, 'unit');
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
            '@OroB2BTesting/Tests/unit_template.php.twig',
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
}
