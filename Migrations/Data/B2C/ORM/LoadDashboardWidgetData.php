<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\WidgetModel;

class LoadDashboardWidgetData extends AbstractFixture implements DependentFixtureInterface
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
            __NAMESPACE__ . '\\LoadDashboardData',
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'widgets' => $this->loadData('dashboards/widgets.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();
        $this->setSecurityContext($this->getMainUser());
        foreach ($data['widgets'] as $widgetData) {
            $dashboard = $this->getDashboardReference($widgetData['dashboard uid']);
            $dashboardModel = $this->dashboardManager->getDashboardModel($dashboard);

            $widget = $this->createWidgetModel($widgetData['widget'],[$widgetData['position x'], $widgetData['position y']]);
            $dashboardModel->addWidget($widget);
            $this->dashboardManager->save($widget);
        }
        $manager->flush();
    }


    /**
     * Create dashboard entity with admin user
     *
     * @param string $widgetName
     * @param array $layoutPosition
     * @return WidgetModel
     */
    protected function createWidgetModel($widgetName, array $layoutPosition = null)
    {
        $widget = $this->dashboardManager->createWidgetModel($widgetName);

        if (null !== $layoutPosition) {
            $widget->setLayoutPosition($layoutPosition);
        }
        return $widget;
    }
}