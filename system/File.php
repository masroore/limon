<?php


namespace Limonade;

class File
{
    /**
     * Create a file path by concatenation of given arguments.
     * Windows paths with backslash directory separators are normalized in *nix paths.
     *
     * @param string $path , ...
     * @return string normalized path
     */
    public static function path($path)
    {
        $args = func_get_args();
        $ds = '/';
        $win_ds = '\\';
        $n_path = count($args) > 1 ? implode($ds, $args) : $path;
        if (strpos($n_path, $win_ds) !== false) {
            $n_path = str_replace($win_ds, $ds, $n_path);
        }
        $n_path = preg_replace(sprintf('/%s{2,}/', preg_quote($ds, $ds)), $ds, $n_path);
        return $n_path;
    }

    /**
     * Checks if $filename is a binary file
     *
     * @param string $filename
     * @return bool|void
     */
    public static function is_binary($filename)
    {
        $is_text = self::is_text($filename);
        return $is_text === null ? null : !$is_text;
    }

    /**
     * Checks if $filename is a text file
     *
     * @param string $filename
     * @return bool
     */
    public static function is_text($filename)
    {
        if ($mime = File::mime_content_type($filename)) {
            return strpos($mime, 'text/') === 0;
        }
        return null;
    }

    /**
     * Detect MIME Content-type for a file
     *
     * @param string $filename Path to the tested file.
     * @return string
     */
    public static function mime_content_type($filename)
    {
        $ext = File::extension($filename); /* strtolower isn't necessary */
        if ($mime = File::mime_type($ext)) {
            return $mime;
        }

        if (function_exists('finfo_open')) {
            if ($finfo = finfo_open(FILEINFO_MIME)) {
                if ($mime = finfo_file($finfo, $filename)) {
                    finfo_close($finfo);
                    return $mime;
                }
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Returns file extension or false if none
     *
     * @param string $filename
     * @return string, false
     */
    public static function extension($filename)
    {
        $pos = strrpos($filename, '.');
        if ($pos !== false) {
            return substr($filename, $pos + 1);
        }
        return false;
    }

    /**
     * Returns mime type for a given extension or if no extension is provided,
     * all mime types in an associative array, with extensions as keys.
     * (extracted from Orbit source http://orbit.luaforge.net/)
     *
     * @param string $ext
     * @return array|string
     */
    public static function mime_type($ext = null)
    {
        $types = [
            'ai' => 'application/postscript',
            'aif' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'aiff' => 'audio/x-aiff',
            'asc' => 'text/plain',
            'atom' => 'application/atom+xml',
            'au' => 'audio/basic',
            'avi' => 'video/x-msvideo',
            'bcpio' => 'application/x-bcpio',
            'bin' => 'application/octet-stream',
            'bmp' => 'image/bmp',
            'cdf' => 'application/x-netcdf',
            'cgm' => 'image/cgm',
            'class' => 'application/octet-stream',
            'cpio' => 'application/x-cpio',
            'cpt' => 'application/mac-compactpro',
            'csh' => 'application/x-csh',
            'css' => 'text/css',
            'csv' => 'text/csv',
            'dcr' => 'application/x-director',
            'dir' => 'application/x-director',
            'djv' => 'image/vnd.djvu',
            'djvu' => 'image/vnd.djvu',
            'dll' => 'application/octet-stream',
            'dmg' => 'application/octet-stream',
            'dms' => 'application/octet-stream',
            'doc' => 'application/msword',
            'dtd' => 'application/xml-dtd',
            'dvi' => 'application/x-dvi',
            'dxr' => 'application/x-director',
            'eps' => 'application/postscript',
            'etx' => 'text/x-setext',
            'exe' => 'application/octet-stream',
            'ez' => 'application/andrew-inset',
            'gif' => 'image/gif',
            'gram' => 'application/srgs',
            'grxml' => 'application/srgs+xml',
            'gtar' => 'application/x-gtar',
            'hdf' => 'application/x-hdf',
            'hqx' => 'application/mac-binhex40',
            'htm' => 'text/html',
            'html' => 'text/html',
            'ice' => 'x-conference/x-cooltalk',
            'ico' => 'image/x-icon',
            'ics' => 'text/calendar',
            'ief' => 'image/ief',
            'ifb' => 'text/calendar',
            'iges' => 'model/iges',
            'igs' => 'model/iges',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'js' => 'application/x-javascript',
            'json' => 'application/json',
            'kar' => 'audio/midi',
            'latex' => 'application/x-latex',
            'lha' => 'application/octet-stream',
            'lzh' => 'application/octet-stream',
            'm3u' => 'audio/x-mpegurl',
            'man' => 'application/x-troff-man',
            'mathml' => 'application/mathml+xml',
            'me' => 'application/x-troff-me',
            'mesh' => 'model/mesh',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            'mif' => 'application/vnd.mif',
            'mov' => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mp2' => 'audio/mpeg',
            'mp3' => 'audio/mpeg',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpga' => 'audio/mpeg',
            'ms' => 'application/x-troff-ms',
            'msh' => 'model/mesh',
            'mxu' => 'video/vnd.mpegurl',
            'nc' => 'application/x-netcdf',
            'oda' => 'application/oda',
            'ogg' => 'application/ogg',
            'pbm' => 'image/x-portable-bitmap',
            'pdb' => 'chemical/x-pdb',
            'pdf' => 'application/pdf',
            'pgm' => 'image/x-portable-graymap',
            'pgn' => 'application/x-chess-pgn',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'ppm' => 'image/x-portable-pixmap',
            'ppt' => 'application/vnd.ms-powerpoint',
            'ps' => 'application/postscript',
            'qt' => 'video/quicktime',
            'ra' => 'audio/x-pn-realaudio',
            'ram' => 'audio/x-pn-realaudio',
            'ras' => 'image/x-cmu-raster',
            'rdf' => 'application/rdf+xml',
            'rgb' => 'image/x-rgb',
            'rm' => 'application/vnd.rn-realmedia',
            'roff' => 'application/x-troff',
            'rss' => 'application/rss+xml',
            'rtf' => 'text/rtf',
            'rtx' => 'text/richtext',
            'sgm' => 'text/sgml',
            'sgml' => 'text/sgml',
            'sh' => 'application/x-sh',
            'shar' => 'application/x-shar',
            'silo' => 'model/mesh',
            'sit' => 'application/x-stuffit',
            'skd' => 'application/x-koan',
            'skm' => 'application/x-koan',
            'skp' => 'application/x-koan',
            'skt' => 'application/x-koan',
            'smi' => 'application/smil',
            'smil' => 'application/smil',
            'snd' => 'audio/basic',
            'so' => 'application/octet-stream',
            'spl' => 'application/x-futuresplash',
            'src' => 'application/x-wais-source',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            't' => 'application/x-troff',
            'tar' => 'application/x-tar',
            'tcl' => 'application/x-tcl',
            'tex' => 'application/x-tex',
            'texi' => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'tr' => 'application/x-troff',
            'tsv' => 'text/tab-separated-values',
            'txt' => 'text/plain',
            'ustar' => 'application/x-ustar',
            'vcd' => 'application/x-cdlink',
            'vrml' => 'model/vrml',
            'vxml' => 'application/voicexml+xml',
            'wav' => 'audio/x-wav',
            'wbmp' => 'image/vnd.wap.wbmp',
            'wbxml' => 'application/vnd.wap.wbxml',
            'wml' => 'text/vnd.wap.wml',
            'wmlc' => 'application/vnd.wap.wmlc',
            'wmls' => 'text/vnd.wap.wmlscript',
            'wmlsc' => 'application/vnd.wap.wmlscriptc',
            'wrl' => 'model/vrml',
            'xbm' => 'image/x-xbitmap',
            'xht' => 'application/xhtml+xml',
            'xhtml' => 'application/xhtml+xml',
            'xls' => 'application/vnd.ms-excel',
            'xml' => 'application/xml',
            'xpm' => 'image/x-xpixmap',
            'xsl' => 'application/xml',
            'xslt' => 'application/xslt+xml',
            'xul' => 'application/vnd.mozilla.xul+xml',
            'xwd' => 'image/x-xwindowdump',
            'xyz' => 'chemical/x-xyz',
            'zip' => 'application/zip'
        ];
        return $ext === null ? $types : $types[strtolower($ext)];
    }

    /**
     * Return or output file content
     *
     * @param string $filename
     * @param bool $return
     * @return    string, int
     *
     */

    public static function read($filename, $return = false)
    {
        if (!file_exists($filename)) {
            trigger_error("$filename doesn't exists", E_USER_ERROR);
        }
        if ($return) {
            return file_get_contents($filename);
        }
        return self::read_chunked($filename);
    }

    /**
     * Read and output file content and return filesize in bytes or status after
     * closing file.
     * This function is very efficient for outputing large files without timeout
     * nor too expensive memory use
     *
     * @param string $filename
     * @param string $retbytes
     * @return bool, int
     */
    public static function read_chunked($filename, $retbytes = true)
    {
        $chunksize = 1 * (1024 * 1024); // how many bytes per chunk
        $cnt = 0;
        $handle = fopen($filename, 'rb');
        if ($handle === false) {
            return false;
        }

        ob_start();
        while (!feof($handle)) {
            $buffer = fread($handle, $chunksize);
            echo $buffer;
            ob_flush();
            flush();
            if ($retbytes) {
                $cnt += strlen($buffer);
            }
            set_time_limit(0);
        }
        ob_end_flush();

        $status = fclose($handle);
        if ($retbytes && $status) {
            return $cnt;
        } // return num. bytes delivered like readfile() does.
        return $status;
    }

    /**
     * Returns an array of files contained in a directory
     *
     * @param string $dir
     * @return array
     */
    public static function list_dir($dir)
    {
        $files = [];
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file[0] !== '.' && $file !== '..') {
                    $files[] = $file;
                }
            }
            closedir($handle);
        }
        return $files;
    }
}
