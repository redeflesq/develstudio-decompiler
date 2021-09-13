<?php


namespace Core\Factories\Providers;


use Core\Bootstrap\Classes\Factory;
use Core\Managers\Providers\FileManager;

class DecompilerFactory extends Factory
{
    public $szDecompilersFolder, $szDecompilersNamespace, $jMainDecompiler;

    public function __construct($szDecompilersFolder, $szDecompilersNamespace)
    {
        $this->szDecompilersFolder = $szDecompilersFolder;
        $this->szDecompilersNamespace = $szDecompilersNamespace;
        parent::__construct(
            function ($szName) {
                $szImagePath = DecompilerFactory::call()->szDecompilersFolder . "\\" . $szName . ".php";
                $szImagePath = realpath($szImagePath);

                if (!$szImagePath) {
                    return NULL;
                }

                $bInclude = include_once $szImagePath;

                if ($bInclude) {
                    $szNamespace = DecompilerFactory::call()->szDecompilersNamespace . "\\" . $szName;
                    $lszContainer = &DecompilerFactory::call()->getContainer();
                    $lszContainer[$szName] = new $szNamespace;
                }
            }
        );
    }

    public function registerDecompilers()
    {
        $jDir = FileManager::call()->register($this->szDecompilersFolder);

        foreach ($jDir->lszGetFiles() as $jFile) {
            $szName = $jFile->szGetFilename();
            $this->register($szName);
            if ($szName == $this->getCurrentDecompiler()) {
                $this->jMainDecompiler =& $this->get($szName);
            }
        }
    }

    public function getCurrentDecompiler()
    {
        return "DefaultDecompiler";
    }

    public function decompile()
    {
        $this->getDecompiler()->vFindScripts();
        $this->getDecompiler()->vFindData();
        $this->getDecompiler()->vFindDFM();
        $this->getDecompiler()->vFindEventData();
        $this->getDecompiler()->vFindForms();
        $this->getDecompiler()->vFindConfig();
    }

    public function& getDecompiler()
    {
        return $this->jMainDecompiler;
    }
}