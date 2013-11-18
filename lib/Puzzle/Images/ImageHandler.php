<?php

namespace Puzzle\Images;

use Puzzle\Configuration;
use Imagine\Image\ImagineInterface;

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
        $storageDir;
    
    public function __construct(Configuration $configuration, ImagineInterface $imagine, $storageDir)
    {
        $this->hashDepth = $configuration->read('images/hashDepth', 3);
        $this->configuration = $configuration;
        $this->formats = $configuration->read('images/formats', array());
        $this->imagine = $imagine;
        $this->storageDir = rtrim($storageDir, $this->getDirectorySeparator()) . $this->getDirectorySeparator();
    }
    
    public function applyFormat($imagePath, $format)
    {
        // FIXME
        $relativeImagePath = ltrim($imagePath, $this->getDirectorySeparator());
        
        if(isset($this->formats[$format]) && is_file($relativeImagePath))
        {
            try
            {
                return $this->getFormat($relativeImagePath, $format);
            }
            catch(\Exception $e)
            {
                // no transformation
            }
        }
        
        return $imagePath;
    }
    
    private function getFormat($imagePath, $format)
    {
        $targetPath = $this->computePath($imagePath, $format);
        
        if(! is_file($targetPath))
        {
            $this->applyTransformation($imagePath, $targetPath, $format);
        }
        
        return $targetPath;
    }
    
    private function computePath($imagePath, $format)
    {
        $targetDirectory = $this->storageDir . $this->sanitize($format) . $this->getDirectorySeparator() . $this->hash(md5($imagePath));
        $this->ensureDirectoryExists($targetDirectory);
        
        $fileInfo = pathinfo($imagePath);
        
        return sprintf(
            '%s.%s',
            $targetDirectory,
            $fileInfo['extension']
        );
    }
    
    private function hash($path)
    {
        $parts = preg_split('~~', $path, $this->hashDepth + 1);
        
        return implode($this->getDirectorySeparator(), $parts);    
    }
    
    private function ensureDirectoryExists($directory)
    {
        if(!is_dir($directory))
        {
            if(!mkdir($directory, 0755, true))
            {
                throw new \Firenote\Exceptions\Filesystem("Cannot create directory $directory");
            }
        }
    }
    
    private function applyTransformation($imageSourcePath, $imageTargetPath, $format)
    {
        $transformation = $this->getTransformation($format);
        $transformation->save($imageTargetPath);
        
        $transformation->apply($this->imagine->open($imageSourcePath));
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
        
        return $transformation;
    }
}