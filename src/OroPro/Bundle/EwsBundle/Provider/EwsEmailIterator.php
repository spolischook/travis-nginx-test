<?php

namespace OroPro\Bundle\EwsBundle\Provider;

use OroPro\Bundle\EwsBundle\Provider\Iterator\AbstractBatchIterator;

/**
 * Class EwsEmailIterator
 * used by sync processor
 * uses EwsEmailManager::getEmails to get items
 * should clone SearchQuery and modify it for each batch
 *
 * @package OroPro\Bundle\EwsBundle
 */
class EwsEmailIterator extends AbstractBatchIterator
{

}
