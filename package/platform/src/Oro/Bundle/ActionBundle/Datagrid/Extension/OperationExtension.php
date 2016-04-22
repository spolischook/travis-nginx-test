<?php

namespace Oro\Bundle\ActionBundle\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Provider\MassActionProviderRegistry;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Helper\OptionsHelper;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationManager;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension as DatagridActionExtension;

class OperationExtension extends AbstractExtension
{
    /** @var OperationManager */
    protected $operationManager;

    /** @var ContextHelper */
    protected $contextHelper;

    /** @var MassActionProviderRegistry */
    protected $providerRegistry;

    /** @var OptionsHelper */
    protected $optionsHelper;

    /** @var array */
    protected $datagridContext = [];

    /** @var array|Operation[] */
    protected $operations = [];

    /** @var array */
    protected $groups;

    /**
     * @param OperationManager $operationManager
     * @param ContextHelper $contextHelper
     * @param MassActionProviderRegistry $providerRegistry
     * @param OptionsHelper $optionsHelper
     */
    public function __construct(
        OperationManager $operationManager,
        ContextHelper $contextHelper,
        MassActionProviderRegistry $providerRegistry,
        OptionsHelper $optionsHelper
    ) {
        $this->operationManager = $operationManager;
        $this->contextHelper = $contextHelper;
        $this->providerRegistry = $providerRegistry;
        $this->optionsHelper = $optionsHelper;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        $this->datagridContext = $this->getDatagridContext($config);
        $this->operations = $this->getOperations(
            $config->offsetGetOr(DatagridActionExtension::ACTION_KEY, []),
            $this->datagridContext
        );

        if (0 === count($this->operations)) {
            return false;
        }

        $this->processActionsConfig($config);
        $this->processMassActionsConfig($config);

        $config->offsetSet(
            DatagridActionExtension::ACTION_CONFIGURATION_KEY,
            $this->getRowConfigurationClosure(
                $config->offsetGetOr(DatagridActionExtension::ACTION_CONFIGURATION_KEY, [])
            )
        );

        return true;
    }

    /**
     * Gets operations from registry if they not already exist in datagrid config as actions
     * @param array $datagridActionsConfig
     * @param array $datagridContext
     * @return Operation[]
     */
    protected function getOperations(array $datagridActionsConfig, array $datagridContext)
    {
        $result = [];

        $operations = $this->operationManager->getOperations($datagridContext, false);

        foreach ($operations as $operationName => $action) {
            $operationName = strtolower($operationName);
            if (!array_key_exists($operationName, $datagridActionsConfig)) {
                $result[$operationName] = $action;
            }
        }

        return $result;
    }

    /**
     * @param array|null|callable $actionConfiguration
     * @return \Closure
     */
    protected function getRowConfigurationClosure($actionConfiguration)
    {
        return function (ResultRecordInterface $record, array $config) use ($actionConfiguration) {
            $actionsNew = [];
            foreach ($this->operations as $operationName => $operation) {
                $actionsNew[$operationName] = $this->getRowOperationConfig(
                    $operation,
                    $record->getValue('id')
                );
            }

            return array_filter(
                array_merge($actionsNew, $this->retrieveConfiguration($actionConfiguration, $record, $config)),
                function ($item) {
                    return $item === false || is_array($item);
                }
            );
        };
    }

    /**
     * Retrieves parent action_configuration from callbacks
     * @param null|array|callable $actionConfiguration
     * @param ResultRecordInterface $record
     * @param array $config
     * @return array
     */
    protected function retrieveConfiguration($actionConfiguration, ResultRecordInterface $record, array $config)
    {
        if (empty($actionConfiguration)) {
            return [];
        }

        $rowActions = [];

        if (is_callable($actionConfiguration)) {
            $rowActions = call_user_func($actionConfiguration, $record, $config);
        } elseif (is_array($actionConfiguration)) {
            $rowActions = $actionConfiguration;
        }

        return is_array($rowActions) ? $rowActions : [];
    }

    /**
     * @param Operation $operation
     * @param mixed $entityId
     * @return bool|array
     */
    protected function getRowOperationConfig(Operation $operation, $entityId)
    {
        $context = [
            'entityId' => $entityId,
            'entityClass' => $this->datagridContext['entityClass'],
            'datagrid' => $this->datagridContext['datagrid'],
        ];

        $actionData = $this->contextHelper->getActionData($context);

        if (!$operation->isAvailable($actionData)) {
            return false;
        }

        $frontendOptions = $this->optionsHelper->getFrontendOptions($operation, $context);

        return $frontendOptions['options'];
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processActionsConfig(DatagridConfiguration $config)
    {
        $actionsConfig = $config->offsetGetOr(DatagridActionExtension::ACTION_KEY, []);

        foreach ($this->operations as $operationName => $operation) {
            $actionsConfig[$operationName] = $this->getRowsActionsConfig($operation, $operationName);
        }

        $config->offsetSet(DatagridActionExtension::ACTION_KEY, $actionsConfig);
    }

    /**
     * @param Operation $operation
     * @param string $operationName
     * @return array
     */
    protected function getRowsActionsConfig(Operation $operation, $operationName)
    {
        $buttonOptions = $operation->getDefinition()->getButtonOptions();
        $icon = !empty($buttonOptions['icon']) ? str_ireplace('icon-', '', $buttonOptions['icon']) : 'edit';

        return [
            'type' => 'action-widget',
            'label' => $operation->getDefinition()->getLabel(),
            'rowAction' => false,
            'link' => '#',
            'icon' => $icon,
            'options' => [
                'operationName' => $operationName,
            ]
        ];
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function processMassActionsConfig(DatagridConfiguration $config)
    {
        $actions = $config->offsetGetOr('mass_actions', []);

        foreach ($this->operations as $operation) {
            $datagridOptions = $operation->getDefinition()->getDatagridOptions();

            if (!empty($datagridOptions['mass_action_provider'])) {
                $provider = $this->providerRegistry->getProvider($datagridOptions['mass_action_provider']);

                if ($provider) {
                    foreach ($provider->getActions() as $name => $massAction) {
                        $actions[$operation->getName() . $name] = $massAction;
                    }
                }
            } elseif (!empty($datagridOptions['mass_action'])) {
                $actions[$operation->getName()] = array_merge(
                    [
                        'label' => $operation->getDefinition()->getLabel()
                    ],
                    $datagridOptions['mass_action']
                );
            }
        }

        $config->offsetSet('mass_actions', $actions);
    }

    /**
     * @param DatagridConfiguration $config
     * @return array
     */
    protected function getDatagridContext(DatagridConfiguration $config)
    {
        $entityClass = $config->offsetGetByPath('[extended_entity_name]');

        return [
            ContextHelper::ENTITY_CLASS_PARAM => $entityClass ?: $config->offsetGetByPath('[entity_name]'),
            ContextHelper::DATAGRID_PARAM => $config->offsetGetByPath('[name]'),
            ContextHelper::GROUP_PARAM => $this->groups,
        ];
    }
}
