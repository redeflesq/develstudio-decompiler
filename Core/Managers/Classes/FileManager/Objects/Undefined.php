<?php


namespace Core\Managers\Classes\FileManager\Objects;


use Core\Managers\Classes\FileManager\FileObject;

class Undefined extends FileObject
{
    function setDir()
    {
        if (!file_exists($this->szGetPath())) {
            mkdir($this->szGetPath());
        }
        return new Dir($this->szGetPath());
    }

    function setFile()
    {
        file_put_contents($this->szGetPath(), "");
        return new File($this->szGetPath());
    }
}