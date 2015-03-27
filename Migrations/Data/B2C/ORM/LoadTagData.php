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
    protected function getExcludeProperties()
    {
        return array_merge(
            parent::getExcludeProperties(),
            [
                'organization uid',
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
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'tags' => $this->loadData('tags/tags.csv'),
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

            $tag = new Tag();
            $this->setObjectValues($tag, $tagData);
            $manager->persist($tag);

            $this->setTagReference($tagData['uid'], $tag);
        }
        $manager->flush();
    }
}
