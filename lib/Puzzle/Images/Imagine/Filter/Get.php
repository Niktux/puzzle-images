<?php

namespace Puzzle\Images\Imagine\Filter;

use Imagine\Image\ImageInterface;
use Imagine\Filter\FilterInterface;

/**
 * A get filter
 */
class Get implements FilterInterface
{
    private 
        $path,
        $options;

    /**
     * Constructs Save filter with given path and options
     *
     * @param string $path
     * @param array  $options
     */
    public function __construct($path, array $options = array())
    {
        $this->path    = $path;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        $format = isset($options['format'])
            ? $options['format']
            : pathinfo($this->path, \PATHINFO_EXTENSION);
        
        return $image->get($format, $this->options);
    }
}
