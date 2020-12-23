<?php

class ReDecompiler
{
    const VERSION = "0.1";

    public $Form, $File, $Console, $LogSystem, $Request;

    static function Loader($DSForm, $File = false)
    {
        return new ReDecompiler($DSForm, $File);
    }

    function __construct($DSForm, $File = false)
    {
        $this->Form = $DSForm;
		
		_VCL::hide($this->Form);

        if($File == false)
        {
            global $argv;
            $File = $argv[1];
        }

        $this->File = $File;
        $this->Console = NULL;
        $this->LogSystem = NULL;
		$this->Request = NULL;
        $this->InitializeConsole();
        $this->InitializeLogSystem();
        $this->InitializeDecompiler();
		
		if($this->Request == true)
		{
			$this->Log("Stop decompiler");
			$this->LogSystem->Save();
		}

		///$this->Stop();
    }

    function Stop()
    {
        $this->Console->Free();
        _VCL::restoreMDI($this->Form);
        app::close();
    }

    function Log($Message)
    {
        $this->LogSystem->Log($Message);
        $this->Console->Echof($Message);
    }

    function InitializeConsole()
    {
        $this->Console = new _PerfectConsole();
		$this->Console->Allocate();
		$this->Console->SetTitle("ReDecompiler " . self::VERSION);
    }

    function InitializeLogSystem()
    {
        $this->LogSystem = new LogSystem(
            "ReDecompiler " . self::VERSION,
            pathinfo($this->File, PATHINFO_FILENAME)."/".pathinfo($this->File, PATHINFO_FILENAME).".log.txt"
        );
    }

    function InitializeDecompiler()
    {
        $this->Decompiler = new Decompiler($this);
    }
}