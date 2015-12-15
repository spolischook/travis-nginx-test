<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Symfony\Component\Routing\Route;

use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Serializer;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\NavigationBundle\Entity\Title;
use Oro\Bundle\NavigationBundle\Title\TitleReader\ConfigReader;
use Oro\Bundle\NavigationBundle\Title\TitleReader\AnnotationsReader;
use Oro\Bundle\NavigationBundle\Title\StoredTitle;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TitleService implements TitleServiceInterface
{
    /**
     * Title template
     *
     * @var string
     */
    private $template;

    /**
     * Short title template
     *
     * @var string
     */
    private $shortTemplate;

    /**
     * Title data readers
     *
     * @var array
     */
    private $readers = [];

    /**
     * Current title template params
     *
     * @var array
     */
    private $params = [];

    /**
     * Current title suffix
     *
     * @var array
     */
    private $suffix = null;

    /**
     * Current title prefix
     *
     * @var array
     */
    private $prefix = null;

    /**
     * @var TitleProvider
     */
    private $titleProvider;

    /**
     * @var TitleTranslator
     */
    private $titleTranslator;

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var Serializer
     */
    protected $serializer = null;

    /**
     * @var ServiceLink
     */
    protected $breadcrumbManagerLink;

    /**
     * @var ConfigManager
     */
    protected $userConfigManager;

    /**
     * @param AnnotationsReader $reader
     * @param ConfigReader $configReader
     * @param TitleTranslator $titleTranslator
     * @param ObjectManager $em
     * @param Serializer $serializer
     * @param $userConfigManager
     * @param ServiceLink $breadcrumbManagerLink
     * @param TitleProvider $titleProvider
     */
    public function __construct(
        AnnotationsReader $reader,
        ConfigReader $configReader,
        TitleTranslator $titleTranslator,
        ObjectManager $em,
        Serializer $serializer,
        $userConfigManager,
        ServiceLink $breadcrumbManagerLink,
        TitleProvider $titleProvider
    ) {
        $this->readers = [$reader, $configReader];
        $this->titleTranslator = $titleTranslator;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->userConfigManager = $userConfigManager;
        $this->breadcrumbManagerLink = $breadcrumbManagerLink;
        $this->titleProvider = $titleProvider;
    }

    /**
     * @param BreadcrumbManagerInterface $breadcrumbManager
     *
     * @deprecated since 1.8 will be moved to constructor
     */
    public function setBreadcrumbManager(BreadcrumbManagerInterface $breadcrumbManager)
    {
        $this->breadcrumbManager = $breadcrumbManager;
    }

    /**
     * Return rendered translated title
     *
     * @param array  $params
     * @param string $title
     * @param string $prefix
     * @param string $suffix
     * @param bool   $isJSON
     * @param bool   $isShort
     * @return $this
     */
    public function render(
        $params = [],
        $title = null,
        $prefix = null,
        $suffix = null,
        $isJSON = false,
        $isShort = false
    ) {
        if (!is_null($title) && $isJSON) {
            try {
                /** @var $data \Oro\Bundle\NavigationBundle\Title\StoredTitle */
                $data =  $this->serializer->deserialize(
                    $title,
                    'Oro\Bundle\NavigationBundle\Title\StoredTitle',
                    'json'
                );

                $params = $data->getParams();
                if ($isShort) {
                    $title = $data->getShortTemplate();
                } else {
                    $title = $data->getTemplate();
                    $prefix = $data->getPrefix();
                    $suffix = $data->getSuffix();
                }
            } catch (RuntimeException $e) {
                // wrong json string - ignore title
                $params = [];
                $title  = 'Untitled';
                $prefix = '';
                $suffix = '';
            }
        }
        if (empty($params)) {
            $params = $this->getParams();
        }
        if ($isShort) {
            if (is_null($title)) {
                $title = $this->getShortTemplate();
            }
        } else {
            if (is_null($title)) {
                $title = $this->getTemplate();
            }
            if (is_null($prefix)) {
                $prefix = $this->prefix;
            }
            if (is_null($suffix)) {
                $suffix = $this->suffix;
            }
            $title = $prefix . $title . $suffix;
        }

        $title = $this->titleTranslator->trans($title, $params);

        return $title;
    }

    /**
     * Set properties from array
     *
     * @param array $values
     * @return $this
     */
    public function setData(array $values)
    {
        if (isset($values['titleTemplate'])
            && ($this->getTemplate() == null
                || (isset($values['force']) && $values['force']))
        ) {
            $this->setTemplate($values['titleTemplate']);
        }
        if (isset($values['titleShortTemplate'])) {
            $this->setShortTemplate($values['titleShortTemplate']);
        }
        if (isset($values['params'])) {
            $this->setParams($values['params']);
        }
        if (isset($values['prefix'])) {
            $this->setPrefix($values['prefix']);
        }
        if (isset($values['suffix'])) {
            $this->setSuffix($values['suffix']);
        }

        return $this;
    }

    /**
     * Set string suffix
     *
     * @param string $suffix
     * @return $this
     */
    public function setSuffix($suffix)
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Set string prefix
     *
     * @param string $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Set template string
     *
     * @param string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template string
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set short template string
     *
     * @param string $shortTemplate
     * @return $this
     */
    public function setShortTemplate($shortTemplate)
    {
        $this->shortTemplate = $shortTemplate;

        return $this;
    }

    /**
     * Get short template string
     *
     * @return string
     */
    public function getShortTemplate()
    {
        return $this->shortTemplate;
    }

    /**
     * Return params
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Setter for params
     *
     * @param array $params
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Load title template from database, fallback to config values
     *
     * @param string $route
     */
    public function loadByRoute($route)
    {
        $templates = $this->titleProvider->getTitleTemplates($route);
        if (!empty($templates)) {
            $this->setTemplate($templates['title']);
            $this->setShortTemplate($templates['short_title']);
        }
    }

    /**
     * Return stored titles repository
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getStoredTitlesRepository()
    {
        return $this->em->getRepository('Oro\Bundle\NavigationBundle\Entity\Title');
    }

    /**
     * Updates title index
     *
     * @param array $routes
     */
    public function update($routes)
    {
        $data = $routes;

        foreach ($this->readers as $reader) {
            /** @var $reader  \Oro\Bundle\NavigationBundle\Title\TitleReader\ReaderInterface */
            $data = array_merge($data, $reader->getData($routes));
        }

        $dbData = $this->getStoredTitlesRepository()->findAll();

        /** @var $entity Title */
        foreach ($dbData as $entity) {
            if (!array_key_exists($entity->getRoute(), $data)) {
                // remove not existing entries
                $this->em->remove($entity);

                continue;
            }

            $route = $entity->getRoute();
            $title = '';

            if (!$data[$route] instanceof Route) {
                $title = $data[$route];
            }

            // update existing system titles
            if ($entity->getIsSystem()) {
                $entity->setShortTitle($this->getShortTitle($title, $route));
                $title = $this->createTitle($route, $title);
                if (!$title) {
                    $title = '';
                }
                $entity->setTitle($title);
                $this->em->persist($entity);
            }

            unset($data[$route]);
        }

        // create title items for new routes
        foreach ($data as $route => $title) {
            if ($fullTitle = $this->createTitle($route, $title)) {
                $entity = new Title();
                $entity->setShortTitle($this->getShortTitle($title, $route));
                $entity->setTitle($fullTitle);
                $entity->setRoute($route);
                $entity->setIsSystem(true);

                $this->em->persist($entity);
            }
        }

        $this->em->flush();
    }

    protected function createTitle($route, $title)
    {
        if (!($title instanceof Route)) {
            $titleData = [];

            if ($title) {
                $titleData[] = $title;
            }

            $breadcrumbLabels = $this->getBreadcrumbs($route);
            if (count($breadcrumbLabels)) {
                $titleData = array_merge($titleData, $breadcrumbLabels);
            }

            if ($globalTitleSuffix = $this->userConfigManager->get('oro_navigation.title_suffix')) {
                $titleData[] = $globalTitleSuffix;
            }

            return implode(' ' . $this->userConfigManager->get('oro_navigation.title_delimiter') . ' ', $titleData);
        }

        return false;
    }

    /**
     * @param $route
     * @return array
     */
    protected function getBreadcrumbs($route)
    {
        return $this->breadcrumbManagerLink->getService()->getBreadcrumbLabels(
            $this->userConfigManager->get('oro_navigation.breadcrumb_menu'),
            $route
        );
    }

    /**
     * Get short title
     *
     * @param $title
     * @param $route
     */
    protected function getShortTitle($title, $route)
    {
        if (!$title) {
            $breadcrumbs = $this->getBreadcrumbs($route);
            if (count($breadcrumbs)) {
                $title = $breadcrumbs[0];
            }
        }

        return $title;
    }

    /**
     * Return serialized title data
     *
     * @return string
     */
    public function getSerialized()
    {
        $storedTitle = new StoredTitle();
        $storedTitle
            ->setTemplate($this->getTemplate())
            ->setShortTemplate($this->getShortTemplate())
            ->setParams($this->getParams())
            ->setPrefix($this->prefix)
            ->setSuffix($this->suffix);

        return $this->serializer->serialize($storedTitle, 'json');
    }
}
