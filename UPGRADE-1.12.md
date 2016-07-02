UPGRADE FROM 1.11 to 1.12
=========================

####OroProOrganizationBundle
- Removed parameter `OroCRM\Bundle\ChannelBundle\Provider\StateProvider $stateProvider` from constructor of `OroPro\Bundle\OrganizationBundle\Form\Handler\OrganizationProHandler` class
- Added parameter `EventDispatcherInterface $eventDispatcher` to constructor of `OroPro\Bundle\OrganizationBundle\Form\Handler\OrganizationProHandler` class
- Added `oro_format_datetime_organization` twig extension - allows get formatted date by user organization localization settings. Deprecated since 1.11. Will be removed after 1.13.
- Added `calendar_date_range_organization` twig extension - allows get formatted calendar date range by user organization localization settings. Deprecated since 1.11. Will be removed after 1.13.
