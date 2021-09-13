<?php


namespace Core\Managers\Providers;


use Core\Bootstrap\Classes\Manager;
use Core\Managers\Classes\FileManager\Objects\Dir;
use Core\Managers\Classes\FileManager\Objects\File;
use Core\Managers\Classes\FileManager\Objects\Undefined;

class FileManager extends Manager
{
    private $szMainFolderCaption = "ReDecompiler";

    public function __construct()
    {
        $this->vSetMainFolder($this->szGetDocumentRootName());

        parent::__construct(
            function ($szPath) {
                $uImage = new Undefined($szPath);
                if (FileManager::call()->IsDir($szPath)) {
                    $uImage = new Dir($szPath);
                } else if (FileManager::call()->isFile($szPath)) {
                    $uImage = new File($szPath);
                }
                $lszContainer = &FileManager::call()->getContainer();
                $lszContainer[$szPath] = $uImage;
                return $lszContainer[$szPath];
            }
        );
    }

    public function vSetMainFolder($szPath)
    {
        $this->szMainFolderCaption = $szPath;
    }

    public function szGetDocumentRootName()
    {
        if (!$_SERVER["DOCUMENT_ROOT"]) {
            return basename(dirname($_SERVER["PHP_SELF"]));
        } else {
            return basename($_SERVER["DOCUMENT_ROOT"]);
        }
    }

    public function isFile($path)
    {
        return !$this->isDir($path);
    }

    public function isDir($path)
    {
        if (is_file($path)) {
            return false;
        } else if (!is_dir($path)) {
            return false;
        } else {
            return true;
        }
    }

    public function szGetMainFolder($szPath)
    {
        while (basename($szPath) != $this->szMainFolderCaption) {
            $szPath = dirname($szPath);
        }
        return $szPath;
    }
}