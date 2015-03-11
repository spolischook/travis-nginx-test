<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Oro\Bundle\TagBundle\Entity\Tag;

use Doctrine\Common\Persistence\ObjectManager;

class LoadTagsData extends AbstractFixture
{
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

        /**
         * TODO:Move to reset command
         */
        $this->removeOldData('OroTagBundle:Tag');

        foreach ($data['tags'] as $tagData) {
            $tagData['organization'] = $this->getMainOrganization();
            $tagData['owner'] = $this->getMainUser();
            $uid = $tagData['uid'];
            unset($tagData['uid']);
            $tag = new Tag();
            $this->setObjectValues($tag, $tagData);
            $manager->persist($tag);

            $this->setReference('OroCRMLiveDemoBundle:Tag:' . $uid, $tag);
        }
        $manager->flush();
    }
}
