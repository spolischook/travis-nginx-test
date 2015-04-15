<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Command;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ExpressionBuilder;

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
                $this->removeOldData();
            }
            die();
            return parent::execute($input, $output);
        }
    }

    protected function removeOldData()
    {
        $container = $this->getContainer();
        /** @var EntityManager $manager */
        $manager = $container->get('doctrine')->getManager();

        $criteria = new Criteria((new ExpressionBuilder())->gt('id', 1));
        $accessGroupCriteria = new Criteria((new ExpressionBuilder())->gt('id', 3));
        $calendarCriteria = new Criteria((new ExpressionBuilder())->gt('id', 1));
        $calendarPropertyCriteria = new Criteria((new ExpressionBuilder())->gt('id', 1));

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
            //'OroEmailBundle:EmailAddress' => null,
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
            'OroCRM\Bundle\AnalyticsBundle\Entity\RFMMetricCategory' => null,
            'Oro\Bundle\SegmentBundle\Entity\Segment' => null,
            'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem' => null,
            'Oro\Bundle\TrackingBundle\Entity\TrackingData' => null,
            'Oro\Bundle\TrackingBundle\Entity\TrackingEvent' => null,
            'Oro\Bundle\TrackingBundle\Entity\TrackingWebsite' => null,
            'Oro\Bundle\TrackingBundle\Entity\TrackingVisit' => null,
            'Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent' => null,
            'Oro\Bundle\TrackingBundle\Entity\TrackingEventDictionary' => null,
            'Oro\Bundle\ReportBundle\Entity\Report' => $criteria,
            'OroCRM\Bundle\MailChimpBundle\Entity\Campaign' => null,
            'OroCRM\Bundle\MailChimpBundle\Entity\StaticSegment' => null,
            'OroCRM\Bundle\MailChimpBundle\Entity\SubscribersList' => null,
            'OroCRM\Bundle\MailChimpBundle\Entity\Member' => null,
            'OroCRM\Bundle\CampaignBundle\Entity\EmailCampaign' => null,
            'Oro\Bundle\EmailBundle\Entity\EmailFolder' => null,
            'Oro\Bundle\EmailBundle\Entity\EmailOrigin' => null,
            'Oro\Bundle\CalendarBundle\Entity\Calendar' => $calendarCriteria,
            'Oro\Bundle\CalendarBundle\Entity\CalendarProperty' => $calendarPropertyCriteria,
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent' => null,
            'Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem' => null,
            'Oro\Bundle\MigrationBundle\Entity\DataFixture' => $migrationsCriteria,
            'OroIntegrationBundle:Channel' => null,
            'Oro\Bundle\IntegrationBundle\Entity\Transport' => null,
            'OroUserBundle:User' => $criteria,
            'OroCRM\Bundle\CallBundle\Entity\Call' => null,
            'Oro\Bundle\DashboardBundle\Entity\Dashboard' => null,
            'Oro\Bundle\DashboardBundle\Entity\Widget' => null,
            'OroCRM\Bundle\MarketingListBundle\Entity\MarketingList' => null,
            'OroCRM\Bundle\TaskBundle\Entity\Task' => null,
            'OroCRM\Bundle\CampaignBundle\Entity\Campaign' => null,
            'OroCRM\Bundle\AccountBundle\Entity\Account' => null,
            'OroCRM\Bundle\ChannelBundle\Entity\Channel' => null,
            'OroEmailBundle:EmailTemplate' => null,
        ];

        foreach ($repositories as $repository => $repositoryCriteria) {
            $this->removeAll($manager, $repository, $repositoryCriteria);
        }
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
        if ($criteria === null) {
            $entities = $entityRepository->findAll();
        } else {
            $entities = $entityRepository->matching($criteria);
        }

        foreach ($entities as $entity) {
            $manager->remove($entity);
        }
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
}
