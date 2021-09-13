<?php


namespace Core\Managers\Classes\FileManager\Objects;


use Core\Managers\Classes\FileManager\FileObject;

class Dir extends FileObject
{
    public function lszGetFiles()
    {
        $lszStaticFiles = array();
        foreach (glob("$this->szPath/*") as $szFilePath) {
            $lszStaticFiles[] = new File(realpath($szFilePath));
        }
        return $lszStaticFiles;
    }

    public function jCreateFile($szFileName)
    {
        $jFile = new Undefined($this->szGetPath() . "\\" . $szFileName);
        $jFile = $jFile->setFile();
        return $jFile;
    }
}