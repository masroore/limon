<?php


namespace Limonade;

class View
{
    /**
     * Returns a string to output
     *
     * Shortcut to render with no layout.
     *
     * @param string $content_or_func
     * @param string $locals
     * @return string
     */
    public static function partial($content_or_func, $locals = [])
    {
        return View::render($content_or_func, null, $locals);
    }

    /**
     * Returns a string to output
     *
     * It might use a template file, a function, or a formatted string (like {@link sprintf()}).
     * It could be embraced by a layout or not.
     * Local vars can be passed in addition to variables made available with the {@link set()}
     * function.
     *
     * @param string $content_or_func
     * @param string $layout
     * @param string $locals
     * @return string
     */
    public static function render($content_or_func, $layout = '', $locals = [])
    {
        $args = func_get_args();
        $content_or_func = array_shift($args);
        $layout = count($args) > 0 ? array_shift($args) : self::layout();
        $view_path = File::path(Core::option('views_dir'), $content_or_func);

        if (function_exists('before_render')) {
            list($content_or_func, $layout, $locals, $view_path) = before_render($content_or_func, $layout, $locals, $view_path);
        }

        $vars = array_merge(Core::set(), $locals);

        $flash = Helpers::flash_now();
        if (array_key_exists('flash', $vars)) {
            trigger_error('A $flash variable is already passed to view. Flash messages will only be accessible through flash_now()', E_USER_NOTICE);
        } elseif (!empty($flash)) {
            $vars['flash'] = $flash;
        }

        $infinite_loop = false;

        # Avoid infinite loop: this function is in the backtrace ?
        if (function_exists($content_or_func)) {
            $back_trace = debug_backtrace();
            while ($trace = array_shift($back_trace)) {
                if ($trace['function'] === strtolower($content_or_func)) {
                    $infinite_loop = true;
                    break;
                }
            }
        }

        if (function_exists($content_or_func) && !$infinite_loop) {
            ob_start();
            call_user_func($content_or_func, $vars);
            $content = ob_get_clean();
        } elseif (file_exists($view_path)) {
            ob_start();
            extract($vars);
            include $view_path;
            $content = ob_get_clean();
        } else {
            if (substr_count($content_or_func, '%') !== count($vars)) {
                $content = $content_or_func;
            } else {
                $content = vsprintf($content_or_func, $vars);
            }
        }

        if (empty($layout)) {
            return $content;
        }

        return View::render($layout, null, compact('content'));
    }

    /**
     * Set and return current layout
     *
     * @param string $function_or_file
     * @return string
     */
    public static function layout($function_or_file = null)
    {
        static $layout = null;
        if (func_num_args() > 0) {
            $layout = $function_or_file;
        }
        return $layout;
    }

    /**
     * Returns html output with proper http headers
     *
     * @param string $content_or_func
     * @param string $layout
     * @param string $locals
     * @return string
     */
    public static function html($content_or_func, $layout = '', $locals = [])
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=' . strtolower(Core::option('encoding')));
        }
        $args = func_get_args();
        return self::render(...$args);
    }

    /**
     * Returns xml output with proper http headers
     *
     * @param string $content_or_func
     * @param string $layout
     * @param string $locals
     * @return string
     */
    public static function xml($data)
    {
        if (!headers_sent()) {
            header('Content-Type: text/xml; charset=' . strtolower(Core::option('encoding')));
        }
        $args = func_get_args();
        return self::render(...$args);
    }

    /**
     * Returns css output with proper http headers
     *
     * @param string $content_or_func
     * @param string $layout
     * @param string $locals
     * @return string
     */
    public static function css($content_or_func, $layout = '', $locals = [])
    {
        if (!headers_sent()) {
            header('Content-Type: text/css; charset=' . strtolower(Core::option('encoding')));
        }
        $args = func_get_args();
        return self::render(...$args);
    }

    /**
     * Returns txt output with proper http headers
     *
     * @param string $content_or_func
     * @param string $layout
     * @param string $locals
     * @return string
     */
    public static function txt($content_or_func, $layout = '', $locals = [])
    {
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=' . strtolower(Core::option('encoding')));
        }
        $args = func_get_args();
        return self::render(...$args);
    }

    /**
     * Returns json representation of data with proper http headers
     *
     * @param string $data
     * @param int $json_option
     * @return string
     */
    public static function json($data, $json_option = 0)
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=' . strtolower(Core::option('encoding')));
        }
        return json_encode($data, $json_option);
    }

    /**
     * undocumented function
     *
     * @param string $filename
     * @param string $return
     * @return mixed number of bytes delivered or file output if $return = true
     */
    public static function render_file($filename, $return = false)
    {
        # TODO implements X-SENDFILE headers
        // if($x-sendfile = option('x-sendfile'))
        // {
        //    // add a X-Sendfile header for apache and Lighttpd >= 1.5
        //    if($x-sendfile > X-SENDFILE) // add a X-LIGHTTPD-send-file header
        //
        // }
        // else
        // {
        //
        // }
        $filename = str_replace('../', '', $filename);
        if (file_exists($filename)) {
            $content_type = File::mime_type(File::extension($filename));
            $header = 'Content-type: ' . $content_type;
            if (File::is_text($filename)) {
                $header .= '; charset=' . strtolower(Core::option('encoding'));
            }
            if (!headers_sent()) {
                header($header);
            }
            return File::read($filename, $return);
        }

        Error::halt(NOT_FOUND, "unknown filename $filename");
    }
}
