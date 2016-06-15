<?php

namespace OroPro\Bundle\OrganizationBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

use OroPro\Bundle\OrganizationBundle\Grid\DynamicFieldsExtension;

class DynamicFieldsExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Test\Entity';
    const ENTITY_NAME = 'Test:Entity';
    const FIELD_NAME = 'testField';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityClassResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $datagridConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $organizationConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $viewConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var DynamicFieldsExtension */
    protected $extension;

    protected function setUp()
    {
        $this->configManager       = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade      = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConfigProvider       = $this->getConfigProviderMock();
        $this->extendConfigProvider       = $this->getConfigProviderMock();
        $this->datagridConfigProvider     = $this->getConfigProviderMock();
        $this->organizationConfigProvider = $this->getConfigProviderMock();
        $this->viewConfigProvider         = $this->getConfigProviderMock();

        $this->configManager
            ->expects($this->any())
            ->method('getProvider')
            ->will(
                $this->returnValueMap(
                    [
                        ['entity', $this->entityConfigProvider],
                        ['extend', $this->extendConfigProvider],
                        ['datagrid', $this->datagridConfigProvider],
                        ['organization', $this->organizationConfigProvider],
                        ['view', $this->viewConfigProvider],
                    ]
                )
            );

        $this->extension = new DynamicFieldsExtension(
            $this->configManager,
            $this->entityClassResolver,
            new DatagridGuesserMock(),
            $this->securityFacade
        );
    }

    public function testProcessConfigs()
    {
        $this->securityFacade->expects($this->once())
            ->method('getOrganizationId')
            ->will($this->returnValue(1));

        $fieldLabel = 'test.field.label';
        $fieldType  = 'string';

        $this->entityClassResolver->expects($this->once())
            ->method('getEntityClass')
            ->with(self::ENTITY_NAME)
            ->will($this->returnValue(self::ENTITY_CLASS));

        $this->setExpectationForGetFields(self::ENTITY_CLASS, self::FIELD_NAME, $fieldType);

        $entityFieldConfig = new Config(
            new FieldConfigId('entity', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType)
        );
        $entityFieldConfig->set('label', $fieldLabel);
        $this->entityConfigProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->will($this->returnValue($entityFieldConfig));

        $datagridFieldConfig = new Config(
            new FieldConfigId('datagrid', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType)
        );
        $this->datagridConfigProvider
            ->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->will($this->returnValue($datagridFieldConfig));

        $viewFieldConfig = new Config(
            new FieldConfigId('view', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType)
        );
        $this->viewConfigProvider->expects($this->any())
            ->method('getConfig')
            ->with(self::ENTITY_CLASS, self::FIELD_NAME)
            ->will($this->returnValue($viewFieldConfig));

        $config = DatagridConfiguration::create(['extended_entity_name' => self::ENTITY_NAME]);
        $this->extension->processConfigs($config);
    }

    protected function setExpectationForGetFields($className, $fieldName, $fieldType)
    {
        $fieldId = new FieldConfigId('entity', $className, $fieldName, $fieldType);

        $extendConfig = new Config(new FieldConfigId('extend', $className, $fieldName, $fieldType));
        $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);
        $extendConfig->set('state', ExtendScope::STATE_ACTIVE);
        $extendConfig->set('is_deleted', false);

        $datagridConfig = new Config(new FieldConfigId('datagrid', $className, $fieldName, $fieldType));
        $datagridConfig->set('is_visible', DatagridScope::IS_VISIBLE_TRUE);

        $organizationConfig = new Config(
            new FieldConfigId('organization', self::ENTITY_CLASS, self::FIELD_NAME, $fieldType)
        );
        $organizationConfig->set('applicable', ['all' => true, 'selective' => []]);

        $this->configManager->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->will($this->returnValue(true));
        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->with($className)
            ->will($this->returnValue([$fieldId]));
        $this->extendConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($this->identicalTo($fieldId))
            ->will($this->returnValue($extendConfig));
        $this->datagridConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($this->identicalTo($fieldId))
            ->will($this->returnValue($datagridConfig));
        $this->organizationConfigProvider->expects($this->once())
            ->method('getConfigById')
            ->with($this->identicalTo($fieldId))
            ->will($this->returnValue($organizationConfig));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getConfigProviderMock()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
