<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use OroPro\Bundle\OrganizationBundle\Form\Type\OrganizationChoiceType;

class OrganizationListExtension extends AbstractTypeExtension
{
    /** @var Request */
    protected $request;

    /** @var RouterInterface */
    protected $router;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var SecurityContextInterface */
    protected $securityContext;

    /**
     * @param RouterInterface $router
     * @param SecurityFacade $securityFacade
     * @param SecurityContextInterface $securityContext
     */
    public function __construct(
        RouterInterface $router,
        SecurityFacade $securityFacade,
        SecurityContextInterface $securityContext
    ) {
        $this->router = $router;
        $this->securityFacade = $securityFacade;
        $this->securityContext = $securityContext;
    }

    /**
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $controllerData = $this->getControllerData();
        if (!$controllerData) {
            return;
        }

        list($class, $method) = explode('::', $controllerData['_controller']);

        $annotation = $this->securityFacade->getClassMethodAnnotation($class, $method);
        if (!$annotation) {
            return;
        }

        $resolver->setDefaults(['choices' => $this->getChoices($annotation)]);
    }

    /**
     * @param Acl $annotation Acl to apply
     *
     * @return array
     */
    protected function getChoices(Acl $annotation)
    {
        /** @var Organization[] $organizations */
        $organizations = $this->securityFacade->getLoggedUser()->getOrganizations(true);
        $choices = [];

        foreach ($organizations as $organization) {
            if (!$this->securityContext->isGranted($annotation->getId(), $organization)) {
                continue;
            }

            $choices[$organization->getId()] = $organization->getName();
        }

        return $choices;
    }

    /**
     * @return null|array
     */
    protected function getControllerData()
    {
        if (!$this->request) {
            return null;
        }

        $fromUrl = $this->request->get('form_url');
        if (!$fromUrl) {
            return null;
        }

        $parts = parse_url($fromUrl);
        if (empty($parts['path'])) {
            return null;
        }

        try {
            return $this->router->match(
                str_replace($this->router->getContext()->getBaseUrl(), '', $parts['path'])
            );
        } catch (ResourceNotFoundException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrganizationChoiceType::NAME;
    }
}
