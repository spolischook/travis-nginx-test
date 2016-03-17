<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\ReportType;

use OroCRMPro\Bundle\DemoDataBundle\Exception\EntityNotFoundException;

class LoadReportData extends AbstractFixture implements OrderedFixtureInterface
{
    /** @var EntityRepository */
    protected $reportTypeRepository;

    /**
     * {@inheritdoc}
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
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->removeReports();

        $data = $this->getData();

        foreach ($data['reports'] as $reportData) {
            $report = new Report();
            $this->setObjectValues($report, $reportData);

            $report->setType($this->getReportType($reportData['type']));
            $report->setOrganization($this->getOrganizationReference($reportData['organization uid']));
            $report->setOwner($this->getBusinessUnitReference($reportData['business unit uid']));
            $this->setReportReference($reportData['uid'], $report);
            $manager->persist($report);
        }
        $manager->flush();
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

    /**
     * Remove all reports
     */
    protected function removeReports()
    {
        $reportRepository = $this->em->getRepository('OroReportBundle:Report');
        foreach ($reportRepository->findAll() as $entity) {
            $this->em->remove($entity);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 15;
    }
}
