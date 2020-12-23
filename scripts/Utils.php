<?php


class Utils
{
    static function hashDirectory($directory)
    {
        if (! is_dir($directory))
        {
            return false;
        }

        $files = array();
        $dir = dir($directory);

        while (false !== ($file = $dir->read()))
        {
            if ($file != '.' and $file != '..')
            {
                if (is_dir($directory . '/' . $file))
                {
                    $files[] = self::hashDirectory($directory . '/' . $file);
                }
                else
                {
                    $files[] = md5_file($directory . '/' . $file);
                }
            }
        }

        $dir->close();

        return md5(implode('', $files));
    }

    static function formatFileSize($size)
    {
        $a = array("B", "KB", "MB", "GB", "TB", "PB");
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }
        return round($size, 2) . " " . $a[$pos];
    }

    static function return_var_dump()
    {
        $args = func_get_args();
        ob_start();
        call_user_func_array('var_dump', $args);
        return ob_get_clean();
    }
}