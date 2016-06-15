<?php

namespace OroPro\Bundle\SecurityBundle\Tokens;

use Oro\Bundle\UserBundle\Security\WsseTokenFactoryInterface;

class ProWsseTokenFactory implements WsseTokenFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(array $roles = [])
    {
        return new ProWsseToken($roles);
    }
}
