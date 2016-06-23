<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\ConnectionException;

use Psr\Log\LoggerInterface;

use Exception;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class FixReminderEmailTemplates extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update date format in reminder email templates using organization localization settings';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $pattern = <<<EOF
|date('F j, Y, g:i A')
EOF;
        $replacementCalendar = <<<EOF
|oro_format_datetime_organization({'organization': entity.calendar.organization.id})
EOF;

        $replacementTask = <<<EOF
|oro_format_datetime_organization({'organization': entity.organization.id})
EOF;

        $this->updateReminderTemplates($logger, 'calendar_reminder', $pattern, $replacementCalendar);
        $this->updateReminderTemplates($logger, 'task_reminder', $pattern, $replacementTask);
    }

    /**
     * @param LoggerInterface $logger
     * @param string $templateName
     * @param string $pattern
     * @param string $replacement
     * @throws Exception
     * @throws ConnectionException
     */
    protected function updateReminderTemplates(LoggerInterface $logger, $templateName, $pattern, $replacement)
    {
        $sql = 'SELECT * FROM oro_email_template WHERE name = :name ORDER BY id';
        $parameters = ['name' => $templateName];
        $types = ['name' => 'string'];

        $this->logQuery($logger, $sql, $parameters, $types);
        $templates = $this->connection->fetchAll($sql, $parameters, $types);

        try {
            $this->connection->beginTransaction();
            foreach ($templates as $template) {
                $subject = str_replace($pattern, $replacement, $template['subject']);
                $content = str_replace($pattern, $replacement, $template['content']);
                $this->connection->update(
                    'oro_email_template',
                    ['subject' => $subject, 'content' => $content],
                    ['id' => $template['id']]
                );
            }
            $this->connection->commit();
        } catch (Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
