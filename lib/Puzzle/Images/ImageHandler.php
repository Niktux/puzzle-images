<?php

namespace Puzzle\Images;

use Puzzle\Configuration;
use Puzzle\Images\Imagine\Filter\Get;
use Puzzle\Images\FormatTransformations\Resize;
use Puzzle\Images\FormatTransformations\Thumbnail;
use Gaufrette\Filesystem;
use Gaufrette\File;
use Imagine\Image\ImagineInterface;

class ImageHandler
{
    use FileHandling;
    
    private
        $hashDepth,
        $configuration,
        $formats,
        $imagine,
        $storage,
        $formatTransformations;
    
    public function __construct(Configuration $configuration, ImagineInterface $imagine, Filesystem $storage)
    {
        $this->configuration = $configuration;
        
        $this->hashDepth = $configuration->read('images/hashDepth', 3);
        $this->formats = $configuration->read('images/formats', array());
        
        $this->imagine = $imagine;
        $this->storage = $storage;
        
        $this->formatTransformations = array();
        $this->registerFormatTransformations();
    }
    
    private function registerFormatTransformations()
    {
        $transformations = array(
            new Resize(),
            new Thumbnail(),
        );
        
        foreach($transformations as $t)
        {
            $this->addFormatTransformation($t);
        }
    }
    
    public function addFormatTransformation(FormatTransformation $formatTransformation)
    {
        $this->formatTransformations[$formatTransformation->getName()] = $formatTransformation;
        
        return $this;
    }
    
    public function applyFormat(File $sourceImage, $format)
    {
        if(isset($this->formats[$format]) && $sourceImage->exists())
        {
            return $this->getFormat($sourceImage, $format);
        }
        
        return $sourceImage;
    }
    
    private function getFormat(File $sourceImage, $format)
    {
        $targetPath = $this->computePath($sourceImage, $format);
        
        if(! $this->storage->has($targetPath))
        {
            $this->applyTransformation($sourceImage, $targetPath, $format);
        }
        
        return $this->storage->get($targetPath);
    }
    
    private function computePath(File $sourceImage, $format)
    {
        $relativeImagePath = $sourceImage->getKey();
        $targetPath = $this->sanitize($format) . $this->getDirectorySeparator() . $this->hash(md5($relativeImagePath));
        
        $fileInfo = pathinfo($relativeImagePath);
        if(isset($fileInfo['extension']))
        {
            $targetPath .= '.' . $fileInfo['extension'];
        }

        return $targetPath;
    }
    
    private function hash($path)
    {
        $parts = preg_split('~~', $path, $this->hashDepth + 1, PREG_SPLIT_NO_EMPTY);
        
        return implode($this->getDirectorySeparator(), $parts);    
    }
    
    private function applyTransformation(File $sourceImage, $imageTargetPath, $format)
    {
        $transformation = $this->getTransformation($format);
        
        $transformation->add(
            new Get($imageTargetPath, array('quality' => $this->getQuality($format)))
        );
        
        $imageContent = $transformation->apply(
            $this->imagine->load($sourceImage->getContent())
        );
        
        $this->storage->write($imageTargetPath, $imageContent);
    }
    
    private function getQuality($format)
    {
        $quality = 100;
        
        $formatDescription = $this->formats[$format];
        if(isset($formatDescription['quality']))
        {
            $quality = intval(trim($formatDescription['quality']));
        }
        
        return $quality;
    }
    
    private function getTransformation($format)
    {
        $transformation = new \Imagine\Filter\Transformation();
        
        $formatDescription = $this->formats[$format];
        
        foreach($this->formatTransformations as $key => $formatTransformation)
        {
            if(isset($formatDescription[$key]))
            {
                $formatTransformation->apply($transformation, $formatDescription[$key]);
            }
        }
        
        return $transformation;
    }
}