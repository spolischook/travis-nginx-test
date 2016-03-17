<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Dashboard;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadDashboardData extends AbstractFixture implements OrderedFixtureInterface
{
    /** @var  Manager */
    protected $dashboardManager;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->dashboardManager = $this->container->get('oro_dashboard.manager');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'dashboards' => $this->loadData('dashboards/dashboards.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $this->removeDashboards();

        foreach ($data['dashboards'] as $dashboardData) {
            /** @var DashboardModel $dashboardModel */
            $dashboardModel = $this->dashboardManager->createDashboardModel();
            $dashboardModel->setName($dashboardData['name']);
            $dashboardModel->setLabel($dashboardData['label']);
            $dashboardModel->setOrganization($this->getOrganizationReference($dashboardData['organization uid']));
            $dashboardModel->setOwner($this->getMainUser());
            $dashboardModel->setIsDefault($dashboardData['is_default']);
            $this->dashboardManager->save($dashboardModel);
            $this->setDashboardReference($dashboardData['uid'], $dashboardModel->getEntity());
        }
        $manager->flush();
    }

    /**
     * Remove all dashboards
     */
    protected function removeDashboards()
    {
        $dashboardRepository = $this->em->getRepository('OroDashboardBundle:Dashboard');
        foreach ($dashboardRepository->findAll() as $dashboard) {
            $this->em->remove($dashboard);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 19;
    }
}
