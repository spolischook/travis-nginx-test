<?php

namespace Oro\Bundle\UIBundle\Twig;

class FormatterExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_formatter';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('oro_format_filename', [$this, 'formatFilename']),
        ];
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    public function formatFilename($filename)
    {
        $encoding = mb_detect_encoding($filename);

        if (mb_strlen($filename, $encoding) > 15) {
            $filename = mb_substr($filename, 0, 7, $encoding)
                . '..'
                . mb_substr($filename, mb_strlen($filename, $encoding) - 7, null, $encoding);
        }

        return $filename;
    }
}
