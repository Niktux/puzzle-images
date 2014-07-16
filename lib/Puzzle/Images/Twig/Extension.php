<?php

namespace Puzzle\Images\Twig;

use Puzzle\Images\ImageHandler;
use Puzzle\Configuration;
use Gaufrette\File;

class Extension extends \Twig_Extension
{
    private
        $configuration,
        $handler;
    
    public function __construct(Configuration $configuration, ImageHandler $handler)
    {
        $this->configuration = $configuration;
        $this->handler = $handler;
    }
    
    public function getName()
    {
        return 'puzzle-images';
    }
    
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('image', function($path, $format) {
                $imageFile = $this->handler->applyFormat($path, $format);
                
                return $this->rewritePath($imageFile);
            }),
        );
    }
    
    private function rewritePath(File $imageFile)
    {
        $rules = $this->configuration->read('images/rewriteRules', array());
        
        return str_replace(array_keys($rules), array_values($rules), $imageFile->getKey());
    }
}
