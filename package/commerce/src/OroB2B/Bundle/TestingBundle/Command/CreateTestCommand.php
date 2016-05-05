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
            ->addArgument('class', InputArgument::REQUIRED);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $generator = $container->get('orob2b_testing.generator.test');
        $input->getArgument('class');
        $generator->generate($input->getArgument('class'), 'unit');
    }
}
