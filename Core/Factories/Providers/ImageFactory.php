<?php


namespace Core\Factories\Providers;


use Core\Bootstrap\Classes\Factory;
use Core\Managers\Providers\FileManager;

class ImageFactory extends Factory
{
    public $szImagesFolder, $szImagesNamespace, $jMainImage;

    public function __construct($szImagesFolder, $szImagesNamespace)
    {
        $this->szImagesFolder = $szImagesFolder;
        $this->szImagesNamespace = $szImagesNamespace;
        parent::__construct(
            function ($szName) {

                $szImagePath = ImageFactory::call()->szImagesFolder . "\\" . $szName . ".php";
                $szImagePath = realpath($szImagePath);

                if (!$szImagePath) {
                    return NULL;
                }

                $bInclude = include $szImagePath;

                if ($bInclude) {
                    $szNamespace = ImageFactory::call()->szImagesNamespace . "\\" . $szName;
                    $lszContainer = &ImageFactory::call()->getContainer();
                    $lszContainer[$szName] = new $szNamespace;
                }
            }
        );
    }

    public function registerImages()
    {
        $jDir = FileManager::call()->register($this->szImagesFolder);

        foreach ($jDir->lszGetFiles() as $jFile) {
            $szName = $jFile->szGetFilename();
            $this->register($szName);
            if ($this->get($szName)->bDetect() && !$this->getStatusWA($szName)) {
                $this->jMainImage =& $this->get($szName);
            }
        }
    }

    public function getStatusWA($szName)
    {
        foreach ($this->lszContainer as $jImage) {
            if (get_class($jImage) == "{$this->szImagesNamespace}\\$szName") {
                continue;
            } else {
                if ($jImage->bDetect() == true) {
                    return true;
                }
            }
        }
        return false;
    }

    public function& getImage()
    {
        return $this->jMainImage;
    }
}