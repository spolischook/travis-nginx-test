<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Oro\Bundle\TagBundle\Entity\Tag;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class LoadTagData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadOrganizationData'
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'tags' => $this->loadData('tags/tags.csv')
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['tags'] as $tagData) {
            $tagData['organization'] = $this->getOrganizationReference($tagData['organization uid']);
            $tagData['owner'] = $this->getMainUser();
            $uid = $tagData['uid'];
            unset($tagData['uid'], $tagData['organization uid']);
            $tag = new Tag();
            $this->setObjectValues($tag, $tagData);
            $manager->persist($tag);

            $this->setReference('Tag:' . $uid, $tag);
        }
        $manager->flush();
    }
}
