<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Tag;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\TagBundle\Entity\TagManager;

use OroCRM\Bundle\ContactBundle\Entity\Contact;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadContactTagData extends AbstractFixture implements OrderedFixtureInterface
{
    /** @var  TagManager */
    protected $tagManager;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->tagManager = $container->get('oro_tag.tag.manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadContactData',
            __NAMESPACE__ . '\\LoadTagData',
        ];
    }

    public function getData()
    {
        return [
            'contactTags' => $this->loadData('tags/contact_tags.csv'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        $info = [];
        foreach ($data['contactTags'] as $contactTag) {
            $contact = $this->getContactReference($contactTag['contact uid']);
            $tag     = $this->getTagReference($contactTag['tag uid']);

            if (!isset($info[$contactTag['contact uid']])) {
                $info[$contactTag['contact uid']] = [
                    'contact' => $contact,
                    'tags'    => [$tag]
                ];
            } else {
                $info[$contactTag['contact uid']]['tags'][] = $tag;
            }
        }

        $user = $this->getMainUser();

        /**
         * Need for saveTagging
         */
        $this->setSecurityContext($user);

        foreach ($info as $contactUid => $contactData) {
            /** @var Contact $contact */
            $contact = $contactData['contact'];
            $this->tagManager->setTags($contact, new ArrayCollection($contactData['tags']));

            $this->tagManager->saveTagging($contact, false);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 24;
    }
}
