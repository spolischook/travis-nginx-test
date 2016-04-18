<?php

namespace OroB2B\Bundle\MenuBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\FactoryInterface;

use Symfony\Bridge\Doctrine\RegistryInterface;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class DatabaseBuilder implements BuilderInterface
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var FactoryInterface
     */
    protected $factory;

    /**
     * @param RegistryInterface $registry
     * @param FactoryInterface $factory
     */
    public function __construct(RegistryInterface $registry, FactoryInterface $factory)
    {
        $this->registry = $registry;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function build($alias, array $options = [])
    {
        $root = $this->getRepository()->findMenuItemWithChildrenAndTitleByTitle($alias);
        $item = $this->factory->createItem($alias);
        $this->createFromMenuItem($item, $root, $options);
        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($alias)
    {
        return $this->getRepository()->findMenuItemByTitle($alias) !== null;
    }

    /**
     * @param array $options
     * @return Locale|null
     */
    protected function getLocale(array $options)
    {
        if (array_key_exists('extras', $options) && array_key_exists(MenuItem::LOCALE_OPTION, $options['extras'])
            && $options['extras'][MenuItem::LOCALE_OPTION] instanceof Locale
        ) {
            return $options['extras'][MenuItem::LOCALE_OPTION];
        }
        return null;
    }

    /**
     * @param ItemInterface $item
     * @param MenuItem $entity
     * @param array $options
     */
    protected function createFromMenuItem(ItemInterface $item, MenuItem $entity, array $options)
    {
        foreach ($entity->getChildren() as $childEntity) {
            $child = $item->addChild($childEntity->getTitle(), $this->menuItemEntityToArray($childEntity, $options));
            $this->createFromMenuItem($child, $childEntity, $options);
        }
    }

    /**
     * @param MenuItem $item
     * @param array $options
     * @return array
     */
    protected function menuItemEntityToArray(MenuItem $item, array $options)
    {
        $locale = $this->getLocale($options);
        $getData = function ($key) use ($item) {
            $data = $item->getData();
            return isset($data[$key]) ? $data[$key] : [];
        };
        return array_merge($options, [
            'uri' => $item->getUri(),
            'label' => $item->getTitle($locale)->getString(),
            'attributes' => $getData('attributes'),
            'linkAttributes' => $getData('linkAttributes'),
            'childrenAttributes' => $getData('childrenAttributes'),
            'labelAttributes' => $getData('labelAttributes'),
            'extras' => $getData('extras'),
            'display' => $item->getDisplay(),
            'displayChildren' => $item->getDisplayChildren(),
        ]);
    }

    /**
     * @return \OroB2B\Bundle\MenuBundle\Entity\Repository\MenuItemRepository
     */
    protected function getRepository()
    {
        return $this->registry->getManagerForClass('OroB2BMenuBundle:MenuItem')
            ->getRepository('OroB2BMenuBundle:MenuItem');
    }
}
