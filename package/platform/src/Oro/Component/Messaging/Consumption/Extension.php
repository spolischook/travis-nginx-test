<?php
namespace Oro\Component\Messaging\Consumption;

interface Extension
{
    /**
     * @param Context $context
     */
    public function onStart(Context $context);

    /**
     * @param Context $context
     */
    public function onPreReceived(Context $context);

    /**
     * @param Context $context
     */
    public function onPostReceived(Context $context);

    /**
     * @param Context $context
     */
    public function onIdle(Context $context);

    /**
     * @param Context $context
     */
    public function onInterrupted(Context $context);
}
