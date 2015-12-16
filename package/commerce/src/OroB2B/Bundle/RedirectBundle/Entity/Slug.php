<?php

namespace OroB2B\Bundle\RedirectBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * @ORM\Table(name="orob2b_redirect_slug")
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-share-sign"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class Slug
{
    const DELIMITER = '/';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=1024)
     */
    protected $url;

    /**
     * @var string
     *
     * @ORM\Column(name="route_name", type="string", length=255, nullable=true)
     */
    protected $routeName;

    /**
     * @var array
     *
     * @ORM\Column(name="route_parameters", type="array")
     */
    protected $routeParameters = [];

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getSlugUrl()
    {
        $latestSlash = strrpos($this->url, self::DELIMITER);

        if ($latestSlash !== false) {
            return substr($this->url, $latestSlash + 1);
        } else {
            return $this->url;
        }
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * @param string $routeName
     * @return $this
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;

        return $this;
    }

    /**
     * @return array
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    /**
     * @param array $routeParameters
     * @return $this
     */
    public function setRouteParameters($routeParameters)
    {
        $this->routeParameters = $routeParameters;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getUrl();
    }
}
