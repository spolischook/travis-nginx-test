<?php

namespace OroCRMPro\Bundle\OutlookBundle\Tests\Unit\Manager;

use OroCRMPro\Bundle\OutlookBundle\Manager\AddInManager;

class AddInManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $assetHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $cache;

    protected function setUp()
    {
        $this->assetHelper = $this->getMockBuilder('Symfony\Component\Asset\Packages')
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache = $this->getMockBuilder('Doctrine\Common\Cache\CacheProvider')
            ->setMethods(['fetch', 'save'])
            ->getMockForAbstractClass();
    }

    /**
     * @param string $dir
     *
     * @return AddInManager
     */
    protected function createAddInManager($dir)
    {
        return new AddInManager(
            __DIR__ . '/Fixtures/' . $dir,
            'fixtures',
            $this->assetHelper,
            $this->cache
        );
    }

    public function testEmptyAddInDir()
    {
        $addInManager = $this->createAddInManager('files1');
        $this->cache->expects($this->any())->method('fetch')->willReturn(false);

        $this->assertNull($addInManager->getMinSupportedVersion());
        $this->assertNull($addInManager->getLatestVersion());
        $this->assertEquals([], $addInManager->getVersions());
        $this->assertEquals([], $addInManager->getFiles());
        $this->assertNull($addInManager->getFile('1.2'));
        $this->assertEquals([], $addInManager->getBinaries());
    }

    public function testOnlyOneBinaryFile()
    {
        $addInManager = $this->createAddInManager('files2');
        $this->cache->expects($this->any())->method('fetch')->willReturn(false);
        $this->assetHelper->expects($this->any())->method('getUrl')->willReturnArgument(0);

        $this->assertNull($addInManager->getMinSupportedVersion());
        $this->assertEquals('1.2.3', $addInManager->getLatestVersion());
        $this->assertEquals(['1.2.3'], $addInManager->getVersions());
        $this->assertEquals(
            [
                '1.2.3' => [
                    'name' => 'OroCRMOutlookAddIn_1.2.3.exe',
                    'url'  => 'fixtures/OroCRMOutlookAddIn_1.2.3.exe'
                ]
            ],
            $addInManager->getFiles()
        );
        $this->assertEquals(
            [
                'name' => 'OroCRMOutlookAddIn_1.2.3.exe',
                'url'  => 'fixtures/OroCRMOutlookAddIn_1.2.3.exe'
            ],
            $addInManager->getFile('1.2.3')
        );
        $this->assertEquals(
            ['OroCRMOutlookAddIn_1.2.3.exe' => 'fixtures/OroCRMOutlookAddIn_1.2.3.exe'],
            $addInManager->getBinaries()
        );
    }

    public function testSeveralFilesAndConfigFile()
    {
        $addInManager = $this->createAddInManager('files3');
        $this->cache->expects($this->any())->method('fetch')->willReturn(false);
        $this->assetHelper->expects($this->any())->method('getUrl')->willReturnArgument(0);

        $this->assertEquals('1.5', $addInManager->getMinSupportedVersion());
        $this->assertEquals('2.0', $addInManager->getLatestVersion());
        $this->assertEquals(['2.0', '2.0-beta1', '1.2.3'], $addInManager->getVersions());
        $this->assertEquals(
            [
                '2.0'       => [
                    'name'    => 'OroCRMOutlookAddIn_2.0.exe',
                    'url'     => 'fixtures/OroCRMOutlookAddIn_2.0.exe',
                    'doc_url' => 'fixtures/OroCRMOutlookAddIn_2.0.md'
                ],
                '2.0-beta1' => [
                    'name' => 'OroCRMOutlookAddIn_2.0-beta1.exe',
                    'url'  => 'fixtures/OroCRMOutlookAddIn_2.0-beta1.exe'
                ],
                '1.2.3'     => [
                    'name'    => 'OroCRMOutlookAddIn_1.2.3.exe',
                    'url'     => 'fixtures/OroCRMOutlookAddIn_1.2.3.exe',
                    'doc_url' => 'fixtures/OroCRMOutlookAddIn_1.2.3.md'
                ],
            ],
            $addInManager->getFiles()
        );
        $this->assertEquals(
            [
                'name' => 'OroCRMOutlookAddIn_2.0-beta1.exe',
                'url'  => 'fixtures/OroCRMOutlookAddIn_2.0-beta1.exe'
            ],
            $addInManager->getFile('2.0-beta1')
        );
        $this->assertEquals(
            [
                'OroCRMOutlookAddIn_2.0.exe'       => 'fixtures/OroCRMOutlookAddIn_2.0.exe',
                'OroCRMOutlookAddIn_2.0-beta1.exe' => 'fixtures/OroCRMOutlookAddIn_2.0-beta1.exe',
                'OroCRMOutlookAddIn_1.2.3.exe'     => 'fixtures/OroCRMOutlookAddIn_1.2.3.exe'
            ],
            $addInManager->getBinaries()
        );
    }

    public function testLoadFromCache()
    {
        $addInManager = $this->createAddInManager('files3');

        $cacheData = [
            'min_supported_version' => '1.5',
            'files'                 => [
                '2.0'       => [
                    'name'    => 'OroCRMOutlookAddIn_2.0.exe',
                    'url'     => 'fixtures/OroCRMOutlookAddIn_2.0.exe',
                    'doc_url' => 'fixtures/OroCRMOutlookAddIn_2.0.md'
                ],
                '2.0-beta1' => [
                    'name' => 'OroCRMOutlookAddIn_2.0-beta1.exe',
                    'url'  => 'fixtures/OroCRMOutlookAddIn_2.0-beta1.exe'
                ],
                '1.2.3'     => [
                    'name'    => 'OroCRMOutlookAddIn_1.2.3.exe',
                    'url'     => 'fixtures/OroCRMOutlookAddIn_1.2.3.exe',
                    'doc_url' => 'fixtures/OroCRMOutlookAddIn_1.2.3.md'
                ],
            ]
        ];

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(AddInManager::CACHE_KEY)
            ->willReturn($cacheData);
        $this->cache->expects($this->never())
            ->method('save');

        $this->assetHelper->expects($this->any())->method('getUrl')->willReturnArgument(0);

        $this->assertEquals('1.5', $addInManager->getMinSupportedVersion());
        $this->assertEquals(
            [
                'name'    => 'OroCRMOutlookAddIn_1.2.3.exe',
                'url'     => 'fixtures/OroCRMOutlookAddIn_1.2.3.exe',
                'doc_url' => 'fixtures/OroCRMOutlookAddIn_1.2.3.md'
            ],
            $addInManager->getFile('1.2.3')
        );
        $this->assertEquals(
            [
                'name'    => 'OroCRMOutlookAddIn_2.0.exe',
                'url'     => 'fixtures/OroCRMOutlookAddIn_2.0.exe',
                'doc_url' => 'fixtures/OroCRMOutlookAddIn_2.0.md'
            ],
            $addInManager->getFile('2.0')
        );
    }

    public function testSaveToCache()
    {
        $addInManager = $this->createAddInManager('files3');

        $cacheData = [
            'min_supported_version' => '1.5',
            'files'                 => [
                '2.0'       => [
                    'name'    => 'OroCRMOutlookAddIn_2.0.exe',
                    'url'     => 'fixtures/OroCRMOutlookAddIn_2.0.exe',
                    'doc_url' => 'fixtures/OroCRMOutlookAddIn_2.0.md'
                ],
                '2.0-beta1' => [
                    'name' => 'OroCRMOutlookAddIn_2.0-beta1.exe',
                    'url'  => 'fixtures/OroCRMOutlookAddIn_2.0-beta1.exe'
                ],
                '1.2.3'     => [
                    'name'    => 'OroCRMOutlookAddIn_1.2.3.exe',
                    'url'     => 'fixtures/OroCRMOutlookAddIn_1.2.3.exe',
                    'doc_url' => 'fixtures/OroCRMOutlookAddIn_1.2.3.md'
                ],
            ]
        ];

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with(AddInManager::CACHE_KEY)
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with(AddInManager::CACHE_KEY, $cacheData);

        $this->assetHelper->expects($this->any())->method('getUrl')->willReturnArgument(0);

        $this->assertEquals('1.5', $addInManager->getMinSupportedVersion());
        $this->assertEquals(
            [
                'name'    => 'OroCRMOutlookAddIn_1.2.3.exe',
                'url'     => 'fixtures/OroCRMOutlookAddIn_1.2.3.exe',
                'doc_url' => 'fixtures/OroCRMOutlookAddIn_1.2.3.md'
            ],
            $addInManager->getFile('1.2.3')
        );
        $this->assertEquals(
            [
                'name'    => 'OroCRMOutlookAddIn_2.0.exe',
                'url'     => 'fixtures/OroCRMOutlookAddIn_2.0.exe',
                'doc_url' => 'fixtures/OroCRMOutlookAddIn_2.0.md'
            ],
            $addInManager->getFile('2.0')
        );
    }
}
