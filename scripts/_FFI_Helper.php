<?php

class _FFI_Helper
{
    static function fix($string)
    {
        $replace = array(
            'BOOLEAN ' => 'sint8 ',
            'BOOL ' => 'int ',
            'LPDWORD ' => 'int ',
            'DWORD ' => 'int ',
            'BYTE ' => 'int ',
            'INTEGER ' => 'int ',
            'STRING ' => 'char *',
            'UINT ' => 'int ',
            'CARDINAL ' => 'int ',
            'SHORT ' => 'int ',
            'LPSTR ' => 'char *',
            'LPCSTR ' => 'char *',
            'LPCTSTR ' => 'char *',
            'LPVOID ' => 'int ',
            'LCID ' => 'uint32 ',
            'HWND ' => 'int ',
            'WINAPI ' => '',
            'HANDLE ' => 'int '
        );

        return str_ireplace(array_keys($replace), array_values($replace), $string);
    }

    static function eval_dll_args($path_to_ffi, $var_func_name, $args)
    {
        _FFI_Helper::array2_to_array($args);
        $evl = '$_args = json_decode(base64_decode("' . base64_encode(json_encode($args)) . '"), true);' . "\n";
        $evl .= 'return ' . $path_to_ffi . '->{' . $var_func_name . '}(';
        foreach ($args as $argi => $argv) {
            $evl .= '$_args[' . $argi . ']' . (sizeof($args) - 1 == $argi ? "" : ",");
        }
        $evl .= ");";
        return $evl;
    }

    static function array2_to_array(&$args)
    {
        $i = 0;
        while (1) {
            if ($i >= sizeof($args)) {
                break;
            }
            if (is_array($args[$i])) {
                $as = $args[$i];
                unset($args[$i]);
                foreach ($as as $ad) {
                    if (is_array($ad)) {
                        self::array2_to_array($ad);
                    }
                    $args[] = $ad;
                }
            }
            $i += 1;
        }
        $args = array_values($args);
    }
}