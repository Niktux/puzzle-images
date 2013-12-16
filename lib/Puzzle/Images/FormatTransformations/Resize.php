<?php

namespace Puzzle\Images\FormatTransformations;

use Puzzle\Images\FormatTransformation;
use Imagine\Image\Box;
use Imagine\Filter\Transformation;

class Resize implements FormatTransformation
{
    public function getName()
    {
        return 'resize';
    }
    
    public function apply(Transformation $transformation, $option)
    {
        if(strpos($option, self::SIZE_STRING_DELIMITER) !== false)
        {
            $dimensions = explode(self::SIZE_STRING_DELIMITER, $option);
            $transformation->resize(new Box($dimensions[0], $dimensions[1]));
        }
    }
}