<?php

namespace OroCRM\Bundle\DotmailerBundle\Placeholders;

use OroCRM\Bundle\MarketingListBundle\Entity\MarketingList;
use OroCRM\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

class ButtonFilter
{
    /**
     * @var ContactInformationFieldsProvider
     */
    protected $fieldsProvider;

    /**
     * @param ContactInformationFieldsProvider $fieldsProvider
     */
    public function __construct(ContactInformationFieldsProvider $fieldsProvider)
    {
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    public function isApplicable($entity)
    {
        if ($entity instanceof MarketingList) {
            return (bool)$this->fieldsProvider->getMarketingListTypedFields(
                $entity,
                ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
            );
        }

        return false;
    }
}
