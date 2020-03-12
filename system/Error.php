<?php


namespace Limonade;

class Error
{
    /**
     * Raise an error, passing a given error number and an optional message,
     * then exit.
     * Error number should be a HTTP status code or a php user error (E_USER...)
     * $errno and $msg arguments can be passsed in any order
     * If no arguments are passed, default $errno is SERVER_ERROR (500)
     *
     * @param int,string $errno Error number or message string
     * @param string,string $msg Message string or error number
     * @param mixed $debug_args extra data provided for debugging
     * @return void
     */
    public static function halt($errno = SERVER_ERROR, $msg = '', $debug_args = null)
    {
        $args = func_get_args();
        $error = array_shift($args);

        # switch $errno and $msg args
        # TODO cleanup / refactoring
        if (is_string($errno)) {
            $msg = $errno;
            $oldmsg = array_shift($args);
            $errno = empty($oldmsg) ? SERVER_ERROR : $oldmsg;
        } elseif (!empty($args)) {
            $msg = array_shift($args);
        }

        if (empty($msg) && $errno == NOT_FOUND) {
            $msg = Request::request_uri();
        }
        if (empty($msg)) {
            $msg = "";
        }
        if (!empty($args)) {
            $debug_args = $args;
        }
        Core::set('_lim_err_debug_args', $debug_args);

        Error::handler_dispatcher($errno, $msg, null, null);
    }

    /**
     * Internal error handler dispatcher
     * Find and call matching error handler and exit
     * If no match found, call default error handler
     *
     * @access private
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     * @return void
     */
    public static function handler_dispatcher($errno, $errstr, $errfile, $errline)
    {
        $back_trace = debug_backtrace();
        while ($trace = array_shift($back_trace)) {
            if ($trace['function'] === 'halt') {
                $errfile = $trace['file'];
                $errline = $trace['line'];
                break;
            }
        }

        # Notices and warning won't halt execution
        if (Error::wont_halt_app($errno)) {
            Error::notice($errno, $errstr, $errfile, $errline);
            return;
        }

        # Other errors will stop application
        static $handlers = [];
        if (empty($handlers)) {
            Error::error(E_LIM_PHP, 'error_default_handler');
            $handlers = Error::error();
        }

        $is_http_err = Http::response_status_is_valid($errno);
        while ($handler = array_shift($handlers)) {
            $e = is_array($handler['errno']) ? $handler['errno'] : [$handler['errno']];
            while ($ee = array_shift($e)) {
                if ($ee == $errno || $ee == E_LIM_PHP || ($ee == E_LIM_HTTP && $is_http_err)) {
                    echo Utils::call_if_exists($handler['function'], $errno, $errstr, $errfile, $errline);
                    exit;
                }
            }
        }
    }

    /**
     * Checks if an error is will halt application execution.
     * Notices and warnings will not.
     *
     * @access private
     * @param string $num error code number
     * @return boolean
     */
    public static function wont_halt_app($num)
    {
        return $num == E_NOTICE ||
            $num == E_WARNING ||
            $num == E_CORE_WARNING ||
            $num == E_COMPILE_WARNING ||
            $num == E_USER_WARNING ||
            $num == E_USER_NOTICE ||
            $num == E_DEPRECATED ||
            $num == E_USER_DEPRECATED ||
            $num == E_LIM_DEPRECATED;
    }

    /**
     * Set a notice if arguments are provided
     * Returns all stored notices.
     * If $errno argument is null, reset the notices array
     *
     * @access private
     * @param string, null $str
     * @return array
     */
    public static function notice($errno = false, $errstr = null, $errfile = null, $errline = null)
    {
        static $notices = [];
        if ($errno) {
            $notices[] = compact('errno', 'errstr', 'errfile', 'errline');
        } elseif ($errno === null) {
            $notices = [];
        }
        return $notices;
    }

    /**
     * Associate a function with error code(s) and return all associations
     *
     * @param string $errno
     * @param string $function
     * @return array
     */
    public static function error($errno = null, $function = null)
    {
        static $errors = [];
        if (func_num_args() > 0) {
            $errors[] = ['errno' => $errno, 'function' => $function];
        }
        return $errors;
    }

    /**
     * Default error handler
     *
     * @param string $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     * @return string error output
     */
    public static function default_handler($errno, $errstr, $errfile, $errline)
    {
        $is_http_err = Http::response_status_is_valid($errno);
        $http_error_code = $is_http_err ? $errno : SERVER_ERROR;

        Http::status($http_error_code);

        return $http_error_code == NOT_FOUND ?
            Error::not_found_output($errno, $errstr, $errfile, $errline) :
            Error::server_error_output($errno, $errstr, $errfile, $errline);
    }

    /**
     * Returns not found error output
     *
     * @access private
     * @param string $msg
     * @return string
     */
    public static function not_found_output($errno, $errstr, $errfile, $errline)
    {
        if (!function_exists('not_found')) {
            /**
             * Default not found error output
             *
             * @param string $errno
             * @param string $errstr
             * @param string $errfile
             * @param string $errline
             * @return string
             */
            function not_found($errno, $errstr, $errfile = null, $errline = null)
            {
                Core::option('views_dir', Core::option('error_views_dir'));
                $msg = Helpers::h(rawurldecode($errstr));
                return View::html("<h1>Page not found:</h1><p><code>{$msg}</code></p>", Error::layout());
            }
        }
        return not_found($errno, $errstr, $errfile, $errline);
    }

    /**
     * Set and returns error output layout
     *
     * @param string $layout
     * @return string
     */
    public static function layout($layout = false)
    {
        static $o_layout = 'default_layout.php';
        if ($layout !== false) {
            Core::option('error_views_dir', Core::option('views_dir'));
            $o_layout = $layout;
        }
        return $o_layout;
    }

    /**
     * Returns server error output
     *
     * @access private
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     * @return string
     */
    public static function server_error_output($errno, $errstr, $errfile, $errline)
    {
        if (!function_exists('server_error')) {
            /**
             * Default server error output
             *
             * @param string $errno
             * @param string $errstr
             * @param string $errfile
             * @param string $errline
             * @return string
             */
            function server_error($errno, $errstr, $errfile = null, $errline = null)
            {
                $is_http_error = Http::response_status_is_valid($errno);
                $args = compact('errno', 'errstr', 'errfile', 'errline', 'is_http_error');
                Core::option('views_dir', Core::option('limonade_views_dir'));
                $html = View::render('error.html.php', null, $args);
                Core::option('views_dir', Core::option('error_views_dir'));
                return View::html($html, Error::layout(), $args);
            }
        }
        return server_error($errno, $errstr, $errfile, $errline);
    }

    /**
     * Returns notices output rendering and reset notices
     *
     * @return string
     */
    public static function error_notices_render()
    {
        if (Core::option('debug') && Core::option('env') > ENV_PRODUCTION) {
            $notices = Error::notice();
            Error::notice(null); // reset notices
            $c_view_dir = Core::option('views_dir'); // keep for restore after render
            Core::option('views_dir', Core::option('limonade_views_dir'));
            $o = View::render('_notices.html.php', null, ['notices' => $notices]);
            Core::option('views_dir', $c_view_dir); // restore current views dir

            return $o;
        }
    }

    /**
     * return error code name for a given code num, or return all errors names
     *
     * @param string $num
     * @return mixed
     */
    public static function type($num = null)
    {
        $types = [
            E_ERROR => 'ERROR',
            E_WARNING => 'WARNING',
            E_PARSE => 'PARSING ERROR',
            E_NOTICE => 'NOTICE',
            E_CORE_ERROR => 'CORE ERROR',
            E_CORE_WARNING => 'CORE WARNING',
            E_COMPILE_ERROR => 'COMPILE ERROR',
            E_COMPILE_WARNING => 'COMPILE WARNING',
            E_USER_ERROR => 'USER ERROR',
            E_USER_WARNING => 'USER WARNING',
            E_USER_NOTICE => 'USER NOTICE',
            E_STRICT => 'STRICT NOTICE',
            E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
            E_DEPRECATED => 'DEPRECATED WARNING',
            E_USER_DEPRECATED => 'USER DEPRECATED WARNING',
            E_LIM_DEPRECATED => 'LIMONADE DEPRECATED WARNING'
        ];
        return $num === null ? $types : $types[$num];
    }

    /**
     * Returns http response status for a given error number
     *
     * @param string $errno
     * @return int
     */
    public static function http_status($errno)
    {
        $code = Http::response_status_is_valid($errno) ? $errno : SERVER_ERROR;
        return Http::response_status($code);
    }
}
