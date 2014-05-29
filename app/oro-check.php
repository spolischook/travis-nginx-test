<?php

require_once dirname(__FILE__) . '/OroRequirements.php';
$classMap = require_once dirname(__FILE__) . '/../vendor/composer/autoload_classmap.php';

$requiredClasses = array(
    'Symfony\Component\Process\ProcessUtils',
    'Symfony\Component\Process\Process',
    'Symfony\Component\Process\ProcessPipes',
    'Symfony\Component\Process\ProcessBuilder',
    'Symfony\Component\Intl\Intl',
    'Symfony\Component\Process\PhpExecutableFinder',

    'Oro\Bundle\InstallerBundle\Process\PhpExecutableFinder',
);

foreach ($requiredClasses as $requiredClass) {
    if (isset($classMap[$requiredClass])) {
        require_once $classMap[$requiredClass];
    }
}

$oroRequirements = new OroRequirements();

foreach ($oroRequirements->getRequirements() as $requirement) {
    /** @var Requirement $requirement */
    if (!$requirement->isFulfilled()) {
        echo $requirement->getTestMessage() . "\n";
    }
}
