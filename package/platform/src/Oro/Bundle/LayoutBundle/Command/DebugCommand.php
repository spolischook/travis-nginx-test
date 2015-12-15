<?php

namespace Oro\Bundle\LayoutBundle\Command;

use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Component\Layout\LayoutManager;
use Oro\Component\Layout\LayoutRegistryInterface;

use Oro\Bundle\LayoutBundle\Command\Util\DebugLayoutContext;
use Oro\Bundle\LayoutBundle\Command\Util\DebugOptionsResolver;

class DebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this
            ->setName('oro:layout:debug')
            ->addOption(
                'context',
                null,
                InputOption::VALUE_NONE,
                'Show the configuration of the layout context'
            )
            ->addOption(
                'type',
                null,
                InputOption::VALUE_REQUIRED,
                'Show the configuration of the layout block type'
            )
            ->setDescription('Displays the layout configuration.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var LayoutManager $layoutManager */
        $layoutManager = $this->getContainer()->get('oro_layout.layout_manager');
        $registry      = $layoutManager->getLayoutFactory()->getRegistry();

        // the layout context configuration
        if ($input->getOption('context')) {
            $context = new DebugLayoutContext();
            $registry->configureContext($context);
            $output->writeln('Context configurators:');
            $contextConfigurators = array_map(
                function ($configurator) {
                    return get_class($configurator);
                },
                $registry->getContextConfigurators()
            );
            foreach ($contextConfigurators as $configurator) {
                $output->writeln(' ' . $configurator);
            }

            $this->dumpOptionResolver($context->getResolver(), $output);

            $output->writeln('Known data values:');
            /** @var TableHelper $table */
            $table = $this->getHelper('table');
            $table->setHeaders(['Name']);
            $table->setRows([]);
            $dataValues = $context->data()->getKnownValues();
            sort($dataValues);
            foreach ($dataValues as $name) {
                $table->addRow([$name]);
            }
            $table->render($output);

            return;
        }

        // the block type configuration
        $blockTypeName = $input->getOption('type');
        if ($blockTypeName) {
            $blockType = $layoutManager->getLayoutFactory()->getType($blockTypeName);
            $output->writeln(sprintf('Class: %s', get_class($blockType)));
            $hierarchy  = [$blockTypeName];
            $parentName = $blockType->getParent();
            while ($parentName) {
                array_unshift($hierarchy, $parentName);
                $parentName = $registry->getType($parentName)->getParent();
            }
            $output->writeln(sprintf('Type inheritance: %s', implode(' <- ', $hierarchy)));
            $output->writeln('Type extensions:');
            $blockTypeExtensions = array_map(
                function ($extension) {
                    return get_class($extension);
                },
                $registry->getTypeExtensions($blockTypeName)
            );
            foreach ($blockTypeExtensions as $extension) {
                $output->writeln(' ' . $extension);
            }
            $optionsResolver = $this->getBlockTypeOptionsResolver($blockTypeName, $registry);
            $this->dumpOptionResolver($optionsResolver, $output);
        }
    }

    /**
     * @param DebugOptionsResolver $resolver
     * @param OutputInterface      $output
     */
    protected function dumpOptionResolver(DebugOptionsResolver $resolver, OutputInterface $output)
    {
        /** @var TableHelper $table */
        $table = $this->getHelper('table');

        $output->writeln('Default options:');
        $table->setHeaders(['Name', 'Value']);
        $table->setRows([]);
        $options = $resolver->getDefaultOptions();
        ksort($options);
        foreach ($options as $name => $value) {
            $table->addRow([$name, $this->formatValue($value)]);
        }
        $table->render($output);

        $output->writeln('Defined options:');
        $table->setHeaders(['Name', 'Type(s)']);
        $table->setRows([]);
        $options = $resolver->getDefinedOptions();
        ksort($options);
        foreach ($options as $name => $types) {
            $table->addRow([$name, implode(', ', $types)]);
        }
        $table->render($output);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function formatValue($value)
    {
        if (is_object($value)) {
            return sprintf('[%s]', get_class($value));
        }
        if (is_scalar($value)) {
            if ($value === true) {
                $formatted = 'true';
            } elseif ($value === false) {
                $formatted = 'false';
            } elseif (is_string($value)) {
                $formatted = sprintf('"%s"', $value);
            } else {
                $formatted = $value;
            }

            return sprintf('%s [%s]', $formatted, gettype($value));
        }

        return sprintf('[%s]', gettype($value));
    }

    /**
     * @param string                  $blockTypeName
     * @param LayoutRegistryInterface $registry
     *
     * @return DebugOptionsResolver
     */
    protected function getBlockTypeOptionsResolver($blockTypeName, LayoutRegistryInterface $registry)
    {
        $type       = $registry->getType($blockTypeName);
        $parentName = $type->getParent();

        $optionsResolver = $parentName
            ? clone $this->getBlockTypeOptionsResolver($parentName, $registry)
            : new DebugOptionsResolver();

        $type->setDefaultOptions($optionsResolver);
        $registry->setDefaultOptions($blockTypeName, $optionsResolver);

        return $optionsResolver;
    }
}
