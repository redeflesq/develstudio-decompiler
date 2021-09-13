<?php


namespace Core\Managers\Classes\FileManager\Objects;


use Core\Managers\Classes\FileManager\FileObject;

class File extends FileObject
{
    public function szGetExtension()
    {
        return pathinfo($this->szGetPath(), PATHINFO_EXTENSION);
    }

    public function szGetFilename()
    {
        return pathinfo($this->szGetPath(), PATHINFO_FILENAME);
    }

    public function bExist()
    {
        return file_exists($this->szGetPath());
    }

    public function vWrite($szString)
    {
        file_put_contents($this->szGetPath(), $szString);
    }

    public function szGetDir()
    {
        return dirname($this->szGetPath());
    }
}