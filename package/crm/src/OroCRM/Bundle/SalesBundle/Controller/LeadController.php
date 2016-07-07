<?php

namespace OroCRM\Bundle\SalesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use OroCRM\Bundle\AccountBundle\Entity\Account;
use OroCRM\Bundle\SalesBundle\Entity\Lead;
use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\SalesBundle\Entity\Opportunity;
use OroCRM\Bundle\ContactBundle\Entity\Contact;

/**
 * @Route("/lead")
 */
class LeadController extends Controller
{
    /**
     * @Route("/view/{id}", name="orocrm_sales_lead_view", requirements={"id"="\d+"})
     * @Template
     * @Acl(
     *      id="orocrm_sales_lead_view",
     *      type="entity",
     *      permission="VIEW",
     *      class="OroCRMSalesBundle:Lead"
     * )
     */
    public function viewAction(Lead $lead)
    {
        $isLeadConvertible = $this
            ->get('orocrm_sales.provider.lead_to_opportunity')
            ->isLeadConvertibleToOpportunity($lead);

        $isDisqualifyAllowed = $this
            ->get('orocrm_sales.provider.lead_to_opportunity')
            ->isDisqualifyAllowed($lead);

        return array(
            'entity' => $lead,
            'isLeadConvertible' => $isLeadConvertible,
            'isDisqualifyAllowed' => $isDisqualifyAllowed
        );
    }

    /**
     * @Route("/info/{id}", name="orocrm_sales_lead_info", requirements={"id"="\d+"})
     * @AclAncestor("orocrm_sales_lead_view")
     * @Template()
     */
    public function infoAction(Lead $lead)
    {
        return array(
            'entity'  => $lead
        );
    }

    /**
     * @Route("/address-book/{id}", name="orocrm_sales_lead_address_book", requirements={"id"="\d+"})
     * @AclAncestor("orocrm_sales_lead_view")
     * @Template()
     */
    public function addressBookAction(Lead $lead)
    {
        return array(
            'entity' => $lead
        );
    }

    /**
     * Create lead form
     * @Route("/create", name="orocrm_sales_lead_create")
     * @Template("OroCRMSalesBundle:Lead:update.html.twig")
     * @Acl(
     *      id="orocrm_sales_lead_create",
     *      type="entity",
     *      permission="CREATE",
     *      class="OroCRMSalesBundle:Lead"
     * )
     */
    public function createAction()
    {
        return $this->update(new Lead());
    }

    /**
     * Update user form
     * @Route("/update/{id}", name="orocrm_sales_lead_update", requirements={"id"="\d+"}, defaults={"id"=0})
     *
     * @Template
     * @Acl(
     *      id="orocrm_sales_lead_update",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroCRMSalesBundle:Lead"
     * )
     */
    public function updateAction(Lead $entity)
    {
        return $this->update($entity);
    }

    /**
     * @Route(
     *      "/{_format}",
     *      name="orocrm_sales_lead_index",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Template
     * @AclAncestor("orocrm_sales_lead_view")
     */
    public function indexAction()
    {
        return [
            'entity_class' => $this->container->getParameter('orocrm_sales.lead.entity.class')
        ];
    }

    /**
     * @Route("/widget/account-leads/{id}", name="orocrm_sales_widget_account_leads", requirements={"id"="\d+"})
     * @AclAncestor("orocrm_sales_lead_view")
     * @Template()
     */
    public function accountLeadsAction(Account $account)
    {
        return array('entity' => $account);
    }

    /**
     * Create lead form with data channel
     *
     * @Route("/create/{channelIds}", name="orocrm_sales_lead_data_channel_aware_create")
     * @Template("OroCRMSalesBundle:Lead:update.html.twig")
     * @AclAncestor("orocrm_sales_lead_view")
     *
     * @ParamConverter(
     *      "channel",
     *      class="OroCRMChannelBundle:Channel",
     *      options={"id" = "channelIds"}
     * )
     */
    public function leadWithDataChannelCreateAction(Channel $channel)
    {
        $lead = new Lead();
        $lead->setDataChannel($channel);

        return $this->update($lead);
    }

    /**
     * @Route("/datagrid/lead-with-datachannel/{channelIds}", name="orocrm_sales_datagrid_lead_datachannel_aware")
     * @Template("OroCRMSalesBundle:Widget:entityWithDataChannelGrid.html.twig")
     * @AclAncestor("orocrm_sales_lead_view")
     */
    public function leadWithDataChannelGridAction($channelIds, Request $request)
    {
        $gridName = $request->query->get('gridName');

        if (!$gridName) {
            return $this->createNotFoundException('`gridName` Should be defined.');
        }

        return [
            'channelId'    => $channelIds,
            'gridName'     => $gridName,
            'params'       => $request->query->get('params', []),
            'renderParams' => $request->query->get('renderParams', []),
            'multiselect'  => $request->query->get('multiselect', false)
        ];
    }

    /**
     * Change status for lead
     *
     * @Route("/disqualify/{id}", name="orocrm_sales_lead_disqualify", requirements={"id"="\d+"})
     * @Acl(
     *      id="orocrm_sales_lead_disqualify",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroCRMSalesBundle:Lead"
     * )
     */
    public function disqualifyAction(Lead $lead)
    {
        if ($this->get('orocrm_sales.model.change_lead_status')->disqualify($lead)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('orocrm.sales.controller.lead.saved.message')
            );
        }

        return $this->redirectToRoute('orocrm_sales_lead_view', ['id' => $lead->getId()]);
    }

    /**
     * @Route("/convert/{id}", name="orocrm_sales_lead_convert_to_opportunity", requirements={"id"="\d+"})
     * @Acl(
     *      id="orocrm_sales_lead_convert_to_opportunity",
     *      type="entity",
     *      permission="EDIT",
     *      class="OroCRMSalesBundle:Lead"
     * )
     * @Template()
     */
    public function convertToOpportunityAction(Lead $lead, Request $request)
    {
        if (!$this->get('orocrm_sales.provider.lead_to_opportunity')->isLeadConvertibleToOpportunity($lead)) {
            throw new AccessDeniedException('Only one conversion per lead is allowed !');
        }

        $formId = $this->get('orocrm_sales.provider.lead_to_opportunity')->getFormId($lead);
        $opportunity = $this
            ->get('orocrm_sales.provider.lead_to_opportunity')
            ->prepareOpportunity($lead, $request);

        if ($this->get($formId . '.handler')->process($opportunity)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans('orocrm.sales.controller.opportunity.saved.message')
            );

            return $this->redirectToRoute('orocrm_sales_opportunity_view', ['id' => $opportunity->getId()]);
        }

        return [
            'entity'       => $opportunity,
            'form'         => $this->get($formId)->createView()
        ];
    }

    /**
     * @param Lead $entity
     *
     * @return array
     */
    protected function update(Lead $entity)
    {
        return $this->get('oro_form.model.update_handler')->update(
            $entity,
            $this->get('orocrm_sales.lead.form'),
            $this->get('translator')->trans('orocrm.sales.controller.lead.saved.message'),
            $this->get('orocrm_sales.lead.form.handler')
        );
    }
}
