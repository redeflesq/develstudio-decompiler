<?php


class ReDecompiler
{
    const VERSION = "1.2";

    public $Form, $Args, $File, $FileDir, $Console, $LogSystem;

    function __construct($DSForm, $File = false)
    {
        $this->Form = $DSForm;
        $this->Args = array(
            "-dump" => false, //Get Sections through Dumper
            "-close" => false, //Close program after decompile
            "-dsc" => false //Double Sections Check
        );

        _VCL::hide($this->Form);

        if ($File == false) {
            global $argv;
            $File = $argv[1];
        }

        $this->File = $File;
        $this->FileDir = pathinfo($this->File, PATHINFO_FILENAME) . "/";
        $this->Console = NULL;
        $this->LogSystem = NULL;

        $this->PerformArgs();

        $this->InitializeConsole();
        $this->InitializeLogSystem();

        $this->Log("Start ReDecompiler " . self::VERSION . "...");

        if (!$this->VerifyFile() || !$this->VerifySystemFiles()) {
            $this->Log("Verifying error!");
            goto stop;
        }

        $this->Log("Project Settings: ");
        $this->Log(" 1\tUse Dumper(Dangerous!) - " . ((bool)$this->Args["-dump"] ? "true" : "false"));
        $this->Log(" 2\tClosing after decompile - " . ((bool)$this->Args["-close"] ? "true" : "false"));
        $this->Log(" 3\tDouble sections check - " . ((bool)$this->Args["-dsc"] ? "true" : "false"));

        $this->VerifyProjectDir();
        $this->InitializeDecompiler();
        $this->LogSystem->Save();

        stop:
        if ($this->Args["-close"]) {
            $this->Stop();
        } else {
            goto stop_while;
        }
        return;

        stop_while:
        $this->Console->Echof("Press Enter for exit...");

        while (1) {
            if ($this->Console->GetKeyState(VK_RETURN)) {
                $this->Stop();
            }
        }
        return;
    }

    private function PerformArgs()
    {
        global $argv;
        for ($i = 2; $i < sizeof($argv); $i++) {
            $this->Args[$argv[$i]] = true;
        }
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
            $this->FileDir . pathinfo($this->File, PATHINFO_FILENAME) . ".log.txt"
        );
    }

    function Log($Message)
    {
        $this->LogSystem->Log($Message);
        $this->Console->Echof($Message);
    }

    private function VerifyFile()
    {
        if (!file_exists($this->File)) {
            return false;
        }

        if (is_dir($this->File)) {
            return false;
        }

        if (!is_executable($this->File)) {
            return false;
        }

        if (pathinfo($this->File, PATHINFO_EXTENSION) != "exe") {
            return false;
        }

        return true;
    }

    private function VerifySystemFiles()
    {
        $FileDir = "system";
        $FileProcDump = "$FileDir/procdump.exe";
        $FileDirPHPEXE = "$FileDir/php";

        if (!$this->_VerifyS($FileProcDump, "d3763ffbfaf30bcfd866b8ed0324e7a3")) {
            return false;
        }

        if (!$this->_VerifyS($FileDirPHPEXE, "de8f3e4603282189212c05bbbe573bd7")) {
            return false;
        }

        return true;
    }

    private function _VerifyS($file_dir, $MD5Need)
    {
        if (!file_exists($file_dir)) {
            return false;
        } else {

            if (is_file($file_dir)) {
                $MD5Current = md5_file($file_dir);
            } elseif (is_dir($file_dir)) {
                $MD5Current = Utils::hashDirectory($file_dir);
            } else {
                $MD5Current = "";
            }

            $this->Log(strtoupper(pathinfo($file_dir, PATHINFO_FILENAME)) . " MD5: " . $MD5Current);

            return ($MD5Current == $MD5Need);
        }
    }

    private function VerifyProjectDir()
    {
        $FileDir = pathinfo($this->File, PATHINFO_FILENAME);
        if ($FileDir == "system") {
            $FileDir .= "0";
        }
        if (file_exists($FileDir) && is_dir($FileDir)) {
            $this->deleteDir($FileDir);
        }
        mkdir($FileDir);
    }

    private function deleteDir($path)
    {
        $dir = $path;
        $files = array_diff(scandir($dir), array('.', '..'));

        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->deleteDir("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }

    function InitializeDecompiler()
    {
        $this->Decompiler = new Decompiler($this);
    }

    function Stop()
    {
        $this->Console->Free();
        _VCL::restoreMDI($this->Form);
        app::close();
    }

    static function Loader($DSForm, $File = false)
    {
        return new ReDecompiler($DSForm, $File);
    }
}