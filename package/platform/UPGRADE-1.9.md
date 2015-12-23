UPGRADE FROM 1.8 to 1.9
=======================

####ActivityBundle
- Services with tag `oro_activity.activity_widget_provider` was marked as private

####ActivityListBundle
- `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setEditor` deprecated since 1.8.0. Will be removed in 1.10.0. Use `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setUpdatedBy` instead.
- `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getEditor` deprecated since 1.8.0. Will be removed in 1.10.0. Use `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getUpdatedBy` instead.
- `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getDate` removed. Use `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getCreatedAt` and `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getUpdatedAt` instead
- `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::isDateUpdatable` removed. It is not needed.
- `Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface::getOwner` added.

####AddressBundle
- `oro_address.address.manager` service was marked as private
- Validation `AbstractAddress::isRegionValid` was moved to `Oro\Bundle\AddressBundle\Validator\Constraints\ValidRegion` constraint

####CalendarBundle
- `oro_calendar.calendar_provider.user` service was marked as private
- `oro_calendar.calendar_provider.system` service was marked as private
- `oro_calendar.calendar_provider.public` service was marked as private

####ConfigBundle
- An implementation of scope managers has been changed to be simpler and performant. This can bring a `backward compatibility break` if you have own scope managers. See [add_new_config_scope.md](./src/Oro/Bundle/ConfigBundle/Resources/doc/add_new_config_scope.md) and the next items for more detailed info.
- Method `loadStoredSettings` of `Oro\Bundle\ConfigBundle\Config\AbstractScopeManager` is `protected` now.
- Constructor for `Oro\Bundle\ConfigBundle\Config\AbstractScopeManager` changed. New arguments: `ManagerRegistry $doctrine, CacheProvider $cache`.
- Removed methods `loadSettings`, `getByEntity` of `Oro\Bundle\ConfigBundle\Entity\Repository\ConfigRepository`.
- Removed method `loadStoredSettings` of `Oro\Bundle\ConfigBundle\Config\ConfigManager`.
- Removed class `Oro\Bundle\ConfigBundle\Manager\UserConfigManager` and service `oro_config.user_config_manager`. Use `oro_config.user` service instead.

####DataAuditBundle
- `Oro\Bundle\DataAuditBundle\EventListener\KernelListener` added to the class cache and constructor have container as performance improvement
- `Oro\Bundle\DataAuditBundle\Entity\AbstractAudit` has `@InheritanceType("SINGLE_TABLE")`
- `audit-grid` and `audit-history-grid` based on `Oro\Bundle\DataAuditBundle\Entity\AbstractAudit` now. Make join to get your entity on grid

####DataGridBundle
- Services with tag `oro_datagrid.extension.formatter.property` was marked as private
- JS collection models format changed to maintain compatibility with Backbone collections: now it is always list of models, and additional parameters are passed through the options 
- Grid merge uses distinct policy

```
grid-name:
    source:
        value: 1
grid-name:
    source:
        value: 2
```

will result

```
grid-name:
    source:
        value: 2
```

instead of

```
grid-name:
    source:
        value:
            - 1
            - 2
```

####DistributionBundle:
- Fix `priority` attribute handling for `routing.options_resolver` tag to be conform Symfony standards. New behaviour: the higher the priority, the sooner the resolver gets executed.

####EmailBundle
- Method `setFolder` of `Oro\Bundle\EmailBundle\Entity\EmailUser` marked as deprecated. Use the method `addFolder` instead.
- `oro_email.emailtemplate.variable_provider.entity` service was marked as private
- `oro_email.emailtemplate.variable_provider.system` service was marked as private
- `oro_email.emailtemplate.variable_provider.user` service was marked as private 

####EmbeddedFormBundle
- Bundle now contains configuration of security firewall `embedded_form`

####EntityBundle
- Class `Oro\Bundle\EntityBundle\ORM\QueryUtils` marked as deprecated. Use `Oro\Component\DoctrineUtils\ORM\QueryUtils` instead.
- Class `Oro\Bundle\EntityBundle\ORM\SqlQuery` marked as deprecated. Use `Oro\Component\DoctrineUtils\ORM\SqlQuery` instead.
- Class `Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder` marked as deprecated. Use `Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder` instead.
- Methods `getSingleRootAlias`, `getPageOffset`, `applyJoins` and `normalizeCriteria` of `Oro\Bundle\EntityBundle\ORM\DoctrineHelper` marked as deprecated. Use corresponding methods of `Oro\Component\DoctrineUtils\ORM\QueryUtils` instead.
- `oro_entity.entity_hierarchy_provider` service was marked as private.
- `oro_entity.entity_hierarchy_provider.class` parameter was removed.
- `oro_entity.entity_hierarchy_provider.all` service was added. It can be used if you need a hierarchy of all entities but not only configurable ones.

####EntityConfigBundle
- Removed `optionSet` field type deprecated since v1.4. Existing options sets are converted to `Select` or `Multi-Select` automatically during the Platform update.
- `Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface` marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` instead.
- Renamed `Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel` to `Oro\Bundle\EntityConfigBundle\Entity\ConfigModel`.
- Constants `MODE_DEFAULT`, `MODE_HIDDEN` and `MODE_READONLY` of `Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager` marked as deprecated. Use the same constants of `Oro\Bundle\EntityConfigBundle\Entity\ConfigModel` instead. Also `isDefault()`, `isHidden()` and `isReadOnly()` methods of `Oro\Bundle\EntityConfigBundle\Entity\ConfigModel` can be used.
- Method `clearCache` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `clearCache` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the `getConfigManager()` of the ConfigProvider.
- Method `persist` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `persist` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the `getConfigManager()` of the ConfigProvider.
- Method `merge` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `merge` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the `getConfigManager()` of the ConfigProvider.
- Method `flush` of `Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider` marked as deprecated. Use the method `flush` of `Oro\Bundle\EntityConfigBundle\Config\ConfigManager` instead. The ConfigManager can be retrieved using the `getConfigManager()` of the ConfigProvider.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::NEW_ENTITY_CONFIG` (`entity_config.new.entity.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::CREATE_ENTITY` (`oro.entity_config.entity.create`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_ENTITY_CONFIG` (`entity_config.update.entity.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_ENTITY` (`oro.entity_config.entity.update`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::NEW_FIELD_CONFIG` (`entity_config.new.field.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::CREATE_FIELD` (`oro.entity_config.field.create`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_FIELD_CONFIG` (`entity_config.update.field.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::UPDATE_FIELD` (`oro.entity_config.field.update`) instead.
- Event name `Oro\Bundle\EntityConfigBundle\Event\Events::RENAME_FIELD` is renamed from `entity_config.rename.field` to `oro.entity_config.field.rename`. Old event marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::RENAME_FIELD` (`oro.entity_config.field.rename`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::PRE_PERSIST_CONFIG` (`entity_config.persist.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::PRE_FLUSH` (`oro.entity_config.pre_flush`) instead.
- Event `Oro\Bundle\EntityConfigBundle\Event\Events::POST_FLUSH_CONFIG` (`entity_config.flush.config`) marked as deprecated. Use `Oro\Bundle\EntityConfigBundle\Event\Events::POST_FLUSH` (`oro.entity_config.post_flush`) instead.
- New `Oro\Bundle\EntityConfigBundle\Migration\RemoveEnumFieldQuery` added. It using for remove outdated enum field data for entity.

####EntityExtendBundle
- Added parameters `Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider` to constructor of `Oro\Bundle\EntityExtendBundle\Form\Type\FieldType`
- Services with tag `oro_entity_extend.entity_config_dumper_extension` was marked as private
- Services with tag `oro_entity_extend.entity_generator_extension` was marked as private

####EntitySerializer component
- `Oro\Component\EntitySerializer\EntitySerializer` class has a lot of changes. This can bring a `backward compatibility break` if you have inherited classes.
- `excluded_fields` attribute is marked as deprecated. Use `exclude` attribute for a field.
- `orderBy` attribute is marked as deprecated. Use `order_by` attribute instead.
- `result_name` attribute is marked as deprecated. Use `property_path` attribute instead.

before:
```
    'primary' => ['result_name' => 'isPrimary']
```
after:
```
    'isPrimary' => ['property_path' => 'primary']
```
- The signature of `post_serialize` callback is changed. Old signature: `function (array &$item) : void`. New signature: `function (array $item) : array`.
- Now `post_serialize` callback is called before data normalization. This can bring a `backward compatibility break` if you use `post_serialize` callback together with `result_name` attribute. Use original field names instead of renamed ones in `post_serialize` callbacks.

before:
```
    'fields' => [
        'firstName' => null,
        'lastName' => ['result_name' => 'surName']
    ],
    `post_serialize` => function (array &$item) {
        $item['fullName'] = $item['firstName'] . ' ' . $item['surName'];
    }
```
after:
```
    'fields' => [
        'firstName' => null,
        'lastName' => ['result_name' => 'surName']
    ],
    `post_serialize` => function (array $item) {
        $item['fullName'] = $item['firstName'] . ' ' . $item['lastName'];
        return $item;
    }
```
- The `EntitySerializer` changed to accept existing joins. See https://github.com/orocrm/platform/issues/283.

####FilterBundle
- Services with tag `oro_filter.extension.orm_filter.filter` was marked as private

####FormBundle
- Add new form type: `oro_autocomplete`. See [text_autocomplete_form_type.md](./src/Oro/Bundle/FormBundle/Resources/doc/reference/text_autocomplete_form_type.md) for more detailed info.

####ImportExportBundle
- `Oro\Bundle\ImportExportBundle\Writer\EntityDetachFixer`: the first argument of constructor `Doctrine\ORM\EntityManager $entityManager` replaced by `Oro\Bundle\EntityBundle\ORM\DoctrineHelper $doctrineHelper`
- `Oro\Bundle\ImportExportBundle\Writer\EntityWriter`: the first argument of constructor `Doctrine\ORM\EntityManager $entityManager` replaced by `Oro\Bundle\EntityBundle\ORM\DoctrineHelper $doctrineHelper`
- `Oro\Bundle\ImportExportBundle\Writer\DoctrineClearWriter`: the first argument of constructor `Doctrine\ORM\EntityManager $entityManager` replaced by `Doctrine\Common\Persistence\ManagerRegistry $registry`
- `Oro\Bundle\ImportExportBundle\Writer\DummyWriter`: the first argument of constructor `Doctrine\ORM\EntityManager $entityManager` replaced by `Doctrine\Common\Persistence\ManagerRegistry $registry`
- `Oro\Bundle\ImportExportBundle\Writer` second argument `Oro\Bundle\EntityBundle\Provider\EntityFieldProvider` `oro_entity.entity_field_provider` service replaced with `Oro\Bundle\ImportExportBundle\Field\FieldHelper` `oro_importexport.field.field_helper`
- Added `Oro\Bundle\ImportExportBundle\Formatter\ExcelDateTimeTypeFormatter` as default formatter for the date, time and datetime types in `Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DateTimeNormalizer`. This types exported/imported depends on the application locale and timezone and recognized as dates in Microsoft Excel.
- `Oro\Bundle\ImportExportBundle\Field\DatabaseHelper::getRegistry` is deprecated. Use class methods instead of disposed registry
- Services with tag `oro_importexport.normalizer` was marked as private

####InstallerBundle
- `Oro\Bundle\InstallerBundle\EventListener\RequestListener` added to the class cache as performance improvement

####LayoutBundle
- `Oro\Bundle\LayoutBundle\EventListener\ThemeListener` added to the class cache as performance improvement

####LocaleBundle
- `Oro\Bundle\LocaleBundle\EventListener\LocaleListener` added to the class cache and constructor have container as performance improvement

####MigrationBundle
- Services with tag `oro_migration.extension` was marked as private

####NavigationBundle
- `Oro\Bundle\NavigationBundle\Event\AddMasterRequestRouteListener` added to the class cache as performance improvement
- `Oro\Bundle\NavigationBundle\Event\RequestTitleListener` added to the class cache as performance improvement

####NoteBundle
 - Added parameter `DoctrineHelper $doctrineHelper` to constructor of `\Oro\Bundle\NoteBundle\Placeholder\PlaceholderFilter`

####PlatformBundle
- Bundle now has priority `-200` and it is loaded right after main Symfony bundles
- Services with tag `doctrine.event_listener` was marked as private

####SearchBundle
- SearchBundle now uses own EntityManager with `search` name. use `connection: search` in tag definition to listen its events

####SecurityBundle
- `Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface` is changed. New method `buildTree` added (due to performance issues). It should be called once after all `addDeepEntity` calls. See [OwnerTreeProvider](./src/Oro/Bundle/SecurityBundle/Owner/OwnerTreeProvider.php) method `fillTree`. Implementation example [OwnerTree](./src/Oro/Bundle/SecurityBundle/Owner/OwnerTree.php).
- Bundle now contains part of Symfony security configuration (ACL configuration and access decision manager strategy) 
- `Oro\Bundle\SecurityBundle\Http\Firewall\ContextListener` added to the class cache and constructor have container as performance improvement
- `Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactoryInterface` and its implementation `Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationTokenFactory` were introduced to encapsulate creation of `UsernamePasswordOrganizationToken` in `Oro\Bundle\SecurityBundle\Authentication\Provider\UsernamePasswordOrganizationAuthenticationProvider` and `Oro\Bundle\SecurityBundle\Http\Firewall\OrganizationBasicAuthenticationListener`
- `Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactoryInterface` and its implementation `Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationRememberMeTokenFactory` were introduced to encapsulate creation of `OrganizationRememberMeToken` in `Oro\Bundle\SecurityBundle\Authentication\Provider\UsernamePasswordOrganizationAuthenticationProvider`

####SidebarBundle
- `Oro\Bundle\SidebarBundle\EventListener\RequestHandler` added to the class cache as performance improvement

####SSOBundle
- `Oro\Bundle\SSOBundle\Security\OAuthTokenFactoryInterface` and its implementation `Oro\Bundle\SSOBundle\Security\OAuthTokenFactory` were introduced to encapsulate creation of `OAuthToken` in `Oro\Bundle\SSOBundle\Security\OAuthProvider`

####SoapBundle
- Bundle now contains configuration of security firewall `wsse_secured` 
- `Oro\Bundle\SoapBundle\EventListener\LocaleListener` added to the class cache and constructor have container as performance improvement

####TrackingBundle
- Bundle now contains configuration of security firewall `tracking_data`

####TranslationBundle
- `/Resources/translations/tooltips.*.yml` deprecated since 1.9.0. Will be removed in 1.11.0. Use `/Resources/translations/messages.*.yml` instead

####UiBundle
- Added possibility to group tabs in dropdown for tabs panel. Added options to tabPanel function. Example: `{{ tabPanel(tabs, {useDropdown: true}) }}`
- Added possibility to set content for specific tab. Example: `{{ tabPanel([{label: 'Tab', content: 'Tab content'}]) }}`
- `Oro\Bundle\UIBundle\EventListener\ContentProviderListener` added to the class cache and constructor have container as performance improvement
- Services with tag `oro_ui.content_provider` was marked as private
- Services with tag `oro_formatter` was marked as private

####UserBundle
- Bundle now contains configuration of security providers (`chain_provider`, `oro_user`, `in_memory`), encoders and security firewalls (`login`, `reset_password`, `main`)
- Bundle DI extension `OroUserExtension` has been updated to make sure that `main` security firewall is always the last in list
- `Oro\Bundle\UserBundle\Security\WsseTokenFactoryInterface` and its implementation `Oro\Bundle\UserBundle\Security\WsseTokenFactory` were introduced to encapsulate creation of `WsseToken` in `Oro\Bundle\UserBundle\Security\WsseAuthProvider`

####WorkflowBundle
- Constructor of `Oro\Bundle\WorkflowBundle\Model\Process` changed. New argument: `ConditionFactory $conditionFactory`
- Constructor of `Oro\Bundle\WorkflowBundle\Model\ProcessFactory` changed. New argument: `ConditionFactory $conditionFactory`
- Added new process definition option `pre_conditions`
- Class `Oro\Bundle\WorkflowBundle\Model\WorkflowManager` now has method `massTransit` to perform several transitions in one transaction, can be used to improve workflow performance
- Services with tag `oro_workflow.condition` was marked as private 
- Services with tag `oro_workflow.action` was marked as private 
- Route `oro_workflow_api_rest_process_activate` marked as deprecated. Use the route `oro_api_process_activate` instead.
- Route `oro_workflow_api_rest_process_deactivate` marked as deprecated. Use the route `oro_api_process_deactivate` instead.
- Route `oro_workflow_api_rest_workflowdefinition_get` marked as deprecated. Use the route `oro_api_workflow_definition_get` instead.
- Route `oro_workflow_api_rest_workflowdefinition_post` marked as deprecated. Use the route `oro_api_workflow_definition_post` instead.
- Route `oro_workflow_api_rest_workflowdefinition_put` marked as deprecated. Use the route `oro_api_workflow_definition_put` instead.
- Route `oro_workflow_api_rest_workflowdefinition_delete` marked as deprecated. Use the route `oro_api_workflow_definition_delete` instead.
- Route `oro_workflow_api_rest_entity_get` marked as deprecated. Use the route `oro_api_workflow_entity_get` instead.
- Route `oro_workflow_api_rest_workflow_get` marked as deprecated. Use the route `oro_api_workflow_get` instead.
- Route `oro_workflow_api_rest_workflow_delete` marked as deprecated. Use the route `oro_api_workflow_delete` instead.
- Route `oro_workflow_api_rest_workflow_activate` marked as deprecated. Use the route `oro_api_workflow_activate` instead.
- Route `oro_workflow_api_rest_workflow_deactivate` marked as deprecated. Use the route `oro_api_workflow_deactivate` instead.
- Route `oro_workflow_api_rest_workflow_start` marked as deprecated. Use the route `oro_api_workflow_start` instead.
- Route `oro_workflow_api_rest_workflow_transit` marked as deprecated. Use the route `oro_api_workflow_transit` instead.

####OroIntegrationBundle
- `Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository::addStatus` marked as deprecated since 1.9.0. Will be removed in 1.11.0. Use `Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository::addStatusAndFlush` instead.
- Added possibility to skip connectors during synchronization using implemenation of `Oro\Bundle\IntegrationBundle\Provider\AllowedConnectorInterface`. 
- Added possibility to sort connectors execution order using implementation of `Oro\Bundle\IntegrationBundle\Provider\OrderedConnectorInterface`.
