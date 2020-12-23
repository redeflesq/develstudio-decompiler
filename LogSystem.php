<?php

class LogSystem
{
    const VERSION = "0.1";

    private $Log, $StartMessage;
    public $FileName;

    public function __construct($StartMessage = false, $FileName = false)
    {
        $this->Log = array();
        $this->StartMessage = ((!$StartMessage) ? "LogSystem " . self::VERSION : $StartMessage);
        $this->FileName = ((!$FileName) ? "Log.txt" : $FileName);
		$this->Log($this->StartMessage);
    }

    private function Append($Time, $Message)
    {
        $this->Log[] = array(
            "Time" => $Time,
            "Message" => $Message);
    }

    public function Log($Message)
    {
        $this->Append(time(), $Message);
    }

    public function Save()
    {
        $File = "";
        foreach ($this->Log as $item)
        {
            $File .= $item["Time"] . ": " . $item["Message"] . "\n";
        }
        file_put_contents($this->FileName, $File);
    }
}