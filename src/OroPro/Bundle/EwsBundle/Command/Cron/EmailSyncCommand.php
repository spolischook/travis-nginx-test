<?php

namespace OroPro\Bundle\EwsBundle\Command\Cron;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Component\Log\OutputLogger;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;

use OroPro\Bundle\EwsBundle\Sync\EwsEmailSynchronizer;

class EmailSyncCommand extends ContainerAwareCommand implements CronCommandInterface
{
    /**
     * The maximum number of email origins which can be synchronized
     */
    const MAX_TASKS = -1;

    /**
     * The maximum number of synchronization tasks running in the same time
     */
    const MAX_CONCURRENT_TASKS = 5;

    /**
     * The minimum time interval (in minutes) between two synchronizations of the same email origin
     */
    const MIN_EXEC_INTERVAL_IN_MIN = 0;

    /**
     * The maximum execution time (in minutes)
     */
    const MAX_EXEC_TIME_IN_MIN = 15;

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/1 * * * *';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:cron:ews-sync')
            ->setDescription('Synchronization emails via EWS')
            ->addOption(
                'max-concurrent-tasks',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum number of synchronization tasks running in the same time.',
                self::MAX_CONCURRENT_TASKS
            )
            ->addOption(
                'min-exec-interval',
                null,
                InputOption::VALUE_OPTIONAL,
                'The minimum time interval (in minutes) between two synchronizations of the same email origin.',
                self::MIN_EXEC_INTERVAL_IN_MIN
            )
            ->addOption(
                'max-exec-time',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum execution time (in minutes). -1 for unlimited.',
                self::MAX_EXEC_TIME_IN_MIN
            )
            ->addOption(
                'max-tasks',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum number of email origins which can be synchronized. -1 for unlimited.',
                self::MAX_TASKS
            )
            ->addOption(
                'id',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'The identifier of email origin to be synchronized.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EwsEmailSynchronizer $synchronizer */
        $synchronizer = $this->getContainer()->get('oro_pro_ews.email_synchronizer');
        $synchronizer->setLogger(new OutputLogger($output));

        $originIds = $input->getOption('id');
        if (!empty($originIds)) {
            $synchronizer->syncOrigins($originIds);
        } else {
            $synchronizer->sync(
                (int)$input->getOption('max-concurrent-tasks'),
                (int)$input->getOption('min-exec-interval'),
                (int)$input->getOption('max-exec-time'),
                (int)$input->getOption('max-tasks')
            );
        }
    }
}
