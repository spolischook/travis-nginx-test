<?php

namespace Oro\Component\Layout\Loader\Generator\Extension;

use Oro\Component\Layout\Extension\Theme\ThemeExtension;
use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGeneratorExtensionInterface;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

class ImportsLayoutUpdateExtension implements ConfigLayoutUpdateGeneratorExtensionInterface
{
    const NODE_IMPORTS = 'imports';

    /**
     * {@inheritdoc}
     */
    public function prepare(GeneratorData $data, VisitorCollection $visitorCollection)
    {
        $source = $data->getSource();

        if (!empty($source[self::NODE_IMPORTS])) {
            $visitorCollection->append(new ImportsAwareLayoutUpdateVisitor($source[self::NODE_IMPORTS]));
        }
        if (strpos($data->getFilename(), ThemeExtension::IMPORT_FOLDER) !== false) { // @todo find another way to detect imports
            $visitorCollection->append(new ImportLayoutUpdateVisitor());
        }
    }
}
