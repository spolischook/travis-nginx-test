<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Unit\Entity\Stub;

use OroB2B\Bundle\CMSBundle\Entity\Page;

class PageStub extends Page
{
    use MetaFieldSetterGetterTrait {
        MetaFieldSetterGetterTrait::__construct as private traitConstructor;
    }

    public function __construct()
    {
        parent::__construct();
        $this->traitConstructor();
    }
}
