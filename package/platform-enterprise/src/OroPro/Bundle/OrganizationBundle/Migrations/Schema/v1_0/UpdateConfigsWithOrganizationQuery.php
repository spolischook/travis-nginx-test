<?php

namespace OroPro\Bundle\OrganizationBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Types\Type;
use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class UpdateConfigsWithOrganizationQuery extends ParametrizedMigrationQuery
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
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $classNames = $this->getAllConfigurableEntities($logger);
        foreach ($classNames as $entityData) {
            $className       = $entityData['class_name'];
            $arrEntityConfig = $this->connection->convertToPHPValue($entityData['data'], 'array');

            if (!isset($arrEntityConfig['extend']['is_extend']) || $arrEntityConfig['extend']['is_extend'] != true) {
                continue;
            }

            if ($arrEntityConfig['extend']['owner'] == ExtendScope::OWNER_CUSTOM) {
                $arrEntityConfig['organization']['applicable'] = ['all' => true, 'selective' => []];
                $query  = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
                $params = ['data' => $arrEntityConfig, 'id' => $entityData['id']];
                $types  = ['data' => 'array', 'id' => 'integer'];
                $this->logQuery($logger, $query, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $params, $types);
                    $this->insertConfigIndexValue($entityData['id']);
                }
            }
            $fieldConfigs = $this->loadFieldConfigs($logger, $className);
            foreach ($fieldConfigs as $fieldConfig) {
                $data = $fieldConfig['data'];
                if (!isset($data['extend']['is_extend']) || $data['extend']['is_extend'] != true) {
                    continue;
                }
                $data['organization']['applicable'] = ['all' => true, 'selective' => []];

                $query  = 'UPDATE oro_entity_config_field SET data = :data WHERE id = :id';
                $params = ['data' => $data, 'id' => $fieldConfig['id']];
                $types  = ['data' => 'array', 'id' => 'integer'];
                $this->logQuery($logger, $query, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeUpdate($query, $params, $types);
                    $this->insertConfigIndexValue(null, $fieldConfig['id']);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return string[]
     */
    protected function getAllConfigurableEntities(LoggerInterface $logger)
    {
        $sql = 'SELECT id, class_name, data FROM oro_entity_config';
        $this->logQuery($logger, $sql);
        return $this->connection->fetchAll($sql);
    }

    /**
     * @param LoggerInterface $logger
     * @param string          $className
     *
     * @return array
     */
    protected function loadFieldConfigs(LoggerInterface $logger, $className)
    {
        $sql    = 'SELECT fc.id, fc.type, fc.field_name, fc.data'
            . ' FROM oro_entity_config ec'
            . ' INNER JOIN oro_entity_config_field fc ON fc.entity_id = ec.id'
            . ' WHERE ec.class_name = :class';
        $params = ['class' => $className];
        $types  = ['class' => 'string'];
        $this->logQuery($logger, $sql, $params, $types);

        $result = [];

        $rows = $this->connection->fetchAll($sql, $params, $types);
        foreach ($rows as $row) {
            $fieldName          = $row['field_name'];
            $result[$fieldName] = [
                'id'   => $row['id'],
                'type' => $row['type'],
                'data' => $this->connection->convertToPHPValue($row['data'], 'array')
            ];
        }

        return $result;
    }

    protected function insertConfigIndexValue($entityId = null, $fieldId = null)
    {
        $query = 'INSERT INTO oro_entity_config_index_value (entity_id, field_id, code, scope, value)
                  VALUES (:entity_id, :field_id, :code, :scope, :value)';
        $value = ['all' => true, 'selective' => []];
        $params = [
            'entity_id' => $entityId,
            'field_id'  => $fieldId,
            'code'      => 'applicable',
            'scope'     => 'organization',
            'value'     => $this->connection->convertToDatabaseValue($value, Type::JSON_ARRAY)
        ];
        $this->connection->executeQuery($query, $params);
    }
}
