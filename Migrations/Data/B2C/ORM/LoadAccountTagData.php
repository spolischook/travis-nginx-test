<?php
namespace OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TagBundle\Entity\TagManager;

use OroCRM\Bundle\AccountBundle\Entity\Account;

class LoadAccountTagData extends AbstractFixture  implements DependentFixtureInterface
{
    /** @var  TagManager */
    protected $tagManager;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadAccountData',
            'OroCRMPro\Bundle\DemoDataBundle\Migrations\Data\B2C\ORM\LoadTagData',
        ];
    }

    /**
     * {@inheritDoc}
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

        $user = $this->getMainUser();

        /**
         * Need for saveTagging
         */

        $this->setSecurityContext($user);

        foreach ($info as $accountUid => $accountData) {
            /** @var Account $account */
            $account = $accountData['account'];
            $account->setTags(['owner' => $accountData['tags'], 'all' => []]);
            $manager->persist($account);

            $this->tagManager->saveTagging($account, false);
        }
        $manager->flush();
    }

    /**
     * @param $uid
     * @return Tag
     * @throws EntityNotFoundException
     */
    public function getTagReference($uid)
    {
        $reference = 'Tag:' . $uid;
        return $this->getReferenceByName($reference);
    }

    /**
     * @param $uid
     * @return Account
     * @throws EntityNotFoundException
     */
    public function getAccountReference($uid)
    {
        $reference = 'Account:' . $uid;
        return $this->getReferenceByName($reference);
    }
}
