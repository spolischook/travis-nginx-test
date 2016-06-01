UPGRADE FROM 1.11 to 1.12
=========================

- The method OroCRM\Bundle\ChannelBundle\EventListener\UpdateIntegrationConnectorsListener::onChannelSucceedSave was renamed to onChannelSave
- The method OroCRM\Bundle\MagentoBundle\EventListener\UpdateIntegrationConnectorsListener::onChannelSucceedSave was renamed to onChannelSave
- The class OroCRM\Bundle\ChannelBundle\EventListener\ChangeChannelStatusListener was renamed to ChangeIntegrationStatusListener
- The class OroCRM\Bundle\ChannelBundle\EventListener\ChannelSaveSucceedListener was renamed to UpdateIntegrationConnectorsListener
- The class OroCRM\Bundle\MagentoBundle\EventListener\ChannelSaveSucceedListener was renamed to UpdateIntegrationConnectorsListener
- Removed support PHP version below 5.5.9

