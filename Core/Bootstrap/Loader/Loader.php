<?php


namespace Core\Bootstrap\Loader;


use Core\Bootstrap\Loader\Classes\Singleton;

class Loader extends Singleton
{
    function vIncludeDir($szPathToDir, $fFunc = NULL)
    {
        $this->lszGetFiles();
        foreach ($this->lszGetFiles($szPathToDir) as $szFile) {
            if ($fFunc != NULL)
                $fFunc($szFile);
            include $szFile;
        }
    }

    function lszGetFiles($szPath = "")
    {
        static $lszStaticFiles;

        if ($szPath == "") {
            $lszStaticFiles = array();
            goto ret;
        }

        foreach (glob("$szPath/*") as $szFilePath) {
            if (is_dir($szFilePath)) {
                $this->lszGetFiles($szFilePath);
            } else {
                $lszStaticFiles[] = realpath($szFilePath);
            }
        }

        ret:
        return $lszStaticFiles;
    }
}