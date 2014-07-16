<?php

namespace Puzzle\Images\Twig;

use Gaufrette\Filesystem;
use Gaufrette\Adapter\Local;
use Puzzle\Configuration\Memory;
use Imagine\Gd\Imagine;
use Gaufrette\Adapter\InMemory;
use Puzzle\Images\ImageHandler;

class ExtensionTest extends \PHPUnit_Framework_TestCase
{
    private
        $configuration,
        $handler,
        $storage,
        $imageFilter;
    
    protected function setUp()
    {
        $this->configuration = new Memory(array(
            'images/hashDepth' => 0,
            'images/formats' => array(
                'thumb' => array(
                    'thumbnail' => '40x40',
                ),
                'castor' => array(
                    'thumbnail' => '40x40',
                ),
            ),
            'images/rewriteRules' => array(
                'castor' => 'raton/laveur'        
            ),
        ));
    
        $this->storage = new Filesystem(new InMemory());
        $this->handler = new ImageHandler($this->configuration, new Imagine(), $this->storage);

        $this->initializeTwigImageFilter();
    }
    
    private function initializeTwigImageFilter()
    {
        $this->imageFilter = null;

        $twigExtension = new Extension($this->configuration, $this->handler);
        $filters = $twigExtension->getFilters();

        foreach($filters as $filter)
        {
            if($filter->getName() === 'image')
            {
                $this->imageFilter = $filter;
                break;
            }
        }
    }
    
    private function callFilter($inputImage, $format)
    {
        $this->assertNotNull($this->imageFilter);
        
        $callable = $this->imageFilter->getCallable();
        
        return $callable($inputImage, $format);
    }
    
    public function testImageFilter()
    {
        $fs = new Filesystem(new Local(__DIR__ . '/../data'));
        $inputPath = 'test.png';
        
        $inputImage = $fs->get($inputPath);
        
        $path = $this->callFilter($inputImage, 'thumb');
        $this->assertSame('thumb', dirname($path));
        
        $path = $this->callFilter($inputImage, 'castor');
        $this->assertSame('raton/laveur', dirname($path));
    }
}