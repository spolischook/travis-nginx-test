<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\TagBundle\Entity\TagManager;

use OroCRM\Bundle\ContactBundle\Entity\Contact;

class LoadContactTagData extends AbstractFixture  implements DependentFixtureInterface
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
            __NAMESPACE__ . '\\LoadContactData',
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
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        $info = [];
        foreach ($data['contactTags'] as $contactTag) {
            $contact = $this->getContactReference($contactTag['contact uid']);
            $tag = $this->getTagReference($contactTag['tag uid']);

            if (!isset($info[$contactTag['contact uid']])) {
                $info[$contactTag['contact uid']] = [
                    'contact' => $contact,
                    'tags' => [$tag]
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
            $contact->setTags(['owner' => $contactData['tags'], 'all' => []]);
            $manager->persist($contact);

            $this->tagManager->saveTagging($contact, false);
        }
        $manager->flush();
    }
}
