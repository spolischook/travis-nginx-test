<?php

namespace OroCRMPro\Bundle\OutlookBundle\Model\Data\Transformer;

use Oro\Bundle\TranslationBundle\Translation\Translator;

use Oro\Bundle\ConfigBundle\Model\Data\Transformer\TransformerInterface;

class LayoutTranslatorDataTransformer implements TransformerInterface
{
    const TRANSLATOR_DOMAIN = 'xaml';

    /** @var Translator $translator */
    protected $translator;

    /**
     * @param Translator $translator
     */
    public function __construct(Translator $translator = null)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($content = '')
    {
        return preg_replace_callback('/<%(.+)%>/', function ($input) {
                        return $this->translator->trans($input[1], [], static::TRANSLATOR_DOMAIN);
        }, $content);
    }
}
