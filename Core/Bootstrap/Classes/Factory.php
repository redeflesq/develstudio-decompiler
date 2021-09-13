<?php


namespace Core\Bootstrap\Classes;


use Core\Bootstrap\Loader\Classes\Singleton;

class Factory extends Singleton
{
    protected $lszContainer, $fRegisterMethod;

    public function __construct($fRegisterMethod)
    {
        $this->lszContainer = array();
        $this->fRegisterMethod = $fRegisterMethod;
    }

    public final function register()
    {
        return call_user_func_array($this->fRegisterMethod, func_get_args());
    }

    public function& get($szName)
    {
        return $this->lszContainer[$szName];
    }

    public function& getContainer()
    {
        return $this->lszContainer;
    }
}