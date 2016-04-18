<?php

namespace OroB2B\Bundle\WebsiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Table(name="orob2b_locale")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\WebsiteBundle\Entity\Repository\LocaleRepository")
 * @Config(
 *      mode="hidden",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-flag"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class Locale
{
    /**
     * @var Website
     *
     * @ORM\ManyToMany(targetEntity="Website", mappedBy="locales")
     */
    protected $websites;

    /**
     * @var Collection|Locale[]
     *
     * @ORM\OneToMany(targetEntity="Locale", mappedBy="parentLocale")
     */
    protected $childLocales;

    /**
     * @var Locale
     *
     * @ORM\ManyToOne(targetEntity="Locale", inversedBy="childLocales")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    protected $parentLocale;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $code;


    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $title;

    /**
     * @var \DateTime $createdAt
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.created_at"
     *          }
     *      }
     * )
     */
    protected $createdAt;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    public function __construct()
    {
        $this->childLocales = new ArrayCollection();
        $this->websites = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->title;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get child locales
     *
     * @return Collection|Locale[]
     */
    public function getChildLocales()
    {
        return $this->childLocales;
    }

    /**
     * Set children locales
     *
     * @param Collection|Locale[] $locales
     *
     * @return Locale
     */
    public function resetLocales($locales)
    {
        $this->childLocales->clear();

        foreach ($locales as $locale) {
            $this->addChildLocale($locale);
        }

        return $this;
    }

    /**
     * Add child locale
     *
     * @param Locale $locale
     * @return Locale
     */
    public function addChildLocale(Locale $locale)
    {
        if (!$this->childLocales->contains($locale)) {
            $this->childLocales->add($locale);
            $locale->setParentLocale($this);
        }

        return $this;
    }

    /**
     * Remove child locale
     *
     * @param Locale $locale
     * @return Locale
     */
    public function removeChildLocale(Locale $locale)
    {
        if ($this->childLocales->contains($locale)) {
            $this->childLocales->removeElement($locale);
            $locale->setParentLocale(null);
        }

        return $this;
    }

    /**
     * @return Locale
     */
    public function getParentLocale()
    {
        return $this->parentLocale;
    }

    /**
     * @param Locale $parentLocale
     * @return $this
     */
    public function setParentLocale(Locale $parentLocale = null)
    {
        $this->parentLocale = $parentLocale;

        return $this;
    }

    /**
     * Get websites with current locales
     *
     * @return Collection|Website[]
     */
    public function getWebsites()
    {
        return $this->websites;
    }

    /**
     * Set website for current locales
     *
     * @param Collection|Locale[] $websites
     *
     * @return Locale
     */
    public function resetWebsites($websites)
    {
        $this->websites->clear();

        foreach ($websites as $website) {
            $this->addWebsite($website);
        }

        return $this;
    }

    /**
     * @param Website $website
     *
     * @return Locale
     */
    public function addWebsite(Website $website)
    {
        if (!$this->websites->contains($website)) {
            $this->websites->add($website);
        }

        return $this;
    }

    /**
     * @param Website $website
     *
     * @return Website
     */
    public function removeWebsite(Website $website)
    {
        if ($this->websites->contains($website)) {
            $this->websites->removeElement($website);
        }

        return $this;
    }

    /**
     * Pre persist event listener
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * Pre update event handler
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return Locale
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }
}
