<?php

namespace OroB2B\Bundle\TestingBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CreateTestCommand extends ContainerAwareCommand
{
    const NAME = 'orob2b:test:create';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Create Test')
            ->addArgument('class', InputArgument::REQUIRED)
            ->addArgument('type', InputArgument::REQUIRED);

    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $type = $input->getArgument('class');
        if ($type === 'unit') {
            $generator = $container->get('orob2b_testing.generator.test.unit');
        }
        if (isset($generator)) {
            $generator->generate($input->getArgument('class'));
        }
    }
}
