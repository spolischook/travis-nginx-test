<?php

namespace OroPro\Bundle\OrganizationBundle\Request\ParamConverter;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class RequestReportParamConverter implements ParamConverterInterface
{
    /** @var  ConfigProvider */
    protected $organizationConfigProvider;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var  Registry */
    protected $doctrine;

    /**
     * @param ConfigProvider $organizationConfigProvider
     * @param SecurityFacade $securityFacade
     * @param Registry       $doctrine
     */
    public function __construct(
        ConfigProvider $organizationConfigProvider,
        SecurityFacade $securityFacade,
        Registry $doctrine
    ) {
        $this->organizationConfigProvider = $organizationConfigProvider;
        $this->securityFacade             = $securityFacade;
        $this->doctrine                   = $doctrine;
    }

    /**
     * Stores the object in the request.
     *
     * @param Request                $request
     * @param ConfigurationInterface $configuration
     *
     * @return bool
     * @throws AccessDeniedException When User doesn't have permission to the object
     */
    public function apply(Request $request, ConfigurationInterface $configuration)
    {
        /** @var Report|Segment $record */
        $record = $this->doctrine->getManager()
            ->getRepository($configuration->getClass())
            ->find($request->attributes->get('id'));
        if ($record) {
            $entityClass      = $record->getEntity();
            $config           = $this->organizationConfigProvider->getConfig($entityClass);
            $applicableConfig = $config->get('applicable', false, false);

            $isApplicable =
                $applicableConfig
                && (
                    $applicableConfig['all']
                    || in_array($this->securityFacade->getOrganizationId(), $applicableConfig['selective'])
                );

            if (!$isApplicable) {
                $acl = $this->securityFacade->getRequestAcl($request);

                /**
                 * If entity has no "Applicable Organizations" - we should not allow to view/edit reports and segments
                 * based on such entity.
                 */
                $hasPermission = !in_array(
                    $acl->getPermission(),
                    [BasicPermissionMap::PERMISSION_VIEW, BasicPermissionMap::PERMISSION_EDIT]
                );

                if (!$hasPermission) {
                    throw new AccessDeniedException(
                        'You do not get ' . $acl->getPermission() . ' permission for this object. ' .
                        'Entity is not applicable for current organization.'
                    );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ConfigurationInterface $configuration)
    {
        if ($configuration instanceof ParamConverter) {
            return in_array(
                $configuration->getClass(),
                [
                    'Oro\Bundle\ReportBundle\Entity\Report',
                    'Oro\Bundle\SegmentBundle\Entity\Segment'
                ]
            );
        }

        return false;
    }
}
