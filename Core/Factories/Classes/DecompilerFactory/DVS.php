<?php


namespace Core\Factories\Classes\DecompilerFactory;


class DVS
{
    const
        CONFIG = "CONFIG",
        FORMS = "formsInfo",
        INFO = "add_info",
        EVENT_DATA = "eventDATA",
        DFM = "DFM",
        SCRIPTS = "scripts",
        DATA = "Data";

    public $Config, $Forms, $Info, $EventData, $DFM, $Scripts, $Data;

    function __construct()
    {
        $this->Config = NULL;
        $this->Forms = NULL;
        $this->Info = array(
            'DV_VERSION' => '3.0.2.0',
            'DV_PREFIX' => 'beta 2'
        );
        $this->EventData = NULL;
        $this->DFM = NULL;
        $this->Scripts = NULL;
        $this->Data = NULL;
    }

    function lszToSupportDS()
    {
        return array(
            self::CONFIG => $this->Config,
            self::FORMS => $this->Forms,
            self::INFO => $this->Info,
            self::EVENT_DATA => $this->EventData,
            self::DFM => $this->DFM,
            self::SCRIPTS => $this->Scripts,
            self::DATA => $this->Data
        );
    }
}