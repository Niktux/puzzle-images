<?php

namespace Puzzle\Images;

trait FileHandling
{
    private
        $posixMode = true;
    
    private function getDirectorySeparator()
    {
        return $this->posixMode ? '/' : DIRECTORY_SEPARATOR;
    }
    
    public function setPosixMode($enabled)
    {
        if(is_bool($enabled))
        {
            $this->posixMode = $enabled;
        }
    
        return $this;
    }
    
    private function sanitize($filename)
    {
        $filename = strtr($filename,
            'ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ',
            'AAAAAACEEEEIIIIOOOOOUUUUYaaaaaaceeeeiiiioooooouuuuyy'
        );
    
        return preg_replace('/([^a-z0-9\.-_]+)/i', '-', $filename);
    }
}