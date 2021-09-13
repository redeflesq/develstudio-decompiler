<?php

class Container
{
    private $lszContainer, $iContainerID;

    public function __construct()
    {
        $this->iContainerID = rand();
        if (!isset($this->lszContainer) || !is_array($this->lszContainer)) {
            $this->lszContainer = array();
        }
    }

    public function &__get($szName)
    {
        if (array_key_exists($szName, $this->lszContainer)) {
            return $this->lszContainer[$szName];
        }
    }

    public function __set($szName, $uValue)
    {
        if (!array_key_exists($szName, $this->lszContainer)) {
            $this->lszContainer[$szName] = $uValue;
        }
    }

    public function __unset($szName)
    {
        if (array_key_exists($szName, $this->lszContainer)) {
            unset($this->lszContainer[$szName]);
        }
    }

    public function __isset($szName)
    {
        return isset($this->lszContainer[$szName]);
    }

    public function __invoke()
    {
        if (isset($this->lszContainer)) {
            return $this->lszContainer;
        } else {
            return false;
        }
    }

    public function __debugInfo()
    {
        return $this->lszContainer;
    }
}
