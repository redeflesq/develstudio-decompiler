<?php


namespace Core\Bootstrap\Classes;


class Manager extends Factory
{
    public function free($szName)
    {
        if ($this->exist($szName)) {
            unset($this->lszContainer[$szName]);
            return true;
        } else {
            return false;
        }
    }

    public function exist($szName)
    {
        return isset($this->lszContainer[$szName]);
    }
}