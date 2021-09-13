<?php


namespace Core\Factories\Classes\ImageFactory;

use Core\Managers\Classes\HookManager\ClassHook;

abstract class Image extends ClassHook
{
    public function __construct()
    {
        parent::__construct(__CLASS__);
    }

    protected abstract function vShowMessage($szMessage);

    protected abstract function vShowArrayMessage($szMask, $lszMainArray, $lszOptionals = array());

    protected abstract function vExit();

    protected abstract function lszGetArgs();

    protected abstract function bDetect();
}