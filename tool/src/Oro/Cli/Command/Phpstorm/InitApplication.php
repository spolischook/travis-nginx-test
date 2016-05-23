<?php

namespace Oro\Cli\Command\Phpstorm;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use Oro\Cli\Command\RootCommand;

/**
 * Prepares PHPStorm configuration from config folder to root .idea folder so proper application settings
 * could be activated.
 *
 * Add or update application config files if any PHPStorm configuration changes should be done.
 */
class InitApplication extends RootCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('phpstorm:init-application')
            ->addArgument('application', InputArgument::OPTIONAL, 'Application name')
            ->setDescription('Switch PHPStorm settings and optimize developer experience for requested application.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $application = $input->getArgument('application');
        $rootSrcDir = __DIR__ . DIRECTORY_SEPARATOR . "config";

        if ($application) {
            $srcDir = $rootSrcDir . DIRECTORY_SEPARATOR . $application;
            if (is_dir($srcDir)) {
                $this->updateConfigs(
                    $output,
                    $srcDir,
                    $this->getRootDir() . DIRECTORY_SEPARATOR . '.idea'
                );

                $output->writeln("Configuration updated. Please restart PHPStorm.");
            } else {
                $output->writeln("Configuration for application \"{$application}\" doesn't exist");
            }
        } else {
            $output->writeln('Existing applications:');
            $files = scandir($rootSrcDir);
            foreach ($files as $name) {
                if (!in_array($name, array('.', '..'), true) && is_dir($rootSrcDir . DIRECTORY_SEPARATOR . $name)) {
                    $output->writeln('  ' . $name);
                }
            }
        }
    }

    /**
     * @param OutputInterface $output
     * @param string          $srcDir
     * @param string          $destDir
     */
    protected function updateConfigs(OutputInterface $output, $srcDir, $destDir)
    {
        $this->copyFile($output, $srcDir, $destDir, 'dev.iml', true);
        $this->mergeFile($output, $srcDir, $destDir, 'php.xml', false);
        $this->updateSymfony2PluginConfig($output, $srcDir, $destDir);
    }

    /**
     * @param OutputInterface $output
     * @param string          $srcDir
     * @param string          $destDir
     * @param string          $fileName
     * @param bool            $override
     *
     * @return bool TRUE if the file copied;
     *              FALSE if the destination file already exists and override was not requested
     */
    protected function mergeFile(OutputInterface $output, $srcDir, $destDir, $fileName, $override = false)
    {
        $destFile = $destDir . DIRECTORY_SEPARATOR . $fileName;
        if ($override || !is_file($destFile)) {
            return $this->copyFile($output, $srcDir, $destDir, $fileName, $override);
        }

        $srcFile = $srcDir . DIRECTORY_SEPARATOR . $fileName;
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Merge {$srcFile} with {$srcFile}");
        }

        $destXmlDoc = $this->loadXmlDocument($destFile);
        $sourceXmlDoc = $this->loadXmlDocument($srcFile);

        foreach ($this->findByXPath($sourceXmlDoc, "/project/component") as $srcElement) {
            $project = $destXmlDoc->getElementsByTagName('project')->item(0);
            $destElement = $this->findByXPath(
                $destXmlDoc,
                sprintf("/project/component[@name='%s']", $srcElement->attributes->getNamedItem('name')->nodeValue)
            );

            $srcElement = $destXmlDoc->importNode($srcElement, true);

            if ($destElement->length) {
                $project->replaceChild($srcElement, $destElement->item(0));
            } else {
                $project->appendChild($srcElement);
            }
        }

        $destXmlDoc->save($destFile);

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param string          $srcDir
     * @param string          $destDir
     * @param string          $fileName
     * @param bool            $override
     *
     * @return bool TRUE if the file copied;
     *              FALSE if the destination file already exists and override was not requested
     */
    protected function copyFile(OutputInterface $output, $srcDir, $destDir, $fileName, $override = false)
    {
        $destFile = $destDir . DIRECTORY_SEPARATOR . $fileName;
        if (!$override && is_file($destFile)) {
            return false;
        }

        $srcFile = $srcDir . DIRECTORY_SEPARATOR . $fileName;
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Copying {$srcFile} to {$destDir}");
        }
        copy($srcFile, $destFile);

        return true;
    }

    /**
     * @param OutputInterface $output
     * @param string          $srcDir
     * @param string          $destDir
     */
    protected function updateSymfony2PluginConfig(OutputInterface $output, $srcDir, $destDir)
    {
        $fileName = 'symfony2.xml';
        if ($this->copyFile($output, $srcDir, $destDir, $fileName)) {
            return;
        }

        $srcFile = $srcDir . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($srcFile)) {
            return;
        }

        $destFile = $destDir . DIRECTORY_SEPARATOR . $fileName;
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $output->writeln("Updating {$destFile}");
        }

        $rootNodePath = "/project/component[@name='Symfony2PluginSettings']";
        $optionNames = ['directoryToApp', 'directoryToWeb', 'pathToUrlGenerator', 'pathToTranslation'];
        $xPathExpr = sprintf(
            $rootNodePath . "/option[%s]",
            implode(
                ' or ',
                array_map(
                    function ($name) {
                        return "@name='{$name}'";
                    },
                    $optionNames
                )
            )
        );

        $destXmlDoc = $this->loadXmlDocument($destFile);
        $destNode = $this->findByXPath($destXmlDoc, $rootNodePath)->item(0);
        foreach ($this->findByXPath($destXmlDoc, $xPathExpr) as $element) {
            $destNode->removeChild($element);
        }
        foreach ($this->findByXPath($this->loadXmlDocument($srcFile), $xPathExpr) as $element) {
            $destNode->appendChild($destXmlDoc->importNode($element, false));
        }
        $destXmlDoc->save($destFile);
    }

    /**
     * @param string $file
     *
     * @return \DOMDocument
     */
    protected function loadXmlDocument($file)
    {
        $xmlDoc = new \DOMDocument();
        $xmlDoc->preserveWhiteSpace = false;
        $xmlDoc->formatOutput = true;
        $xmlDoc->load($file);

        return $xmlDoc;
    }

    /**
     * @param \DOMDocument $xmlDoc
     * @param string       $expression
     *
     * @return \DOMNodeList|\DOMNode[]
     */
    protected function findByXPath(\DOMDocument $xmlDoc, $expression)
    {
        $xPath = new \DOMXPath($xmlDoc);

        return $xPath->query($expression);
    }
}
