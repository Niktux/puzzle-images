<?php

namespace Puzzle\Images\FormatTransformations;

use Puzzle\Images\FormatTransformation;
use Imagine\Image\Box;
use Imagine\Filter\Transformation;
use Imagine\Image\ImageInterface;

class Thumbnail implements FormatTransformation
{
    public function getName()
    {
        return 'thumbnail';
    }
    
    public function apply(Transformation $transformation, $option)
    {
        if(strpos($option, self::SIZE_STRING_DELIMITER) !== false)
        {
            $dimensions = explode(self::SIZE_STRING_DELIMITER, $option);
            $mode = ImageInterface::THUMBNAIL_INSET;
            $transformation->thumbnail(new Box($dimensions[0], $dimensions[1]), $mode);
        }
    }
}