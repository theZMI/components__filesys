<?php

/**
 * Класс для работы с каталогами и файлами в системе
 *
 * @author Zmi
 */
class FileSys
{
    private function __construct()
    {
    }

    public static function filenameSecurity($str)
    {
        $bad = [
            "../",
            "./",
            "<!--",
            "-->",
            "<",
            ">",
            "'",
            '"',
            '&',
            '$',
            '#',
            '{',
            '}',
            '[',
            ']',
            '=',
            ';',
            '?',
            "%20",
            "%22",
            "%3c",   // <
            "%253c", // <
            "%3e",   // >
            "%0e",   // >
            "%28",   // (
            "%29",   // )
            "%2528", // (
            "%26",   // &
            "%24",   // $
            "%3f",   // ?
            "%3b",   // ;
            "%3d"    // =
        ];

        return stripslashes(str_replace($bad, '', $str));
    }

    /**
     * Удаляет каталог с его содержимым
     */
    public static function deleteDir($directory)
    {
        $dir = opendir($directory);
        while (($file = readdir($dir))) {
            if (is_file($directory . '/' . $file)) {
                unlink($directory . '/' . $file);
            } elseif (is_dir($directory . '/' . $file) && ($file != '.') && ($file != '..')) {
                self::deleteDir($directory . '/' . $file);
            }
        }
        closedir($dir);

        return rmdir($directory);
    }

    /**
     * Создать каталоги по указанному пути
     */
    public static function makeDir($path)
    {
        if (is_dir($path)) {
            return;
        }

        $path = explode('/', $path);
        if (count($path) == 1) {
            $path = explode('\\', $path[0]);
        }

        $d = "";
        foreach ($path as $dir) {
            $d .= $dir . "/";

            // Если сейчас идёт перечисление папок до корневой сайта, то пропускаем их ибо сайте не должен создавать папки до своей корневой
            if (stripos(BASEPATH, $d) !== false) {
                continue;
            }

            if (!is_dir($d)) {
                @mkdir($d, 0777);
                @chmod($d, 0777);
            }
        }
    }

    /**
     * Cоздать и записать содержимое в файл по указанному пути, если каталоги указанные в пути не созданы, то их создадут
     */
    public static function writeFile($file, $data, $flgAppend = false)
    {
        $file = self::filenameSecurity($file);
        if (!$flgAppend) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }

        self::makeDir(dirname($file));
        fclose(fopen($file, 'a+b'));
        $f   = fopen($file, $flgAppend ? 'a+b' : 'r+b');
        $ret = fwrite($f, $data);
        fclose($f);
        @chmod($file, 0777);

        return $ret;
    }

    /**
     * Читает файл по переданному пути
     */
    public static function readFile($file)
    {
        $file = self::filenameSecurity(trim($file));

        if (!strlen(trim($file))) {
            return false;
        }

        $b = false;
        if (strstr($file, 'http://') == $file) {
            $f = fopen($file, 'rb');
            while (!feof($f)) {
                $b .= fread($f, 1024);
            }
        } elseif (file_exists($file) && is_readable($file)) {
            $f    = fopen($file, 'rb');
            $size = filesize($file);
            $b    = ($size == 0) ? "" : fread($f, $size);
        }

        if (isset($f) && $f) {
            fclose($f);
        }

        return $b;
    }

    /**
     * Получает список файлов каталога со всеми вложенными каталогами
     *
     * @param string $dir
     *
     * @return array
     */
    public static function readList($dir)
    {
        if (!is_readable($dir)) {
            return [];
        }

        $list = [];
        $dir  = in_array(substr($dir, -1, 1), ['/', '\\']) ? $dir : "$dir/";
        $hDir = opendir($dir);
        while (($f = readdir($hDir)) !== false) {
            if ($f != '.' && $f != '..') {
                $path     = $dir . $f;
                $list[$f] = is_dir($path) ? self::readList($path) : $path;
            }
        }
        closedir($hDir);

        return $list;
    }

    /**
     * Возвращает размер файла в виде: Kb Mb Gb
     */
    public static function size($file)
    {
        $size         = sprintf("%u", filesize($file));
        $filesizename = [" Bytes", " Kb", " Mb", " Gb", " Tb", " Pb", " Eb", " Zb", " Yb"];

        return $size ? round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
    }
}
