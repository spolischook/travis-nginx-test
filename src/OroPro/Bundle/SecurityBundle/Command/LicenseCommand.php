<?php

namespace OroPro\Bundle\SecurityBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Command\Logger\OutputLogger;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;

class LicenseCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const COMMAND_NAME = 'oro:cron:enterprise:license';

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        // Every day at 0 hour 0 minute
        return '0 0 * * *';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Verify enterprise license information');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger    = new OutputLogger($output);
        $serverAgent = $this->getContainer()->get('oropro_security.licence.server_agent');

        if (!$this->getContainer()->getParameter('enterprise_licence')) {
            $logger->warning('Enterprise license is empty');
        }

        try {
            $serverAgent->sendStatusInformation();
            $logger->notice('License information sent');
        } catch (\Exception $e) {
            $logger->critical('Could not send license information', array('exception' => $e));
        }
    }
}
