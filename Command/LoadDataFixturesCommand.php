<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Command;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ExpressionBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand as BaseDataFixturesCommand;
use OroCRM\Bundle\AnalyticsBundle\Command\CalculateAnalyticsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoadDataFixturesCommand extends BaseDataFixturesCommand
{
    const COMMAND_NAME = 'oro:migration:live:demo:data:load';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Load live demo data fixtures.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force installation')
            ->addOption('clean', null, InputOption::VALUE_NONE, 'Clean demo data');
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    protected function getFixtureRelativePath(InputInterface $input)
    {
        $fixtureRelativePath = self::MAIN_FIXTURES_PATH;
        if ($this->getTypeOfFixtures($input)) {
            $fixtureRelativePath = 'Migrations/Data/' . $this->getTypeOfFixtures($input) . '/ORM';
        }

        return str_replace('/', DIRECTORY_SEPARATOR, '/' . $fixtureRelativePath);
    }


    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $clean = $input->getOption('clean');
        $fixturesType = $this->getTypeOfFixtures($input);
        if ($clean && $force) {
            return $output->writeln('<error>Use one of two options: --clean or --force</error>');
        } elseif ($force) {
            $parentReturnCode = parent::execute($input, $output);
            if ($parentReturnCode === 0) {
                $commandName = CalculateAnalyticsCommand::COMMAND_NAME;
                $command = $this->getApplication()->find($commandName);
                $input = new ArrayInput(['command' => $commandName]);
                $command->run($input, $output);
            }
            return $parentReturnCode;
        } elseif ($clean) {
            $output->writeln('  <comment>></comment> <info>Removing demo data</info>');
            $this->removeOldData($output);
            return $output->writeln('  <comment>></comment> <info>Demo data removed</info>');
        } else {
            $this->writeDescription($output, $fixturesType);
            return 0;
        }
    }

    protected function removeOldData()
    {
        $container = $this->getContainer();
        /** @var EntityManager $manager */
        $manager = $container->get('doctrine')->getManager();

        $criteria = new Criteria((new ExpressionBuilder())->gt('id', 1));
        $accessGroupCriteria = new Criteria((new ExpressionBuilder())->gt('id', 3));
        $migrationsCriteria = new Criteria((new ExpressionBuilder())->contains('className', 'Demo'));

        $repositories = [
            'OroCalendarBundle:Calendar' => $criteria,
            'OroCalendarBundle:CalendarEvent' => null,
            'OroCalendarBundle:CalendarProperty' => $criteria,
            'OroCRMContactBundle:ContactAddress' => null,
            'OroEmailBundle:EmailBody' => null,
            'OroEmailBundle:EmailRecipient' => null,
            'OroEmailBundle:Email' => null,
            'OroCRMContactBundle:Contact' => null,
            'OroActivityListBundle:ActivityList' => null,
            'OroCRMMagentoBundle:Website' => null,
            'OroCRMMagentoBundle:Store' => null,
            'OroCRMMagentoBundle:CustomerGroup' => null,
            'OroNavigationBundle:NavigationItem' => null,
            'OroOrganizationBundle:Organization' => $criteria,
            'OroOrganizationBundle:BusinessUnit' => $criteria,
            'OroUserBundle:Group' => $accessGroupCriteria,
            'OroNotificationBundle:EmailNotification' => null,
            'OroCRMMagentoBundle:Order' => null,
            'OroCRMMagentoBundle:Cart' => null,
            'OroCRMMagentoBundle:Customer' => null,
            'OroNotificationBundle:RecipientList' => null,
            'OroCRMMagentoBundle:CartItem' => null,
            'OroCRMAnalyticsBundle:RFMMetricCategory' => null,
            'OroSegmentBundle:Segment' => null,
            'OroWorkflowBundle:WorkflowItem' => null,
            'OroTrackingBundle:TrackingData' => null,
            'OroTrackingBundle:TrackingEvent' => null,
            'OroTrackingBundle:TrackingWebsite' => null,
            'OroTrackingBundle:TrackingVisit' => null,
            'OroTrackingBundle:TrackingVisitEvent' => null,
            'OroTrackingBundle:TrackingEventDictionary' => null,
            'OroReportBundle:Report' => $criteria,
            'OroCRMMailChimpBundle:Campaign' => null,
            'OroCRMMailChimpBundle:StaticSegment' => null,
            'OroCRMMailChimpBundle:SubscribersList' => null,
            'OroCRMMailChimpBundle:Member' => null,
            'OroCRMCampaignBundle:EmailCampaign' => null,
            'OroEmailBundle:EmailFolder' => null,
            'OroEmailBundle:EmailOrigin' => null,
            'OroNavigationBundle:NavigationHistoryItem' => null,
            'OroMigrationBundle:DataFixture' => $migrationsCriteria,
            'OroIntegrationBundle:Channel' => null,
            'OroIntegrationBundle:Transport' => null,
            'OroUserBundle:User' => $criteria,
            'OroCRMCallBundle:Call' => null,
            'OroDashboardBundle:Dashboard' => null,
            'OroDashboardBundle:Widget' => null,
            'OroCRMMarketingListBundle:MarketingList' => null,
            'OroCRMTaskBundle:Task' => null,
            'OroCRMCampaignBundle:Campaign' => null,
            'OroCRMAccountBundle:Account' => null,
            'OroCRMChannelBundle:Channel' => null,
        ];

        $emailAddressRepository = $container
            ->get('oro_email.email.address.manager')
            ->getEmailAddressRepository($manager);

        foreach ($repositories as $repository => $repositoryCriteria) {
            $this->removeAll($manager, $repository, $repositoryCriteria);
        }
        $manager->flush();
        $this->removeAll($manager, 'OroEmailBundle:EmailTemplate');
        $this->removeEntities($manager, $emailAddressRepository);
        $manager->flush();
    }

    /**
     * @param EntityManager $manager
     * @param $repository
     * @param null $criteria
     */
    protected function removeAll(EntityManager $manager, $repository, $criteria = null)
    {
        $entityRepository = $manager->getRepository($repository);
        $this->removeEntities($manager, $entityRepository, $criteria);
    }

    /**
     * @param OutputInterface $output
     * @param $fixturesType
     */
    protected function writeDescription(OutputInterface $output, $fixturesType)
    {
        $fixturesPart = $fixturesType
            ? sprintf(' <info>--fixtures-type=%s</info>', $fixturesType)
            : '';

        $output->writeln(
            'To proceed with install demo data - run command with <info>--force</info> option:'
        );
        $output->write(sprintf('    <info>%s --force</info>', $this->getName()));
        $output->writeln($fixturesPart);

        $output->writeln(
            'To clean data - run command with <info> --clean</info> options:'
        );
        $output->write(sprintf('    <info>%s --clean</info>', $this->getName()));
        $output->writeln($fixturesPart);
    }

    /**
     * @param EntityManager $manager
     * @param EntityRepository $repository
     * @param $criteria
     */
    protected function removeEntities(EntityManager $manager, EntityRepository $repository, $criteria = null)
    {
        $entities = $criteria === null
            ? $repository->findAll()
            : $repository->matching($criteria);

        foreach ($entities as $entity) {
            $manager->remove($entity);
        }
    }
}
