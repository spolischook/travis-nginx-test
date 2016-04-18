<?php

namespace OroPro\Bundle\OrganizationBundle\Exception;

/**
 * This exception throws if user works in System access mode and try to add new record.
 * In this case we should redirect user to select organization step for this new record.
 * Exception listener for this exception type do this redirect job.
 *
 * Please do not use this exception type anywhere else.
 *
 * Class OrganizationAwareException
 * @package OroPro\Bundle\OrganizationBundle\Exception
 */
class OrganizationAwareException extends \Exception
{
}
