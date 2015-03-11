<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Command;

use Symfony\Component\Console\Input\InputInterface;

use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand as BaseDataFixturesCommand;

class LoadDataFixturesCommand extends BaseDataFixturesCommand
{
    const B2C_FIXTURES_TYPE = 'b2c';
    const B2B_FIXTURES_TYPE = 'b2b';
    const MULTICHANNEL_FIXTURES_TYPE = 'multi';

    const B2C_FIXTURES_PATH = 'Migrations/Data/B2C/ORM';
    const B2B_FIXTURES_PATH = 'Migrations/Data/B2B/ORM';
    const MULTICHANNEL_FIXTURES_PATH = 'Migrations/Data/Multi/ORM';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('oro:migration:live:demo:data:load')
            ->setDescription('Load live demo data fixtures.');
    }

    /**
     * @param InputInterapface $input
     * @return string
     */
    protected function getFixtureRelativePath(InputInterface $input)
    {
        $fixtureRelativePath = self::MAIN_FIXTURES_PATH;

        $fixtureTypeName = strtoupper($this->getTypeOfFixtures($input)) . '_FIXTURES_TYPE';
        $fixturePathName = strtoupper($this->getTypeOfFixtures($input)) . '_FIXTURES_PATH';


        if(constant("self::" .$fixtureTypeName) && constant("self::" .$fixturePathName))
        {
            $fixtureRelativePath = constant("self::" . $fixturePathName);
        }

        return str_replace('/', DIRECTORY_SEPARATOR, '/' . $fixtureRelativePath);
    }
}
