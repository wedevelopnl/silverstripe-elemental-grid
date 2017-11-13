<?php

namespace TWM\ElementalGrid\Controllers;

use DNADesign\Elemental\Controllers\ElementController;


class ElementRowController extends ElementController
{

    public function forTemplate()
    {
        return $this->element->forTemplate(false);
    }

}
