<?php

namespace OroCRMPro\Bundle\DemoDataBundle\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand as BaseDataFixturesCommand;

class LoadDataFixturesCommand extends BaseDataFixturesCommand
{
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
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $parentReturnCode = parent::execute($input, $output);
        if ($parentReturnCode === 0) {
            $command = $this->getApplication()->find('oro:cron:analytic:calculate');
            $input = new ArrayInput(['command' => 'oro:cron:analytic:calculate']);
            $command->run($input, $output);
        }
        return $parentReturnCode;
    }


    /**
     * @param InputInterface $input
     * @return string
     */
    protected function getFixtureRelativePath(InputInterface $input)
    {
        $fixtureRelativePath = self::MAIN_FIXTURES_PATH;
        if ($this->getTypeOfFixtures($input)) {
            $fixtureRelativePath = 'Migrations/Data/' . $this->getTypeOfFixtures($input) . '/ORM';
        }

        return str_replace('/', DIRECTORY_SEPARATOR, '/' . $fixtureRelativePath);
    }
}
