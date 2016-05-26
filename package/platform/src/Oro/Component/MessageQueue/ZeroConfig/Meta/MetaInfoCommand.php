<?php
namespace Oro\Component\MessageQueue\ZeroConfig\Meta;

use Oro\Component\MessageQueue\ZeroConfig\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MetaInfoCommand extends Command
{
    /**
     * @var TopicMetaRegistry
     */
    private $topicRegistry;

    /**
     * @var Router
     */
    private $router;

    /**
     * @param TopicMetaRegistry $topicRegistry
     */
    public function __construct(TopicMetaRegistry $topicRegistry)
    {
        parent::__construct('oro:message-queue:zeroconfig:meta-info');

        $this->topicRegistry = $topicRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('A command shows all available topics and some information about them.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Topic', 'Description', 'Subscribers']);

        $count = 0;
        foreach ($this->topicRegistry->getTopicsMeta() as $topic) {
            $table->addRow([$topic->getName(), $topic->getDescription(), implode(PHP_EOL, $topic->getSubscribers())]);

            $count++;
        }

        $output->writeln(sprintf('Found %s topics', $count));
        $output->writeln('');
        $table->render();
    }
}
