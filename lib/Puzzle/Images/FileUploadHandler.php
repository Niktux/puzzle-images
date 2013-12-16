<?php

namespace Puzzle\Images;

use Puzzle\Images\Exceptions\FileUpload;
use Puzzle\Images\Exceptions\Security;
use Puzzle\Images\Exceptions\NotFound;

class FileUploadHandler
{
    use FileHandling;
    
    private
        $defaultTargetDirectory,
        $maxSize,
        $randomMode,
        $hashDepth;
    
    public function __construct($defaultTargetDirectory, $maxSize)
    {
        $this->defaultTargetDirectory = $defaultTargetDirectory;
        $this->maxSize = $maxSize;
        
        $this->randomMode = true;
        $this->hashDepth = 3;
        $this->posixMode = true;
    }
    
    public function setDefaultTargetDirectory($defaultTargetDirectory)
    {
        if(is_string($defaultTargetDirectory))
        {
            $this->defaultTargetDirectory = $defaultTargetDirectory;
        }
        
        return $this;
    }

    public function setMaxSize($maxSize)
    {
        if(is_int($maxSize) && $maxSize > 0)
        {
            $this->maxSize = $maxSize;
        }
    
        return $this;
    }
    
    public function setRandomMode($enabled)
    {
        if(is_bool($enabled))
        {
            $this->randomMode = $enabled;
        }
        
        return $this;
    }
    
    public function setHashDepth($depth)
    {
        if(is_int($depth) && $depth > 0)
        {
            $this->hashDepth = $depth;
        }
    
        return $this;
    }
    
    /**
     *
     * @param string $fieldName
     * @param array $allowedExtensions without leading dots
     * @param string $targetDirectory
     *
     * @return uploaded file path
     */
    public function retrieve($fieldName, array $allowedExtensions, $targetDirectory = null)
    {
        $pathInfo = $this->retrieveFileInformation($fieldName);
        $this->securityCheck($pathInfo, $allowedExtensions);
        
        $targetDirectory = $this->sanitizeTargetDirectory($targetDirectory);
        
        return $this->moveFile($pathInfo, $targetDirectory);
    }
    
    private function retrieveFileInformation($fieldName)
    {
        if(!isset($_FILES[$fieldName]))
        {
            throw new NotFound($fieldName);
        }
        
        $uploadedFileInfo = $_FILES[$fieldName];
        $pathInfo = pathinfo($uploadedFileInfo['name']);
        
        $pathInfo['tmp_name'] = $uploadedFileInfo['tmp_name'];
        $pathInfo['filesize'] = filesize($uploadedFileInfo['tmp_name']);
        
        return $pathInfo;
    }
    
    private function securityCheck($pathInfo, array $allowedExtensions)
    {
        if(!in_array($pathInfo['extension'], $allowedExtensions))
        {
            throw new Security('Not allowed extension : ' . $pathInfo['extension']);
        }
        
        if($pathInfo['filesize'] > $this->maxSize)
        {
            throw new Security(sprintf(
                'File size (%d bytes) exceeds limit (%d bytes)',
                $pathInfo['filesize'],
                $this->maxSize
            ));
        }
    }
    
    private function sanitizeTargetDirectory($targetDirectory)
    {
        if($targetDirectory === null)
        {
            $targetDirectory = $this->defaultTargetDirectory;
        }
        
        $this->ensureDirectoryExists($targetDirectory);
        
        return rtrim($targetDirectory, $this->getDirectorySeparator()) . $this->getDirectorySeparator();
    }
    
    private function ensureDirectoryExists($directory)
    {
        if(!is_dir($directory))
        {
            if(!mkdir($directory, 0755, true))
            {
                throw new Exceptions\Filesystem("Cannot create directory $directory");
            }
        }
    }
    
    private function moveFile($pathInfo, $targetDirectory)
    {
        $target = $targetDirectory . $this->computeFileName($pathInfo);
        $this->ensureDirectoryExists(dirname($target));
        
        if(move_uploaded_file($pathInfo['tmp_name'], $target) === false)
        {
            throw new FileUpload('Cannot move downloaded file');
        }
        
        return $target;
    }
    
    private function computeFileName($pathInfo)
    {
        if($this->randomMode === true)
        {
            return $this->computeRandomFileName($pathInfo);
        }

        return $this->computeRealFileName($pathInfo);
    }
    
    private function computeRandomFileName($pathInfo)
    {
        $md5 = sprintf(
            '%s.%s',
            md5(uniqid($pathInfo['basename'], true)),
            $pathInfo['extension']
        );
        
        $target = $this->hash($md5);
        
        return $target;
    }
    
    private function hash($filename)
    {
        $hashedPart = substr($filename, 0, $this->hashDepth);
        $directories = str_split($hashedPart);
        
        return implode($this->getDirectorySeparator(), $directories) . $this->getDirectorySeparator() . substr($filename, $this->hashDepth);
    }
    
    private function computeRealFileName($pathInfo)
    {
        return $this->sanitize($pathInfo['basename']);
    }
}