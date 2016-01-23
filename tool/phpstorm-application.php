<?php
/**
 * Script allows to switch PHPStorm settings and optimize developer experience for requested application
 */

$example = "Example: php -f tool/phpstorm-apllication.php platform";

if ($argc > 1) {
    $application = $argv[1];
    $applicationDir = __DIR__ . DIRECTORY_SEPARATOR . "phpstorm" . DIRECTORY_SEPARATOR . $application;

    if (is_dir($applicationDir)) {
        $configDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' .DIRECTORY_SEPARATOR . ".idea");

        $files = glob($applicationDir . DIRECTORY_SEPARATOR . "*.*");
        foreach ($files as $file) {
            copy($file, str_replace($applicationDir, $configDir, $file));
        }

        echo "Configuration updated. Please restart  PHPStorm.\n";
    } else {
        echo "Please enter valid application name.\n\n{$example}\n";
    }
} else {
    echo <<<HELP
This script allows to update PHPStorm application settings and optimize developer experience.
Please provide application name as a first option for this command.

{$example}

HELP;
}

