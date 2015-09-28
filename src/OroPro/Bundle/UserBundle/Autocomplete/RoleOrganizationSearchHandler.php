<?php

namespace OroPro\Bundle\UserBundle\Autocomplete;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\OrganizationBundle\Autocomplete\OrganizationSearchHandler as BaseOrganizationSearchHandler;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;

/**
 * Search handler for Organization select field on form of Role.
 * It will return any Organization if user is assigned to Global Organization and logged in to it.
 * Otherwise it's possible to select only current organization.
 */
class RoleOrganizationSearchHandler implements SearchHandlerInterface
{
    /**
     * @var BaseOrganizationSearchHandler
     */
    protected $baseOrganizationSearchHandler;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param BaseOrganizationSearchHandler $baseOrganizationSearchHandler
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        BaseOrganizationSearchHandler $baseOrganizationSearchHandler,
        TokenStorageInterface $tokenStorage
    ) {
        $this->baseOrganizationSearchHandler = $baseOrganizationSearchHandler;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function search($query, $page, $perPage, $searchById = false)
    {
        $result = $this->baseOrganizationSearchHandler->search($query, $page, $perPage, $searchById);

        $token = $this->tokenStorage->getToken();
        if (!$token instanceof OrganizationContextTokenInterface) {
            return $result;
        }
        $currentOrganization = $token->getOrganizationContext();

        if (!$currentOrganization->getIsGlobal()) {
            $result['results'] = array_values(
                array_filter(
                    $result['results'],
                    function ($element) use ($currentOrganization) {
                        return $element['id'] == $currentOrganization->getId();
                    }
                )
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->baseOrganizationSearchHandler->getProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityName()
    {
        return $this->baseOrganizationSearchHandler->getEntityName();
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        return $this->baseOrganizationSearchHandler->convertItem($item);
    }
}
