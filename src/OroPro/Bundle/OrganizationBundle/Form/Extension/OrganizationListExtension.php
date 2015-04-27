<?php

namespace OroPro\Bundle\OrganizationBundle\Form\Extension;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use OroPro\Bundle\OrganizationBundle\Event\OrganizationListEvent;
use OroPro\Bundle\OrganizationBundle\Form\Type\OrganizationChoiceType;

class OrganizationListExtension extends AbstractTypeExtension
{
    /** @var Request */
    protected $request;

    /** @var RouterInterface */
    protected $router;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param RouterInterface $router
     * @param SecurityFacade $securityFacade
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        RouterInterface $router,
        SecurityFacade $securityFacade,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->router = $router;
        $this->securityFacade = $securityFacade;
        $this->eventDispatcher = $eventDispatcher;
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

        if (!$this->securityFacade->isClassMethodGranted($class, $method)) {
            $resolver->setDefaults(['choices' => []]);

            return;
        }

        $organizations = $this->securityFacade->getLoggedUser()->getOrganizations(true);
        $event = new OrganizationListEvent($controllerData['_route'], $organizations);
        $this->eventDispatcher->dispatch(OrganizationListEvent::NAME, $event);

        $resolver->setDefaults(['choices' => $event->getOrganizations()]);
        $resolver->setNormalizers(
            [
                'choices' => function (Options $options, Collection $value) {
                    $choices = [];

                    /** @var Organization $organization */
                    foreach ($value as $organization) {
                        $choices[$organization->getId()] = $organization->getName();
                    }

                    return $choices;
                },
            ]
        );
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
        if (!$parts) {
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
