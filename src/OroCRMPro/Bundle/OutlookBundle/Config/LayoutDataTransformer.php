<?php

namespace OroCRMPro\Bundle\OutlookBundle\Config;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\DataTransformerInterface;

class LayoutDataTransformer implements DataTransformerInterface
{
    const TRANSLATION_DOMAIN = 'outlook';

    /** @var TranslatorInterface $translator */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($content)
    {
        return preg_replace_callback(
            '/<%(.+?)%>/',
            function ($matches) {
                return $this->translator->trans(trim($matches[1]), [], static::TRANSLATION_DOMAIN);
            },
            $content
        );
    }
}
