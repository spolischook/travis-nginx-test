<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;


use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\ReportType;

class LoadReportData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var EntityRepository */
    protected $reportTypeRepository;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->reportTypeRepository = $this->em->getRepository('OroReportBundle:ReportType');
    }

    /**
     * @return array
     */
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'organization uid',
                'business unit uid',
                'type',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\\LoadOrganizationData',
            __NAMESPACE__ . '\\LoadBusinessUnitData',
        ];
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            'reports' => $this->loadData('reports/reports.csv'),
            'columns' => $this->loadData('reports/reports_definition_columns.csv'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['reports'] as $reportData) {
            $report = new Report();
            $this->setObjectValues($report, $reportData);
            $this->addDefinition($report, $reportData['uid']);

            $report->setType($this->getReportType($reportData['type']));
            $report->setOrganization($this->getOrganizationReference($reportData['organization uid']));
            $report->setOwner($this->getBusinessUnitReference($reportData['business unit uid']));
            $manager->persist($report);
        }
        $manager->flush();
    }

    /**
     * @param Report $report
     * @param $uid
     */
    protected function addDefinition(Report $report, $uid)
    {
        $data = $this->getData();

        $columns = array_filter(
            $data['columns'],
            function ($columnData) use ($uid) {
                return $columnData['report uid'] == $uid;
            }
        );

        $definition = [
            'columns' => []
        ];

        foreach ($columns as $column) {
            $definition['columns'][] = [
                'name' => $column['name'],
                'label' => $column['label'],
                'sorting' => '',
                'func' => null,
            ];
        }
        $report->setDefinition(json_encode($definition));
    }

    /**
     * @param $name
     * @return ReportType
     * @throws EntityNotFoundException
     */
    protected function getReportType($name)
    {
        /** @var ReportType $type */
        $type = $this->reportTypeRepository->findOneBy(['name' => $name]);
        if (!$type) {
            throw new EntityNotFoundException('Report type ' . $name . ' not found!');
        }
        return $type;
    }
}
