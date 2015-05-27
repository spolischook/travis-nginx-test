<?php

namespace OroCRMPro\Bundle\OutlookBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\IdentifierToReferenceFilter;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use OroCRMPro\Bundle\OutlookBundle\Entity\Manager\EmailEntityApiEntityManager;

/**
 * @RouteResource("entity")
 * @NamePrefix("orocrmpro_api_outlook_")
 */
class EmailEntityController extends RestGetController
{
    /**
     * Returns the list of entities associated with the specified email.
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10."
     * )
     * @QueryParam(
     *      name="emailId",
     *      requirements="\d+",
     *      nullable=true,
     *      description="The email identifier."
     * )
     * @QueryParam(
     *     name="messageId",
     *     requirements=".+",
     *     nullable=true,
     *     description="The email 'Message-ID' attribute. One or several message ids separated by comma."
     * )
     * @QueryParam(
     *      name="from",
     *      requirements=".+",
     *      nullable=true,
     *      description="The email sender address. One or several addresses separated by comma."
     * )
     * @QueryParam(
     *      name="to",
     *      requirements=".+",
     *      nullable=true,
     *      description="The email recipient address. One or several addresses separated by comma."
     * )
     * @QueryParam(
     *      name="cc",
     *      requirements=".+",
     *      nullable=true,
     *      description="The email address of carbon copy recipient. One or several addresses separated by comma."
     * )
     * @QueryParam(
     *      name="bcc",
     *      requirements=".+",
     *      nullable=true,
     *      description="The email address of blind carbon copy recipient. One or several addresses separated by comma."
     * )
     * @QueryParam(
     *      name="subject",
     *      requirements=".+",
     *      nullable=true,
     *      description="The email subject."
     * )
     * @ApiDoc(
     *      description="Returns the list of entities associated with the specified email",
     *      resource=true
     * )
     *
     * @AclAncestor("oro_email_view")
     *
     * @return Response
     */
    public function cgetAction()
    {
        $manager = $this->getManager();

        $emailId = $this->getRequest()->get('emailId');
        if ($emailId === null) {
            $emailId = $manager->findEmailId(
                $this->getFilterCriteria(
                    array_diff($this->getSupportedQueryParameters(__FUNCTION__), ['emailId']),
                    [
                        'messageId' => new StringToArrayParameterFilter(),
                        'from'      => new StringToArrayParameterFilter(),
                        'to'        => new StringToArrayParameterFilter(),
                        'cc'        => new StringToArrayParameterFilter(),
                        'bcc'       => new StringToArrayParameterFilter()
                    ],
                    [
                        'from' => 'fromEmailAddress.email',
                        'to'   => 'toAddress.email',
                        'cc'   => 'ccAddress.email',
                        'bcc'  => 'bccAddress.email'
                    ]
                ),
                [
                    'fromEmailAddress' => null,
                    'toRecipients'     => [
                        'join' => 'recipients'
                    ],
                    'toAddress'        => [
                        'join' => 'toRecipients.emailAddress'
                    ],
                    'ccRecipients'     => [
                        'join'      => 'recipients',
                        'condition' => 'ccRecipients.type = \'' . EmailRecipient::CC . '\''
                    ],
                    'ccAddress'        => [
                        'join' => 'ccRecipients.emailAddress'
                    ],
                    'bccRecipients'    => [
                        'join'      => 'recipients',
                        'condition' => 'bccRecipients.type = \'' . EmailRecipient::BCC . '\''
                    ],
                    'bccAddress'       => [
                        'join' => 'bccRecipients.emailAddress'
                    ]
                ]
            );
        }

        if ($emailId === null) {
            return $this->buildResponse('', self::ACTION_READ, ['result' => null], Codes::HTTP_NOT_FOUND);
        }

        $page     = (int)$this->getRequest()->get('page', 1);
        $limit    = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);
        $criteria = $this->buildFilterCriteria(
            [
                'id' => ['=', $emailId]
            ],
            [
                'id' => new IdentifierToReferenceFilter($this->getDoctrine(), $manager->getMetadata()->getName())
            ]
        );

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Get entity manager
     *
     * @return EmailEntityApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('orocrmpro_outlook.manager.api.email_entity');
    }
}
