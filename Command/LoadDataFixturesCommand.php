<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Command;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ExpressionBuilder;

use Doctrine\ORM\EntityRepository;
use OroCRM\Bundle\AnalyticsBundle\Command\CalculateAnalyticsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand as BaseDataFixturesCommand;

class LoadDataFixturesCommand extends BaseDataFixturesCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('oro:migration:live:demo:data:load')
            ->setDescription('Load live demo data fixtures.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force installation')
            ->addOption('reload', null, InputOption::VALUE_NONE, 'Reload data');
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
        $reload = $input->getOption('reload');
        $fixturesType = $this->getTypeOfFixtures($input);
        if (!$force) {
            $this->writeDescription($output, $fixturesType);
        } else {
            if ($reload) {
                $this->removeOldData($output);
            }
            //die();
            $parentReturnCode = parent::execute($input, $output);
            if ($parentReturnCode === 0) {
                $commandName = CalculateAnalyticsCommand::COMMAND_NAME;
                $command = $this->getApplication()->find($commandName);
                $input = new ArrayInput(['command' => $commandName]);
                $command->run($input, $output);
            }
            return $parentReturnCode;
        }
    }

    /**
     * @param OutputInterface $output
     */
    protected function removeOldData(OutputInterface $output)
    {
        $container = $this->getContainer();
        /** @var EntityManager $manager */
        $manager = $container->get('doctrine')->getManager();

        $criteria = new Criteria((new ExpressionBuilder())->gt('id', 1));
        $accessGroupCriteria = new Criteria((new ExpressionBuilder())->gt('id', 3));

        $migrationsCriteria = new Criteria(
            (new ExpressionBuilder())->orX(
                (new ExpressionBuilder())->contains('className', 'LoadTestContactData'),
                (new ExpressionBuilder())->contains('className', 'Demo')
            )
        );

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

        $specialRepositories = [
            [
                'repository' => 'OroEmailBundle:EmailTemplate',
                'criteria' => null
            ],
            [
                'repository' => $emailAddressRepository,
                'criteria' => null
            ],
        ];
        $output->writeln('  <comment>></comment> <info>Removing entities</info>');

        foreach ($repositories as $repository => $repositoryCriteria) {
            $this->removeAll($manager, $repository, $repositoryCriteria);
        }
        $manager->flush();

        foreach ($specialRepositories as $attributes) {
            $this->removeSpecial($manager, $attributes);
        }
        $manager->flush();
        $manager->clear();
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

    protected function removeSpecial(EntityManager $manager, array $attributes)
    {
        $repository = $attributes['repository'] instanceof EntityRepository
            ? $attributes['repository']
            : $manager->getRepository($attributes['repository']);
        $this->removeEntities($manager, $repository, $attributes['criteria']);
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
            'To reload data - run command with <info>--force --reload</info> options:'
        );
        $output->write(sprintf('    <info>%s --force --reload</info>', $this->getName()));
        $output->writeln($fixturesPart);
    }

    /**
     * @param EntityManager $manager
     * @param EntityRepository $repository
     * @param $criteria
     */
    protected function removeEntities(EntityManager $manager, EntityRepository $repository, $criteria)
    {
        $entities = $criteria === null
            ? $repository->findAll()
            : $repository->matching($criteria);

        foreach ($entities as $entity) {
            $manager->remove($entity);
        }
    }
}
