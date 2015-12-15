<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Entity\GridView;

class LoadGridViewData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $privateView = new GridView();
        $privateView
            ->setName('gridView')
            ->setGridName('testing-grid')
            ->setOwner($this->getReference('simple_user'));
        $manager->persist($privateView);

        $publicView = new GridView();
        $publicView
            ->setName('gridView')
            ->setType(GridView::TYPE_PUBLIC)
            ->setGridName('testing-grid')
            ->setOwner($this->getReference('simple_user'));
        $manager->persist($publicView);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadUserData',
        ];
    }
}
