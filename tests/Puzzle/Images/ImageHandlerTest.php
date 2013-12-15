<?php

use Puzzle\Images\ImageHandler;
use Puzzle\Configuration\Memory;
use Imagine\Gd\Imagine;
use Gaufrette\Filesystem;
use Gaufrette\Adapter\InMemory;
use Gaufrette\File;
use Gaufrette\Adapter\Local;

class ImageHandlerTest extends PHPUnit_Framework_TestCase
{
    private
        $hashDepth,
        $handler,
        $storage;
    
    protected function setUp()
    {
        $this->hashDepth = 5;
        
        $configuration = new Memory(array(
            'images/hashDepth' => $this->hashDepth,
            'images/formats' => array(
               'small' => array(
                   'quality' => 90,
                   'resize' => '120x80',
                ),
                'medium' => array(
                      'resize' => '200x200'
               ),
               'thumb' => array(
                   'thumbnail' => '40x40',
               ),
            ),    
        ));
        
        $imagine = new Imagine();
        
        $this->storage = new Filesystem(new InMemory());
        
        $this->handler = new ImageHandler($configuration, $imagine, $this->storage);
    }
    
    /**
     * @dataProvider providerTestApplyFormat
     */
    public function testApplyFormat($format, $expectedWidth, $expectedHeight)
    {
        $fs = new Filesystem(new Local(__DIR__));
        $extension = 'png';
        $inputPath = 'data/test.' . $extension;
        
        // apply format
        $output = $this->handler->applyFormat($fs->get($inputPath), $format);
        $this->assertInstanceOf('Gaufrette\File', $output);
        $outputPath = $output->getKey();
        
        // the output path exist and well formed
        $this->assertTrue($this->storage->has($outputPath), 'output file must exist in output filesystem');
        $this->assertRegExp("~$format/([a-f0-9]/){" . $this->hashDepth . "}[a-f0-9]+\.$extension~", $outputPath);
        
        // the file content is a valid image
        $imageContent = $output->getContent();
        $this->assertNotSame(false, imagecreatefromstring($imageContent), 'Output content must be a valid image');
        
        // size metadata can be read
        $info = getimagesizefromstring($imageContent);
        $this->assertInternalType('array', $info);
        $this->assertGreaterThanOrEqual(2, count($info));

        // size is valid
        list($width, $height) = $info;
        $this->assertSame($expectedWidth, $width);
        $this->assertSame($expectedHeight, $height);
    }
    
    public function providerTestApplyFormat()
    {
        return array(
            array('small', 120, 80),
            array('medium', 200, 200),
            array('thumb', 40, 40),
        );
    }
    
    public function testWithInvalidFormat()
    {
        $fs = new Filesystem(new Local(__DIR__));
        $image = $fs->get('data/test.png');
        
        $output = $this->handler->applyFormat($image, 'invalidFormat');
        
        $this->assertSame($output, $image);
    }
}