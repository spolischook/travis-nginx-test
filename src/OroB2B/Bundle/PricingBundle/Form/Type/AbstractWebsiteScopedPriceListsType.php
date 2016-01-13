<?php

namespace OroB2B\Bundle\PricingBundle\Form\Type;

use OroB2B\Bundle\PricingBundle\Entity\PriceListFallback;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\PricingBundle\Event\PriceListCollectionChange;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceListRelation;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepositoryInterface;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;

abstract class AbstractWebsiteScopedPriceListsType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var  boolean */
    protected $changed;

    /**
     * @param object $targetEntity
     * @return BasePriceListRelation
     */
    abstract protected function createPriceListToTargetEntity($targetEntity);

    /**
     * @return string
     */
    abstract protected function getClassName();

    /**
     * @return string
     */
    abstract protected function getTargetFieldName();

    /**
     * @return array
     */
    abstract protected function getFallbackChoices();

    /**
     * @return string
     */
    abstract protected function getFallbackClassName();

    /**
     * @return string
     */
    abstract protected function getDefaultFallback();

    /**
     * @param ManagerRegistry $registry
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ManagerRegistry $registry, EventDispatcherInterface $eventDispatcher)
    {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData'], 10);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'], 10);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'type' => PriceListsSettingsType::NAME,
                'options' => [
                    'fallback_class_name' => $this->getFallbackClassName(),
                    'target_field_name' => $this->getTargetFieldName(),
                    'fallback_choices' => $this->getFallbackChoices(),
                    'default_fallback' => $this->getDefaultFallback(),
                ],
                'label' => false,
                'required' => false,
                'mapped' => false,
                'data' => [],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return WebsiteScopedDataType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm()->getParent();
        /** @var object|null $targetEntity */
        $targetEntity = $form->getData();

        if (!$targetEntity || !$targetEntity->getId()) {
            return;
        }

        /** @var FormInterface $priceListsByWebsites */
        $priceListsByWebsites = $form->get('priceListsByWebsites');

        $formData = $this->prepareFormData($targetEntity, $priceListsByWebsites);
        $event->setData($formData);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSubmit(FormEvent $event)
    {
        /** @var object|null $targetEntity */
        $targetEntity = $event->getForm()->getParent()->getData();
        if (!$targetEntity || !$targetEntity->getId()) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isValid()) {
            return;
        }

        /** @var FormInterface $priceListsByWebsites */
        $priceListsByWebsites = $form->getParent()->get('priceListsByWebsites');

        $em = $this->registry->getManagerForClass($this->getClassName());

        foreach ($priceListsByWebsites->all() as $priceListsByWebsite) {
            $this->changed = false;

            $website = $priceListsByWebsite->getConfig()->getOption('website');
            $submittedFallback = $priceListsByWebsite->get('fallback')->getData();
            $actualFallback = $this->getFallback(
                $this->getFallbackClassName(),
                $targetEntity,
                $this->getTargetFieldName(),
                $website
            );
            if ((!$actualFallback && $submittedFallback != $this->getDefaultFallback())
                || ($actualFallback && $submittedFallback != $actualFallback)
            ) {
                $this->changed = true;
            }

            $actualPriceListsToTargetEntity = $this->getActualPriceListsToTargetEntity($targetEntity, $website);

            $submittedPriceLists = $this->getWebsiteSubmittedPriceLists($priceListsByWebsite);

            /** @var BasePriceListRelation[] $actualPriceListsToTargetEntity */
            foreach ($actualPriceListsToTargetEntity as $priceListToTargetEntity) {
                if (!in_array($priceListToTargetEntity->getPriceList(), $submittedPriceLists)) {
                    $em->remove($priceListToTargetEntity);
                    $this->changed = true;
                }
            }

            foreach ($priceListsByWebsite->get(PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD)->all(
            ) as $priceListWithPriority) {
                $priceListWithPriorityData = $priceListWithPriority->getData();
                $this->updatePriceListToTargetEntity(
                    $em,
                    $targetEntity,
                    $website,
                    $priceListWithPriorityData,
                    $actualPriceListsToTargetEntity
                );
            }
            if ($this->changed) {
                $this->eventDispatcher->dispatch(
                    PriceListCollectionChange::BEFORE_CHANGE,
                    new PriceListCollectionChange($targetEntity, $website)
                );
            }
        }
    }

    /**
     * @param string $className
     * @param object $targetEntity
     * @param string $targetFieldName
     * @param Website $website
     * @return null|PriceListFallback
     */
    protected function getFallback($className, $targetEntity, $targetFieldName, Website $website)
    {
        /** @var PriceListFallback $fallback */
        return $this->registry
            ->getManagerForClass($className)
            ->getRepository($className)
            ->findOneBy([$targetFieldName => $targetEntity, 'website' => $website]);
    }

    /**
     * @param ObjectManager $em
     * @param object $targetEntity
     * @param Website $website
     * @param array $priceListWithPriorityData
     * @param array $actualPriceListsToTargetEntity
     */
    protected function updatePriceListToTargetEntity(
        ObjectManager $em,
        $targetEntity,
        Website $website,
        array $priceListWithPriorityData,
        array $actualPriceListsToTargetEntity
    ) {
        $priceList = $priceListWithPriorityData['priceList'];
        if (!$priceList instanceof PriceList) {
            return;
        }
        if (in_array($priceList->getId(), array_keys($actualPriceListsToTargetEntity))) {
            /** @var BasePriceListRelation $priceListToTargetEntity */
            $priceListToTargetEntity = $actualPriceListsToTargetEntity[$priceList->getId()];
            if ($priceListToTargetEntity->getPriority() != $priceListWithPriorityData['priority']
                || $priceListToTargetEntity->isMergeAllowed() != $priceListWithPriorityData['mergeAllowed']
            ) {
                $this->changed = true;
            }
        } else {
            $priceListToTargetEntity = $this->createPriceListToTargetEntity($targetEntity);
            $priceListToTargetEntity->setWebsite($website);
            $priceListToTargetEntity->setPriceList($priceListWithPriorityData['priceList']);
            $this->changed = true;
        }
        $priceListToTargetEntity->setPriority($priceListWithPriorityData['priority']);
        $priceListToTargetEntity->setMergeAllowed($priceListWithPriorityData['mergeAllowed']);
        $em->persist($priceListToTargetEntity);
    }

    /**
     * @param object $targetEntity
     * @param Website $website
     * @return PriceList[]
     */
    protected function getActualPriceListsToTargetEntity($targetEntity, Website $website)
    {
        /** @var PriceListRepositoryInterface $repo */
        $repo = $this->registry->getManagerForClass($this->getClassName())->getRepository($this->getClassName());
        $actualPriceListsToTargetEntity = $repo->getPriceLists($targetEntity, $website);

        $result = [];
        foreach ($actualPriceListsToTargetEntity as $priceListToTargetEntity) {
            $priceListId = $priceListToTargetEntity->getPriceList()->getId();
            $result[$priceListId] = $priceListToTargetEntity;
        }

        return $result;
    }

    /**
     * @param FormInterface $priceListsByWebsite
     * @return array
     */
    protected function getWebsiteSubmittedPriceLists($priceListsByWebsite)
    {
        $submittedPriceLists = [];

        foreach ($priceListsByWebsite->get(PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD)->getData() as $item) {
            $submittedPriceLists[] = $item['priceList'];
        }

        return $submittedPriceLists;
    }

    /**
     * @param object $targetEntity
     * @param FormInterface $priceListsByWebsites
     * @return array
     */
    protected function prepareFormData($targetEntity, FormInterface $priceListsByWebsites)
    {
        $formData = [];
        /** @var PriceListRepositoryInterface $repo */
        $repo = $this->registry->getManagerForClass($this->getClassName())->getRepository($this->getClassName());
        foreach ($priceListsByWebsites->all() as $priceListsByWebsite) {
            /** @var Website $website */
            $website = $priceListsByWebsite->getConfig()->getOption('website');
            $actualPriceListsToTargetEntity = $repo->getPriceLists($targetEntity, $website);

            $actualPriceLists = [];
            /** @var object $priceListToTargetEntity */
            foreach ($actualPriceListsToTargetEntity as $priceListToTargetEntity) {
                $priceLists['priceList'] = $priceListToTargetEntity->getPriceList();
                $priceLists['priority'] = $priceListToTargetEntity->getPriority();
                $priceLists['mergeAllowed'] = $priceListToTargetEntity->isMergeAllowed();

                $actualPriceLists[] = $priceLists;
            }

            $formData[$website->getId()][PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD] = $actualPriceLists;
        }

        return $formData;
    }
}
