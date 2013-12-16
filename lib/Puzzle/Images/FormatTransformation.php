<?php

namespace Puzzle\Images;

use Imagine\Filter\Transformation;

interface FormatTransformation
{
    const
        SIZE_STRING_DELIMITER = 'x';
    
    public function getName();

    /**
     * @param Imagine\Filter\Transformation $transformation
     * @param string $option
     * 
     * @return void
     */
    public function apply(Transformation $transformation, $option);
}