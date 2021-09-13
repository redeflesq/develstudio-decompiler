<?php


namespace Core\Managers\Classes\FileManager;


class FileObject
{
    protected $szPath;

    public function __construct($szPath)
    {
        $this->szPath = $szPath;
    }

    public function szGetPath()
    {
        return $this->szPath;
    }

    public function vSetPath($szPath)
    {
        $this->szPath = $szPath;
    }

    public function vDelete()
    {
        if (is_dir($this->szPath)) {
            rmdir($this->szPath);
        } else if (is_file($this->szPath)) {
            unlink($this->szPath);
        }
    }

    public function szGetName()
    {
        return pathinfo($this->szPath, PATHINFO_FILENAME);
    }
}