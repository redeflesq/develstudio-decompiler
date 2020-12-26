<?php


class Decompiler
{
    public $System, $DVS, $Time, $FileData;

    function __construct($System)
    {
        $this->System = $System;
        $this->System->Log("Start decompiler...");
        $this->Time = 0;
        $this->DVS = array(
            "Sections" => array()
        );

        $this->System->Log("File: " . basename($this->System->File));

        if ($this->System->Args["-dump"]) {
            $output_file = pathinfo($this->System->File, PATHINFO_FILENAME) . "/" . pathinfo($this->System->File, PATHINFO_FILENAME) . ".dmp";
            passthru("start " . basename($this->System->File));
            shell_exec('"system/procdump.exe" -ma ' . basename($this->System->File) . " " . $output_file);
            exec("TASKKILL /F /IM " . basename($this->System->File));
            $this->FileData = file_get_contents($output_file);
            $this->System->Log("File Size: " . Utils::formatFileSize(strlen(file_get_contents($this->System->File))));
            $this->System->Log("Dump Size: " . Utils::formatFileSize(strlen($this->FileData)));
        } else {
            $this->FileData = file_get_contents($this->System->File);
            $this->System->Log("File Size: " . Utils::formatFileSize(strlen($this->FileData)));
        }

        $this->DecompileFile();
        $this->System->Log("File Sections: ");

        $i = 0;
        foreach ($this->DVS["Sections"] as $item => $value) {
            $this->System->Log(" " . ($i + 1) . "\t" . $item);
            $i++;
        }

        $this->System->Log("Total File Sections: " . $i);
        $this->System->Log("File decompiled!");
        $this->System->Log("Total Decompile Time: " . number_format(pow($this->Time, 6), 16, ".", ""));

        $otptfile = $this->SaveDVS();
        $this->SaveSource($otptfile);

        $this->System->Log("Stop decompiler");
    }

    private function DecompileFile()
    {
        $DecompileStartTime = microtime(true);

        $BCompiler = false;

        $DVS = array(
            "Sections" => $this->get_sections($this->FileData),
            "Scripts" => array(),
            "Data" => array()
        );

        if ($this->System->Args["-dsc"]) {
            exemod_start($this->System->File);
            foreach ($DVS["Sections"] as $item => $value) {
                if (empty($value) || $value == "" || $value == NULL || $value == " " || $value == "null") {
                    $DVS["Sections"][$item] = exemod_extractstr($item);
                }
            }
            exemod_finish();
        }

        foreach ($DVS["Sections"] as $item => $value) {
            $DVS["Sections"][$item] = $this->string_sunpack($value);
        }

        foreach ($DVS["Sections"] as $item => $value) {
            if (is_string($value) && $this->isBCompiler($value)) {
                $DVS["Sections"][$item] = $this->RemoveBCompiler($value);
                $BCompiler = true;
            }
        }

        if (isset($DVS["Sections"]['$_EXEVFILE']) && $BCompiler) {
            $this->script_analize(
                $this->create_op_code($DVS["Sections"]['$_EXEVFILE']),
                $DVS["Sections"]['$_EVENTS']
            );
        }

        if(isset($DVS["Sections"]['$X_MODULES'])) {
            foreach ($this->getscripts($DVS["Sections"]['$X_MODULES']) as $script => $data) {
                $sname = $this->getScriptName($data);
                if ($sname) {
                    $script = $sname;
                }
                $DVS['Scripts'][$script . '.php'] = $data;
            }
        }

        if (isset($DVS["Sections"]['$RESLIST$'])) {
            exemod_start($this->System->File);
            foreach ($DVS["Sections"]['$RESLIST$'] as $file) {
                $value = exemod_extractstr($file);
                $file = substr($file, 5);
                $DVS['Data'][$file] = $value;
            }
            exemod_finish();
        }

        $this->DVS["Scripts"] = $DVS["Scripts"];
        $this->DVS["Data"] = $DVS["Data"];

        foreach ($DVS["Sections"] as $item => $value) {
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

        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $str)) {
            return false;
        }
        if (!$decoded) {
            return false;
        }
        if (base64_encode($decoded) != $str) {
            return false;
        }

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
        err_status(false);

        if (@unserialize($str) !== false) {
            return unserialize($str);
        }

        if (@json_decode($str, true) != false) {
            return json_decode($str, true);
        }

        err_status(true);

        return $str;
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

    private function getScriptName($script_data)
    {
        if (substr($script_data, 0, 5) != "<?php" && substr($script_data, 0, 2) != "<?") {
            return false;
        }

        $tokens = token_get_all($script_data);
        $name = false;
        foreach ($tokens as $i => $token){
            if(is_array($token)){
                if($token[0] == T_CLASS){
                    $name = $tokens[Utils::tsubtoken($tokens, $i, T_STRING, 3)][1];
                }
            }
        }

        return $name;
    }

    private function RemoveBCompiler($Data)
    {
        $bcfolder = $this->System->FileDir . "bcompiler";
        if (!file_exists($bcfolder)) {
            mkdir($bcfolder);
        } else {
            if (!is_dir($bcfolder)) {
                return false;
            }
        }
        $bcname = md5(rand() - time()) . "_" . time();
        $bcpath = $bcfolder . "/" . $bcname . ".txt";
        file_put_contents($bcpath, $Data);
        $dc = shell_exec('cd system && cd php && php.exe unbcompiler.phs "' . realpath($bcpath) . '"');
        file_put_contents($bcpath . "_decoded.txt", $dc);
        $d = '#ReDecompiler ' . ReDecompiler::VERSION;
        return str_replace(array("eval enc_getValue('__incCode');",
            'eval enc_getValue("__incCode");',
            'eval (enc_getValue("__incCode"));',
            "eval (enc_getValue('__incCode'));",
            ') = ;', '$form->x = $x - $cx - cursor_pos_x();'), array($d, $d, $d, $d, ');', ''), $dc);

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

    function create_op_code($code)
    {
        $code = str_replace('eval (enc_getvalue("__incCode"));', '#ReDecompiler ' . ReDecompiler::VERSION, $code);
        $code = str_replace('eval (enc_getValue("__incCode"));', '#ReDecompiler ' . ReDecompiler::VERSION, $code);
        $code = str_replace('}
}
', '', $code);
        $code = str_replace('
	}
', '', $code);

        $code = str_replace('
	
return true;
return NULL;

?>
', '#ReDecompiler ' . ReDecompiler::VERSION, $code);
        $code = str_replace(') = ;', ');', $code);
        $func = $class = false;
        $debug = false;
        $op = explode("\n", $code);
        $s = count($op);
        $sk = 0;
        $out = array();
        for ($i = 0; $i < $s; ++$i) {
            $opi = $op[$i];

            if (preg_match('/\{/', $opi)) {
                $sk++;
            } elseif (preg_match('/\}/', $opi)) {
                $sk--;
                if ($sk < 1) {
                    $func = false;
                }
                if ($sk < 0) {
                    $class = false;
                }
            }

            if (preg_match('/class ([a-zA-Z0-9_]*)/', $opi, $a)) {
                $class = $a[1];
                $debug = false;
                continue;
            } elseif (preg_match('/static public function ([a-zA-Z0-9_]*)\(.*\)/', $opi, $a)) {
                $func = $a[1];
                $debug = false;
                continue;
            } elseif (($class != false) && ($func != false) && ($sk > 1)) {
                if (!$debug) {
                    $debug = true;
                    continue;
                } else {
                    $out[$class][$func] .= $opi . "\r\n";
                    continue;
                }
            }
        }
        $out = str_replace('	
return true;
return NULL;

?>', '#ReDecompiler ' . ReDecompiler::VERSION, $out);
        return $out;
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

        if (is_bool($return) || !is_array($return)) {
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

    private function SaveDVS()
    {
        $this->System->Log("Saving File to DVS");

        $DFM = NULL;

        if (isset($this->DVS["Sections"]['$F\\XFORMS'])) {
            $DFM = $this->DVS["Sections"]['$F\\XFORMS'];
        } elseif (isset($this->DVS["Sections"]['$F_XFORMS'])) {
            $DFM = $this->DVS["Sections"]['$F_XFORMS'];
        }

        $DVS = array(
            "CONFIG" => $this->DVS["Sections"]['$X_CONFIG']["config"],
            "formsInfo" => $this->DVS["Sections"]['$X_CONFIG']["formsInfo"],
            "add_info" => array(
                'DV_VERSION' => '3.0.2.0',
                'DV_PREFIX' => 'beta 2'
            ),
            "eventDATA" => $this->DVS["Sections"]['$_EVENTS'],
            "DFM" => $DFM,
            "scripts" => $this->DVS['Scripts'],
            "Data" => $this->DVS['Data']
        );

        $file = $this->System->FileDir . pathinfo($this->System->File, PATHINFO_FILENAME) . '.dvs';
        file_put_contents($file, gzcompress(base64_encode(serialize($DVS)), 9));

        return $file;
    }

    private function SaveSource($otptfile)
    {
        $path_src = "./".$this->System->FileDir . "source";
        $path_src_sections = $path_src . "/sections";
        $path_src_dvs = $path_src . "/dvs";
        $path_src_scripts = $path_src . "/scripts";
        $path_src_resources = $path_src . "/resources";

        if (file_exists($path_src) || file_exists($path_src_sections) ||
            file_exists($path_src_dvs) || file_exists($path_src_scripts) ||
            file_exists($path_src_resources)) {
            return false;
        }

        mkdir($path_src);

        if(isset($this->DVS["Data"])) {
            mkdir($path_src_resources);
            foreach ($this->DVS["Data"] as $item => $value) {
                file_put_contents(
                    $path_src_resources . "/" . $item,
                    $value
                );
            }
        }

        if(isset($this->DVS["Scripts"])) {
            mkdir($path_src_scripts);
            foreach ($this->DVS["Scripts"] as $item => $value) {
                if (substr($item, -4) != ".php") {
                    $item .= ".php";
                }
                file_put_contents(
                    $path_src_scripts . "/" . $item,
                    $value
                );
            }
        }

        if(isset($this->DVS["Sections"])) {
            mkdir($path_src_sections);
            foreach ($this->DVS["Sections"] as $item => $value) {
                $item = str_ireplace(array(
                    '\\', '/'
                ),
                    '_',
                    $item
                );
                file_put_contents(
                    $path_src_sections . "/" . $item . ".txt",
                    Utils::return_var_dump($value)
                );
            }
        }

        if(file_exists($otptfile)) {
            mkdir($path_src_dvs);
            foreach ($this->string_sunpack(file_get_contents($otptfile)) as $item => $value) {
                file_put_contents(
                    $path_src_dvs . "/" . $item . ".txt",
                    Utils::return_var_dump($value)
                );
            }
        }

        return true;
    }
}