<?php

namespace OroCRMPro\Bundle\OutlookBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use OroCRMPro\Bundle\OutlookBundle\Manager\AddInManager;

class CacheClearCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:outlook:cache:clear')
            ->setDescription('Clears the Outlook Add-In files cache')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command clears the Outlook Add-In files cache:

  <info>php %command.full_name% --env=prod</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var AddInManager $addInManager */
        $addInManager = $this->getContainer()->get('orocrmpro_outlook.addin_manager');
        $addInManager->clearCache();
    }
}
