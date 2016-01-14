<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\TaxBundle\Entity\Tax;

class LoadTaxes extends AbstractFixture
{
    const TAX_1 = 'TAX1';
    const TAX_2 = 'TAX2';

    const DESCRIPTION_1 = 'Tax description 1';
    const DESCRIPTION_2 = 'Tax description 2';

    const RATE_1 = 10;
    const RATE_2 = 20;

    const REFERENCE_PREFIX = 'tax';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createTax($manager, self::TAX_1, self::DESCRIPTION_1, self::RATE_1);
        $this->createTax($manager, self::TAX_2, self::DESCRIPTION_2, self::RATE_2);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $code
     * @param string        $description
     * @param int           $rate
     * @return Tax
     */
    protected function createTax(ObjectManager $manager, $code, $description, $rate)
    {
        $tax = new Tax();
        $tax->setCode($code);
        $tax->setDescription($description);
        $tax->setRate($rate);

        $manager->persist($tax);
        $this->addReference(self::REFERENCE_PREFIX . '.' . $code, $tax);

        return $tax;
    }
}
