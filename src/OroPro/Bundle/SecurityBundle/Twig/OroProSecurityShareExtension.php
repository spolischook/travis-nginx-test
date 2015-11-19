<?php

namespace OroPro\Bundle\SecurityBundle\Twig;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroPro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use OroPro\Bundle\SecurityBundle\Acl\Domain\OrganizationSecurityIdentity;

class OroProSecurityShareExtension extends \Twig_Extension
{
    /** @var ObjectManager */
    protected $manager;

    /** @var AclCacheInterface */
    protected $aclCache;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ConfigProvider */
    protected $securityProvider;

    /**
     * @param ObjectManager $manager
     * @param AclCacheInterface $aclCache
     * @param SecurityFacade $securityFacade
     * @param NameFormatter $nameFormatter
     * @param TranslatorInterface $translator
     * @param ConfigProvider $securityProvider
     */
    public function __construct(
        ObjectManager $manager,
        AclCacheInterface $aclCache,
        SecurityFacade $securityFacade,
        NameFormatter $nameFormatter,
        TranslatorInterface $translator,
        ConfigProvider $securityProvider
    ) {
        $this->manager = $manager;
        $this->aclCache = $aclCache;
        $this->securityFacade = $securityFacade;
        $this->nameFormatter = $nameFormatter;
        $this->translator = $translator;
        $this->securityProvider = $securityProvider;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'format_share_scopes' => new \Twig_Function_Method($this, 'formatShareScopes'),
            'oropro_share_count' => new \Twig_Function_Method($this, 'getShareCount'),
            'oropro_shared_with_name' => new \Twig_Function_Method($this, 'getSharedWithName'),
        );
    }

    /**
     * Formats json encoded string of share scopes entity config attribute
     *
     * @param EntityConfigModel|string|array|null $value
     * @param string $labelType
     *
     * @return string
     */
    public function formatShareScopes($value, $labelType = 'label')
    {
        if ($value instanceof EntityConfigModel) {
            $value = $this->securityProvider->getConfig($value->getClassName())->get('share_scopes');
        }
        if (empty($value)) {
            return $this->translator->trans('oro.security.share_scopes.not_available');
        }
        $result = [];
        if (is_string($value)) {
            $shareScopes = json_decode($value);
        } elseif (is_array($value)) {
            $shareScopes = $value;
        } else {
            throw new \LogicException('$value must be string or array');
        }

        foreach ($shareScopes as $shareScope) {
            $result[] = $this->translator->trans('oro.security.share_scopes.' . $shareScope . '.' . $labelType);
        }

        return implode(', ', $result);
    }

    /**
     * @param object $object
     *
     * @return int
     */
    public function getShareCount($object)
    {
        $oid = ObjectIdentity::fromDomainObject($object);
        /** @var Acl $acl */
        $acl = $this->aclCache->getFromCacheByIdentity($oid);
        $count = 0;
        if ($acl && $acl->getObjectAces()) {
            $count = count($acl->getObjectAces());
        }

        return $count;
    }

    /**
     * @param object $object
     *
     * @return string
     *
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function getSharedWithName($object)
    {
        $oid = ObjectIdentity::fromDomainObject($object);
        /** @var Acl $acl */
        $acl = $this->aclCache->getFromCacheByIdentity($oid);
        $name = '';
        $objectAces = $acl->getObjectAces();
        if ($acl && $objectAces) {
            usort(
                $objectAces,
                [$this, 'compareEntries']
            );
            /** @var Entry $entry */
            $entry = $objectAces[0];
            $sid = $entry->getSecurityIdentity();
            $name = $this->getFormattedName($sid);
        }

        return $name;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'oropro_security_share_extension';
    }

    /**
     * @param Entry $entryA
     * @param Entry $entryB
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function compareEntries(Entry $entryA, Entry $entryB)
    {
        $sidA = $entryA->getSecurityIdentity();
        $sidB = $entryB->getSecurityIdentity();

        switch (true) {
            case $sidA instanceof UserSecurityIdentity && $sidB instanceof UserSecurityIdentity:
                $result = strcmp($sidA->getUsername(), $sidB->getUsername());
                break;
            case $sidA instanceof BusinessUnitSecurityIdentity && $sidB instanceof BusinessUnitSecurityIdentity:
            case $sidA instanceof OrganizationSecurityIdentity && $sidB instanceof OrganizationSecurityIdentity:
                $idA = (int) $sidA->getId();
                $idB = (int) $sidB->getId();

                $result = $idA < $idB ? -1 : 1;
                break;
            case $sidA instanceof UserSecurityIdentity && $sidB instanceof BusinessUnitSecurityIdentity:
            case $sidA instanceof UserSecurityIdentity && $sidB instanceof OrganizationSecurityIdentity:
            case $sidA instanceof BusinessUnitSecurityIdentity && $sidB instanceof OrganizationSecurityIdentity:
                $result = 1;
                break;
            case $sidA instanceof BusinessUnitSecurityIdentity && $sidB instanceof UserSecurityIdentity:
            case $sidA instanceof OrganizationSecurityIdentity &&
                ($sidB instanceof UserSecurityIdentity || $sidB instanceof BusinessUnitSecurityIdentity):
                $result = -1;
                break;
            default:
                $result = 0;
        }

        return $result;
    }

    /**
     * @param SecurityIdentityInterface $sid
     *
     * @return string
     */
    protected function getFormattedName(SecurityIdentityInterface $sid)
    {
        if ($sid instanceof UserSecurityIdentity && $sid->getUsername()) {
            $user =  $this->manager->getRepository('OroUserBundle:User')
                ->findOneBy(['username' => $sid->getUsername()]);
            if ($user) {
                return $this->nameFormatter->format($user);
            }
        } elseif ($sid instanceof BusinessUnitSecurityIdentity) {
            $businessUnit = $this->manager->getRepository('OroOrganizationBundle:BusinessUnit')->find($sid->getId());
            if ($businessUnit) {
                return $businessUnit->getName();
            }
        } elseif ($sid instanceof OrganizationSecurityIdentity) {
            $organization = $this->manager->getRepository('OroOrganizationBundle:Organization')->find($sid->getId());
            if ($organization) {
                return $organization->getName();
            }
        }

        return '';
    }
}
