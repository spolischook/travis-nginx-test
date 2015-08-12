<?php

namespace OroPro\Bundle\OrganizationBundle\Twig;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Routing\Router;

use Oro\Bundle\WindowsBundle\Entity\WindowsState;
use Oro\Bundle\WindowsBundle\Twig\WindowsExtension as BaseWindowsExtension;
use OroPro\Bundle\OrganizationBundle\Exception\OrganizationAwareException;

/**
 * Override for remove incompleted global org window
 *
 * Class WindowsExtension
 * @package OroPro\Bundle\OrganizationBundle\Twig
_ */
class WindowsExtension extends BaseWindowsExtension
{
    /** @var Router */
    protected $router;

    /**
     * @param SecurityContextInterface $securityContext
     * @param EntityManager $em
     * @param Router $router
     */
    public function __construct(
        SecurityContextInterface $securityContext,
        EntityManager $em,
        Router $router
    ) {
        $this->router = $router;
        parent::__construct($securityContext, $em);
    }

    /**
     * { @inheritdoc }
     */
    public function renderFragment(\Twig_Environment $environment, WindowsState $windowState)
    {
        $result = '';
        try {
            $result = parent::renderFragment($environment, $windowState);
        } catch (OrganizationAwareException $e) {
            // remove if organisation do not determine
            $this->em->remove($windowState);
            $this->em->flush($windowState);
        }

        return $result;
    }
}
