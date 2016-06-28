<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\FilterBundle\Event\ChoiceTreeFilterLoadDataEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceTreeFilterType;

class ChoiceTreeFilter extends AbstractFilter
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var RouterInterface */
    protected $router;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * ChoiceTreeFilter constructor.
     *
     * @param FormFactoryInterface $factory
     * @param FilterUtility $util
     * @param ManagerRegistry $registry
     * @param RouterInterface $router
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        ManagerRegistry $registry,
        RouterInterface $router,
        EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($factory, $util);
        $this->registry = $registry;
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return ChoiceTreeFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        $entities = [];

        if ($this->getOr('className') && isset($this->state[$this->name])) {
            $data = $this->parseData($this->state[$this->name]);
            
            $event = new ChoiceTreeFilterLoadDataEvent($this->getOr('className'), $data['value']);
            $this->eventDispatcher->dispatch(ChoiceTreeFilterLoadDataEvent::EVENT_NAME, $event);
            $entities = $event->getData();
        }

        $metadata[FilterUtility::TYPE_KEY] = 'choice-tree';
        $metadata['data'] = $entities;
        $metadata['autocomplete_alias'] = $this->getOr('autocomplete_alias') ?
            $this->getOr('autocomplete_alias') : false;
        $routeName = $this->getOr('autocomplete_url') ?
            $this->getOr('autocomplete_url') : 'oro_form_autocomplete_search';
        $metadata['autocomplete_url'] = $this->router->generate($routeName);
        $metadata['renderedPropertyName'] = $this->getOr('renderedPropertyName') ?
            $this->getOr('renderedPropertyName') : false;

        return $metadata;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $type =  $data['type'];
        if (count($data['value']) > 1 || (isset($data['value'][0]) && $data['value'][0] != "")) {
            $parameterName = $ds->generateParameterName($this->getName());

            $this->applyFilterToClause(
                $ds,
                $this->get(FilterUtility::DATA_NAME_KEY) . ' in (:'. $parameterName .')'
            );

            if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY], true)) {
                $ds->setParameter($parameterName, $data['value']);
            }
        }
        return true;
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    public function parseData($data)
    {
        $data['value'] = explode(',', $data['value']);
        return $data;
    }
}
