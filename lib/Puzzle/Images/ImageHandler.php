<?php

namespace Puzzle\Images;

use Puzzle\Configuration;
use Imagine\Image\ImagineInterface;
use Imagine\Image\ImageInterface;
use Gaufrette\Filesystem;
use Puzzle\Images\Imagine\Filter\Get;
use Gaufrette\File;

class ImageHandler
{
    use FileHandling;
    
    const
        SIZE_DELIMITER = 'x';
    
    private
        $hashDepth,
        $configuration,
        $formats,
        $imagine,
        $storage;
    
    public function __construct(Configuration $configuration, ImagineInterface $imagine, Filesystem $storage)
    {
        $this->configuration = $configuration;
        
        $this->hashDepth = $configuration->read('images/hashDepth', 3);
        $this->formats = $configuration->read('images/formats', array());
        
        $this->imagine = $imagine;
        $this->storage = $storage;
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
        
        if(isset($formatDescription['resize']))
        {
            $sizeStr = $formatDescription['resize'];
            if(strpos($sizeStr, self::SIZE_DELIMITER) !== false)
            {
                $dimensions = explode(self::SIZE_DELIMITER, $sizeStr);
                $transformation->resize(new \Imagine\Image\Box($dimensions[0], $dimensions[1]));
            }
        }
        elseif(isset($formatDescription['thumbnail']))
        {
            $sizeStr = $formatDescription['thumbnail'];
            if(strpos($sizeStr, self::SIZE_DELIMITER) !== false)
            {
                $dimensions = explode(self::SIZE_DELIMITER, $sizeStr);
                $mode = ImageInterface::THUMBNAIL_INSET;
                $transformation->thumbnail(new \Imagine\Image\Box($dimensions[0], $dimensions[1]), $mode);
            }
        }
        
        return $transformation;
    }
}