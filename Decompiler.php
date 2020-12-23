<?php

class Decompiler
{
    public $System, $DVS, $Time;

    function __construct($System)
    {
        global $argv;

        $this->System = $System;
        $this->System->Log("Start decompiler...");
        $this->Time = 0;
        $this->DVS = array(
            "Sections" => array()
        );
        $this->UseDumper = false;

        if (!$this->VerifyFile() || !$this->VerifySystemFiles() || (md5_file($argv[0]) == md5_file($this->System->File))) {
            $this->System->Log("Verifying error!");
            goto stop;
        }

        if (isset($argv[2]) && $argv[2] == "-dump") {
            $this->UseDumper = true;
        }

        $this->System->Log("Project Settings: ");
        $this->System->Log(" 1\tUse Dumper - " . ((bool)$this->UseDumper ? "true" : "false"));
        $this->VerifyProjectDir();

        $this->System->Log("File: " . basename($this->System->File));

        if ($this->UseDumper) {
            $output_file = pathinfo($this->System->File, PATHINFO_FILENAME) . "/" . pathinfo($this->System->File, PATHINFO_FILENAME) . ".dmp";
            passthru("start " . basename($this->System->File));
            shell_exec('"system/procdump.exe" -ma ' . basename($this->System->File) . " " . $output_file);
            exec("TASKKILL /F /IM " . basename($this->System->File));
            $this->FileData = file_get_contents($output_file);
            $this->System->Log("File Size: " . $this->formatFileSize(strlen(file_get_contents($this->System->File))));
            $this->System->Log("Dump Size: " . $this->formatFileSize(strlen($this->FileData)));
        } else {
            $this->FileData = file_get_contents($this->System->File);
            $this->System->Log("File Size: " . $this->formatFileSize(strlen($this->FileData)));
        }
        $this->DecompileFile_Simple();
        $this->System->Log("File Sections: ");
        $i = 0;
        foreach ($this->DVS["Sections"] as $item => $value) {
            $this->System->Log(" " . ($i + 1) . "\t" . $item);
            $i++;
        }

        $this->System->Log("Total File Sections: " . ($i + 1));
        $this->System->Log("File decompiled!");
        $this->System->Log("Total Decompile Time: " . number_format(pow($this->Time, 6), 16, ".", ""));

        $otptfile = $this->SaveDVS();

        file_put_contents(pathinfo($this->System->File, PATHINFO_FILENAME) . "/" ."sections.txt", $this->prettyPrint(json_encode($this->DVS)));
        file_put_contents(pathinfo($this->System->File, PATHINFO_FILENAME) . "/" ."dvs.txt", $this->prettyPrint(json_encode($this->string_sunpack(file_get_contents($otptfile)))));

        $this->System->Request = true;
        return;
        stop:
        $this->System->Request = false;
    }

    private function VerifyFile()
    {
        $File = $this->System->File;

        if (!file_exists($File)) {
            return false;
        }

        if (is_dir($File)) {
            return false;
        }

        if (!is_executable($File)) {
            return false;
        }

        if (pathinfo($File, PATHINFO_EXTENSION) != "exe") {
            return false;
        }

        return true;
    }

    private function VerifySystemFiles()
    {
        $FileDir = "system";
        $FileProcDump = "$FileDir/procdump.exe";
        if (!file_exists($FileDir) || !is_dir($FileDir)) {
            return false;
        }

        if (!file_exists($FileProcDump)) {
            return false;
        } else {
            $FileProcDumpMD5Need = "d3763ffbfaf30bcfd866b8ed0324e7a3";
            $FileProcDumpMD5Current = md5_file($FileProcDump);
            $this->System->Log("ProcDump MD5: " . $FileProcDumpMD5Current);

            if ($FileProcDumpMD5Current != $FileProcDumpMD5Need) {
                return false;
            }
        }

        return true;
    }

    private function VerifyProjectDir()
    {
        $FileDir = pathinfo($this->System->File, PATHINFO_FILENAME);
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

    private function formatFileSize($size)
    {
        $a = array("B", "KB", "MB", "GB", "TB", "PB");
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        return round($size, 2) . " " . $a[$pos];
    }

    private function DecompileFile_Simple()
    {
        $DecompileStartTime = microtime(true);

        $Sections = $this->get_sections($this->FileData);
        exemod_start($this->System->File);
        foreach ($Sections as $item => $value) {
            //pre2(array($item, $value));
            if (empty($value) || $value == "" || $value == NULL || $value == " " || $value == "null") {
                $Sections[$item] = exemod_extractstr($item);
            }
        }
        exemod_finish();

        foreach ($Sections as $item => $value) {
            $Sections[$item] = $this->string_sunpack($value);
        }

        foreach ($Sections as $item => $value) {
            $this->DVS["Sections"][$item] = $value;
        }

        $DecompileEndTime = microtime(true);
        $DecompileTime = $DecompileEndTime - $DecompileStartTime;
        $this->Time = $DecompileTime;
    }

    function get_sections($Data, $only_names = false)
    {
        $sections = array();
        $s_tag = 'SO!#';
        $r_tag = substr(html_entity_decode('&#182;', 0, 'UTF-8'), 1);
        $e_tag = 'EO!#';
        $point = 0;
        while (($point = strpos($Data, $s_tag, $point)) !== false) {
            $point += 4;
            $name_pos = $point;
            $point = strpos($Data, $r_tag, $point);
            $name = substr($Data, $name_pos, $point - $name_pos);
            $point += 1;
            $data_pos = $point;
            $point = strpos($Data, $e_tag, $point);
            $sections[$name] = substr($Data, $data_pos, $point - $data_pos);
            $out[] = $name;
        }
        if ($only_names) {
            $out = array_unique($out);
            return $out;
        } else {
            return $sections;
        }
    }

    function string_sunpack($str)
    {
        start:

        $oldstr = $str;

        if (is_string($str) && $this->is_base64($str)) {
            $str = base64_decode($str);
        }

        if (is_string($str) && $this->string_unpack($str) != $str) {
            $str = $this->string_unpack($str);
        }

        if (is_string($str) && $this->multi_unserialize($str) != $str) {
            $str = $this->multi_unserialize($str);
        }

        if ($oldstr != $str) {
            goto start;
        }

        return $str;
    }

    function is_base64($str)
    {
        $decoded = base64_decode($str, true);

        // Check if there is no invalid character in string
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $str)) return false;

        // Decode the string in strict mode and send the response
        if (!$decoded) return false;

        // Encode and compare it to original one
        if (base64_encode($decoded) != $str) return false;

        return true;
    }

    private function string_unpack($str)
    {
        $demethods = array(
            "gzcompress" => "gzuncompress",
            "gzdeflate" => "gzinflate",
            "gzencode" => "gzdecode"
        );

        $method = $this->get_compress_method($str);

        if ($method == false) {
            return $str;
        }

        return $this->string_unpack(call_user_func_array($demethods[$method], array(
            $str
        )));

    }

    private function get_compress_method($str)
    {
        err_status(false);

        $method = false;

        if (function_exists("gzuncompress") && @gzuncompress($str) != false) {
            $method = "gzcompress";
        }

        if (function_exists("gzinflate") && @gzinflate($str) != false) {
            $method = "gzdeflate";
        }

        if (function_exists("gzdecode") && @gzdecode($str) != false) {
            $method = "gzencode";
        }

        err_status(true);

        return $method;
    }

    function multi_unserialize($str)
    {
        if (@unserialize($str) !== false) {
            return unserialize($str);
        }

        if (@json_decode($str, true) != false) {
            return json_decode($str, true);
        }

        return $str;
    }

    private function SaveDVS()
    {
        $this->System->Log("Saving File to DVS");

        $DFM = NULL;

        if(isset($this->DVS["Sections"]['$F\\XFORMS'])){
            $DFM = $this->DVS["Sections"]['$F\\XFORMS'];
        } elseif(isset($this->DVS["Sections"]['$F_XFORMS'])) {
            $DFM = $this->DVS["Sections"]['$F_XFORMS'];
        }

        $DVS = array(
            "CONFIG" => $this->DVS["Sections"]['$X_CONFIG']["config"],
            "formsInfo" => $this->DVS["Sections"]['$X_CONFIG']["formsInfo"],
            "add_info" => array(
                'add_info' => array(
                    'DV_VERSION' => '3.0.2.0',
                    'DV_PREFIX' => 'beta 2'
                )
            ),
            "eventDATA" => $this->DVS["Sections"]['$_EVENTS'],
            "DFM" => $DFM
        );
        $file = pathinfo($this->System->File, PATHINFO_FILENAME) . "/".pathinfo($this->System->File, PATHINFO_FILENAME) . '.dvs';
        file_put_contents( $file, gzcompress( base64_encode(serialize($DVS)), 9));
        return $file;
    }

    private function prettyPrint($json)
    {
        $result = '';
        $level = 0;
        $in_quotes = false;
        $in_escape = false;
        $ends_line_level = NULL;
        $json_length = strlen($json);

        for ($i = 0; $i < $json_length; $i++) {
            $char = $json[$i];
            $new_line_level = NULL;
            $post = "";
            if ($ends_line_level !== NULL) {
                $new_line_level = $ends_line_level;
                $ends_line_level = NULL;
            }
            if ($in_escape) {
                $in_escape = false;
            } else if ($char === '"') {
                $in_quotes = !$in_quotes;
            } else if (!$in_quotes) {
                switch ($char) {
                    case '}':
                    case ']':
                        $level--;
                        $ends_line_level = NULL;
                        $new_line_level = $level;
                        break;

                    case '{':
                    case '[':
                        $level++;
                    case ',':
                        $ends_line_level = $level;
                        break;

                    case ':':
                        $post = " ";
                        break;

                    case " ":
                    case "\t":
                    case "\n":
                    case "\r":
                        $char = "";
                        $ends_line_level = $new_line_level;
                        $new_line_level = NULL;
                        break;
                }
            } else if ($char === '\\') {
                $in_escape = true;
            }
            if ($new_line_level !== NULL) {
                $result .= "\n" . str_repeat("\t", $new_line_level);
            }
            $result .= $char . $post;
        }

        return $result;
    }

    private function isBCompiler($str)
    {
        $string = "bcompiler v0.27s";

        if (substr($str, 4, strlen($string)) == $string) {
            return true;
        } else {
            return false;
        }
    }

    private function getscripts($data)
    {
        if (!$data)
            return false;
        $arr = explode('?><?php', $data);
        $i = 1;
        foreach ($arr as $str1) {
            $arr1 = explode('?><?', $str1);
            foreach ($arr1 as $str) {
                if (stripos($str, '<?') != 0 or
                    stripos($str, '<?') === false)
                    if (stripos($str, '<?php') != 0 or
                        stripos($str, '<?php') === false)
                        $str = '<?php ' . $str;
                if (strripos($str, '?>') === false)
                    $str = $str . ' ?>';
                $return['script' . $i] = $str;
                $i++;
            }
        }
        $return = $this->array__shift($return, 10);
        if (is_bool($return)) {
            goto stop;
        }
        foreach ($return as $name => $script) {
            if (stripos($script, '<?php  class T') !== false) {
                unset($return[$name]);
            }
        }
        stop:
        return $return;
    }

    private function array__shift($array, $count = 1)
    {
        if (count($array) <= $count or $count < 1)
            return false;
        for ($i = 0; $i < $count; $i++)
            array_shift($array);
        return $array;
    }

    private function script_analize($op, &$script)
    {

        if (is_array($script)) {
            foreach ($script as &$i)
                $this->script_analize($op, $i);
        } else {
            if (preg_match('/([a-zA-Z0-9_]*)\:\:([a-zA-Z0-9_]*)/', trim($script), $a)) {
                $script = $op[$a[1]][$a[2]];
            }
        }
    }

    private function getforms($data)
    {
        err_no();
        $forms = array();
        foreach (unserialize(gzuncompress($data)) as $name => $data)
            $forms[] = $name;
        err_yes();
        return $forms;
    }
}