<?php

namespace Oro\Bundle\AccountProBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class UpdateSharePermissionGroupNames extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $scheduledForUpdates = [];
        $permissions = $this->getSharePermissions($logger);

        foreach ($permissions as $permission) {
            $groupNames = $permission['group_names'];

            if (($key = array_search(AccountUser::SECURITY_GROUP, $groupNames, true)) !== false) {
                unset($groupNames[$key]);

                $scheduledForUpdates = [
                    'id' => $permission['id'],
                    'group_names' => $groupNames
                ];
            }
        }

        $this->executeUpdates($scheduledForUpdates, $logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return array
     */
    protected function getSharePermissions(LoggerInterface $logger)
    {
        $sql = 'SELECT id, group_names FROM oro_security_permission WHERE name = :permission_name';
        $params = ['permission_name' => 'SHARE'];
        $types = ['permission_name' => Type::STRING];

        $this->logQuery($logger, $sql, $params, $types);

        $result = [];
        $rows = $this->connection->fetchAll($sql, $params, $types);
        foreach ($rows as $row) {
            $result[] = [
                'id' => $row['id'],
                'group_names' => $this->connection->convertToPHPValue($row['group_names'], Type::TARRAY)
            ];
        }

        return $result;
    }

    /**
     * @param array           $rows
     * @param LoggerInterface $logger
     * @param                 $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    private function executeUpdates(array $rows, LoggerInterface $logger, $dryRun)
    {
        foreach ($rows as $row) {
            $sql = 'UPDATE oro_security_permission SET group_names = :group_names WHERE id = :id';
            $params = ['group_names' => $row['group_names'], 'id' => $row['id']];
            $types = ['group_names' => Type::TARRAY, 'id' => Type::INTEGER];
            $this->logQuery($logger, $sql, $params, $types);

            if (!$dryRun) {
                $this->connection->executeUpdate($sql, $params, $types);
            }
        }
    }
}
