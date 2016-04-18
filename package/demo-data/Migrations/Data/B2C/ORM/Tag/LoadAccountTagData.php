<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\Tag;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\TagBundle\Entity\TagManager;

use OroCRM\Bundle\AccountBundle\Entity\Account;

use OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\AbstractFixture;

class LoadAccountTagData extends AbstractFixture implements OrderedFixtureInterface
{
    /** @var  TagManager */
    protected $tagManager;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->tagManager = $container->get('oro_tag.tag.manager');
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'accountTags' => $this->loadData('tags/accounts_tags.csv')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        $info = [];
        foreach ($data['accountTags'] as $accountTag) {
            $account = $this->getAccountReference($accountTag['account uid']);
            $tag     = $this->getTagReference($accountTag['tag uid']);

            if (!isset($info[$accountTag['account uid']])) {
                $info[$accountTag['account uid']] = [
                    'account' => $account,
                    'tags'    => [$tag]
                ];
            } else {
                $info[$accountTag['account uid']]['tags'][] = $tag;
            }
        }
        $this->setSecurityContext($this->getMainUser());
        /**
         * Need for saveTagging
         */
        foreach ($info as $accountUid => $accountData) {
            /** @var Account $account */
            $account = $accountData['account'];
            $this->tagManager->setTags($account, new ArrayCollection($accountData['tags']));

            $this->tagManager->saveTagging($account, false);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 23;
    }
}
