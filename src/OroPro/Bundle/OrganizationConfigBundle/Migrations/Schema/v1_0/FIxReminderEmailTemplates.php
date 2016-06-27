<?php

namespace OroPro\Bundle\OrganizationConfigBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\ConnectionException;

use Psr\Log\LoggerInterface;

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
        $patternCalendarRange[] = <<<EOF
calendar_date_range(entity.start, entity.end, entity.allDay, 1)
EOF;
        $patternCalendarRange[] = <<<EOF
calendar_date_range(entity.start, entity.end, entity.allDay, 'F j, Y', 1)
EOF;
        $replacementCalendar = <<<EOF
|oro_format_datetime_organization({'organization': entity.calendar.organization.id})
EOF;
        // @codingStandardsIgnoreStart
        $replacementCalendarRange = <<<EOF
calendar_date_range_organization(entity.start, entity.end, entity.allDay, 1, null, null, null, entity.calendar.organization.id)
EOF;
        // @codingStandardsIgnoreEnd
        $replacementTask = <<<EOF
|oro_format_datetime_organization({'organization': entity.organization.id})
EOF;
        $replacementCalendarRangeTask = <<<EOF
calendar_date_range_organization(entity.start, entity.end, entity.allDay, 1, null, null, null, entity.organization.id)
EOF;

        $this->updateReminderTemplates(
            $logger,
            'calendar_reminder',
            $pattern,
            $replacementCalendar,
            $patternCalendarRange,
            $replacementCalendarRange
        );
        $this->updateReminderTemplates(
            $logger,
            'task_reminder',
            $pattern,
            $replacementTask,
            $patternCalendarRange,
            $replacementCalendarRangeTask
        );
    }

    /**
     * @param LoggerInterface $logger
     * @param string $templateName
     * @param string|array $pattern
     * @param string $replacement
     * @param string|array $patternCalendarRange
     * @param string $replacementCalendarRange
     * @throws \Exception
     * @throws ConnectionException
     */
    protected function updateReminderTemplates(
        LoggerInterface $logger,
        $templateName,
        $pattern,
        $replacement,
        $patternCalendarRange,
        $replacementCalendarRange
    ) {
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
                $content = str_replace($patternCalendarRange, $replacementCalendarRange, $content);
                $this->connection->update(
                    'oro_email_template',
                    ['subject' => $subject, 'content' => $content],
                    ['id' => $template['id']]
                );
            }
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }
}
