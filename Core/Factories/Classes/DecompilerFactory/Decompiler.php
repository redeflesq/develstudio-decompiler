<?php


namespace Core\Factories\Classes\DecompilerFactory;

use Core\Factories\Providers\DecompilerFactory;
use Core\Managers\Classes\FileManager\Objects\Undefined;
use Core\Managers\Classes\HookManager\ClassHook;
use Core\Managers\Providers\FileManager;

abstract class Decompiler extends ClassHook
{
    const
        SCRIPTS = '$X_MODULES',
        DATA = '$RESLIST$',
        DFM = '$F\XFORMS',
        EVENT_DATA = '$_EVENTS',
        FORMS = '$X_CONFIG',
        CONFIG = '$X_CONFIG';

    public $jDVS;

    public function __construct($szClass = NULL)
    {
        parent::__construct($szClass);

        $this->jDVS = new DVS();
    }

    protected final function vDecompile($szOutput)
    {
        DecompilerFactory::call()->decompile();
        $this->vSaveProjectDVS($this->jDVS, $szOutput);
    }

    protected final function vSaveProjectDVS($dvsProject, $szPath)
    {
        if (!($dvsProject instanceof DVS)) {
            return;
        }

        $lszDvs = $dvsProject->lszToSupportDS();

        $jFile = FileManager::call()->register($szPath);

        if ($jFile instanceof Undefined) {
            $jFile = $jFile->setFile();
        }

        $jFile->vWrite(gzcompress(base64_encode(serialize($lszDvs)), 9));
    }

    protected abstract function vFindScripts();

    protected abstract function vFindData();

    protected abstract function vFindDFM();

    protected abstract function vFindEventData();

    protected abstract function vFindForms();

    protected abstract function vFindConfig();
}