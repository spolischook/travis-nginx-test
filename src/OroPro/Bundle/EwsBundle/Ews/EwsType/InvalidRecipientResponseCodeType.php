<?php

namespace OroPro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * Represents the message keys that can be returned for invalid recipients
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class InvalidRecipientResponseCodeType
{
    const OTHER_ERROR = "OtherError";
    const RECIPIENT_ORGANIZATION_NOT_FEDERATED = "RecipientOrganizationNotFederated";
    const CANNOT_OBTAIN_TOKEN_FROM_STS = "CannotObtainTokenFromSTS";
    const SYSTEM_POLICY_BLOCKS_SHARING_WITH_THIS_RECIPIENT = "SystemPolicyBlocksSharingWithThisRecipient";
    const RECIPIENT_ORGANIZATION_FEDERATED_WITH_UNKNOWN_TOKEN_ISSUER = "RecipientOrganizationFederatedWithUnknownTokenIssuer";
}
// @codingStandardsIgnoreEnd
