<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\TagBundle\Entity\TagManager;

use OroCRM\Bundle\AccountBundle\Entity\Account;

class LoadAccountTagData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var  TagManager */
    protected $tagManager;

    /**
     * {@inheritDoc}
     */
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
            __NAMESPACE__ . '\\LoadAccountData',
            __NAMESPACE__ . '\\LoadTagData',
        ];
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
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = $this->getData();

        $info = [];
        foreach ($data['accountTags'] as $accountTag) {
            $account = $this->getAccountReference($accountTag['account uid']);
            $tag = $this->getTagReference($accountTag['tag uid']);

            if (!isset($info[$accountTag['account uid']])) {
                $info[$accountTag['account uid']] = [
                    'account' => $account,
                    'tags' => [$tag]
                ];
            } else {
                $info[$accountTag['account uid']]['tags'][] = $tag;
            }
        }

        /**
         * Need for saveTagging
         */
        foreach ($info as $accountUid => $accountData) {
            /** @var Account $account */
            $account = $accountData['account'];
            $account->setTags(['owner' => $accountData['tags'], 'all' => []]);
            $manager->persist($account);

            $this->setSecurityContext($account->getOwner());
            $this->tagManager->saveTagging($account);
        }
    }
}
