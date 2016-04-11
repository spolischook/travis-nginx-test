<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Cases;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use OroCRM\Bundle\CaseBundle\Entity\CaseComment;
use OroCRM\Bundle\CaseBundle\Entity\CaseEntity;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadCaseEntityData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $entitiesCount;

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'cases' => $this->loadData('cases/cases.csv'),
            'comments' => $this->loadData('cases/comments.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        $this->entityManager = $manager;
        foreach ($data['cases'] as $dataCase) {
            $case = $this->createCaseEntity($dataCase);
            $this->entityManager->persist($case);
            $this->setCaseReference($dataCase['uid'], $case);
        }
        foreach ($data['comments'] as $dataComment) {
            /** @var CaseEntity $case */
            $case = $this->getCaseReference($dataComment['case uid']);
            $comment = $this->createCaseComment($case, $dataComment);
            $this->entityManager->persist($comment);
            $this->entityManager->persist($case);
        }

        $manager->flush();
    }

    /**
     * @param array $data
     * @return CaseEntity|null
     */
    protected function createCaseEntity($data)
    {
        $owner = $this->getMainUser();
        $organization = $this->getOrganizationReference($data['organization uid']);
        $assignedTo = $this->getUserReference($data['assignedto uid']);
        $source = $this->getRandomEntity('OroCRMCaseBundle:CaseSource');
        $status = $this->getRandomEntity('OroCRMCaseBundle:CaseStatus');
        $priority = $this->getRandomEntity('OroCRMCaseBundle:CasePriority');

        if (!$owner || !$assignedTo || !$source || !$status) {
            // If we don't have users, sources and status we cannot load fixture cases
            return null;
        }
        /** @var CaseEntity $case */
        $case = $this->container->get('orocrm_case.manager')->createCase();
        $case->setSubject($data['subject']);
        $case->setDescription($data['text']);
        $case->setReportedAt($this->getRandomDate());
        $case->setOwner($owner);
        $case->setAssignedTo($assignedTo);
        $case->setSource($source);
        $case->setStatus($status);
        $case->setPriority($priority);
        $case->setOrganization($organization);

        switch (rand(0, 1)) {
            case 0:
                $contact = $this->getContactReference(rand(1, 50));
                $case->setRelatedContact($contact);
                break;
            case 1:
            default:
                $account = $this->getAccountReference(rand(1, 50));
                $case->setRelatedAccount($account);
                break;
        }

        return $case;
    }

    /**
     * @param CaseEntity $case
     * @param array $data
     * @return CaseEntity|null
     */
    protected function createCaseComment($case, $data)
    {
        /** @var CaseComment $comment */
        $comment = $this->container->get('orocrm_case.manager')->createComment();
        $comment->setMessage($data['text']);
        $comment->setOrganization($case->getOrganization());
        $comment->setOwner($case->getOwner());
        $comment->setPublic(rand(0, 5));
        $comment->setCreatedAt($this->getRandomDate());

        if (rand(0, 3) == 3) {
            $comment->setContact($case->getRelatedContact());
        }
        if (rand(0, 5) == 5) {
            $comment->setUpdatedBy($case->getAssignedTo());
            $comment->setUpdatedAt($this->getRandomDate());
        }
        $case->addComment($comment);

        return $comment;
    }

    /**
     * @param string $entityName
     * @return object|null
     */
    protected function getRandomEntity($entityName)
    {
        $count = $this->getEntityCount($entityName);

        if ($count) {
            return $this->entityManager->createQueryBuilder()
                ->select('e')
                ->from($entityName, 'e')
                ->setFirstResult(rand(0, $count - 1))
                ->setMaxResults(1)
                ->orderBy('e.' . $this->entityManager->getClassMetadata($entityName)->getSingleIdentifierFieldName())
                ->getQuery()
                ->getSingleResult();
        }

        return null;
    }

    /**
     * @param string $entityName
     * @return int
     */
    protected function getEntityCount($entityName)
    {
        if (!isset($this->entitiesCount[$entityName])) {
            $this->entitiesCount[$entityName] = (int)$this->entityManager->createQueryBuilder()
                ->select('COUNT(e)')
                ->from($entityName, 'e')
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->entitiesCount[$entityName];
    }

    /**
     * @return \DateTime
     */
    protected function getRandomDate()
    {
        $result = new \DateTime();
        $result->sub(new \DateInterval(sprintf('P%dDT%dM', rand(0, 30), rand(0, 1440))));

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 35;
    }
}
