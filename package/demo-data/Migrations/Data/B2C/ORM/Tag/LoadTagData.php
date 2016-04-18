<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Tag;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Oro\Bundle\TagBundle\Entity\Tag;
use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadTagData extends AbstractFixture implements OrderedFixtureInterface
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
     * @return array
     */
    public function getData()
    {
        return [
            'tags' => $this->loadData('tags/tags.csv'),
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        foreach ($data['tags'] as $tagData) {
            $tagData['organization'] = $this->getOrganizationReference($tagData['organization uid']);
            $tagData['owner']        = $this->getMainUser();

            $tag = new Tag();
            $this->setObjectValues($tag, $tagData);
            $manager->persist($tag);

            $this->setTagReference($tagData['uid'], $tag);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 22;
    }
}
