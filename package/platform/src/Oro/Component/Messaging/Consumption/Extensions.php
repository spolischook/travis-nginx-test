<?php
namespace Oro\Component\Messaging\Consumption;

final class Extensions implements Extension
{
    /**
     * @var array|Extension[]
     */
    private $extensions;

    /**
     * @param Extension[] $extensions
     */
    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * @param Context $context
     */
    public function onStart(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onStart($context);
        }
    }

    /**
     * @param Context $context
     */
    public function onPreReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPreReceived($context);
        }
    }

    /**
     * @param Context $context
     */
    public function onPostReceived(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onPostReceived($context);
        }
    }

    /**
     * @param Context $context
     */
    public function onIdle(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onIdle($context);
        }
    }

    /**
     * @param Context $context
     */
    public function onInterrupted(Context $context)
    {
        foreach ($this->extensions as $extension) {
            $extension->onInterrupted($context);
        }
    }
}


