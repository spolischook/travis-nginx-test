<?php

namespace OroPro\Bundle\EwsBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroPro\Bundle\EwsBundle\Entity\EwsEmailOrigin;

class UpdateOriginName extends AbstractFixture
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $repo = $manager->getRepository('OroProEwsBundle:EwsEmailOrigin');

        $origins = $repo->findAll();
        if ($origins) {
            foreach ($origins as $origin) {
                $origin->setMailboxName(EwsEmailOrigin::MAILBOX_NAME);
            }

            $manager->flush();
        }
    }
}
