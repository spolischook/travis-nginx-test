<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;

class LoadDashboardData extends AbstractFixture implements DependentFixtureInterface
{

    /** @var  Manager */
    protected $dashboardManager;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->dashboardManager = $this->container->get('oro_dashboard.manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadDefaultUserData',
        ];
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

        $isDefault = true;
        foreach ($data['dashboards'] as $dashboardData) {
            /** @var DashboardModel $dashboardModel */
            $dashboardModel = $this->dashboardManager->createDashboardModel();
            $dashboardModel->setName($dashboardData['name']);
            $dashboardModel->setLabel($dashboardData['label']);
            $dashboardModel->setOrganization($this->getOrganizationReference($dashboardData['organization uid']));
            $dashboardModel->setOwner($this->getMainUser());
            $dashboardModel->setIsDefault($isDefault);
            if ($isDefault) {
                $isDefault = false;
            }
            $this->dashboardManager->save($dashboardModel);
            $this->setReference('Dashboard:' . $dashboardData['uid'], $dashboardModel->getEntity());
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
}