<?php

namespace Oro\Bundle\DataGridBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Entity\GridView;

class GridViewApiHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var Registry */
    protected $registry;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param Registry $registry
     */
    public function __construct(FormInterface $form, Request $request, Registry $registry)
    {
        $this->form = $form;
        $this->request = $request;
        $this->registry = $registry;
    }

    /**
     * @param GridView $entity
     *
     * @return boolean
     */
    public function process(GridView $entity)
    {
        $entity->setFiltersData();
        $entity->setSortersData();
        $entity->setColumnsData();

        $this->form->setData($entity);
        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            $data = $this->request->request->all();
            unset($data['name']);
            if ($this->form->has('owner')) {
                $data['owner'] = $entity->getOwner();
            }
            $this->form->submit($data);

            if ($this->form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * @param GridView $entity
     */
    protected function onSuccess(GridView $entity)
    {
        $this->fixFilters($entity);
        $om = $this->registry->getManagerForClass('OroDataGridBundle:GridView');
        $om->persist($entity);
        $om->flush();
    }

    /**
     * @todo Remove once https://github.com/symfony/symfony/issues/5906 is fixed
     *
     * @param GridView $gridView
     */
    protected function fixFilters(GridView $gridView)
    {
        $filters = $gridView->getFiltersData();
        foreach ($filters as $name => $filter) {
            if (array_key_exists('type', $filter) && $filter['type'] == null) {
                $filters[$name]['type'] = '';
            }
        }

        $gridView->setFiltersData($filters);
    }
}
