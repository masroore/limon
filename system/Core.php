<?php


namespace Limonade;

class Core
{
    /**
     * Sets a template variable with a value or a default value if value is empty
     *
     * @param string $name
     * @param string $value
     * @param string $default
     * @return mixed setted value
     */
    public static function set_or_default($name, $value, $default)
    {
        return Core::set($name, Utils::value_or_default($value, $default));
    }

    /**
     * Set and returns template variables
     *
     * If multiple values are provided, set $name variable with an array of those values.
     * If there is only one value, set $name variable with the provided $values
     *
     * @param string $name
     * @param mixed $values,...
     * @return mixed variable value for $name if $name argument is provided, else return all variables
     */
    public static function set($name = null, $values = null)
    {
        static $vars = [];
        $args = func_get_args();
        $name = array_shift($args);
        if ($name === null) {
            return $vars;
        }
        if (!empty($args)) {
            $vars[$name] = count($args) > 1 ? $args : $args[0];
        }
        if (array_key_exists($name, $vars)) {
            return $vars[$name];
        }
        return $vars;
    }

    /**
     * Running application
     *
     * @param array $env
     * @return void
     */
    public static function run($env = null)
    {
        if ($env === null) {
            $env = static::env();
        }

        # 0. Set default configuration
        $root_dir = dirname(static::app_file());
        $base_path = dirname(File::path($env['SERVER']['SCRIPT_NAME']));
        $base_file = basename($env['SERVER']['SCRIPT_NAME']);
        $base_uri = File::path($base_path, (($base_file === 'index.php') ? '?' : $base_file . '?'));
        $lim_dir = dirname(__FILE__);
        static::option('root_dir', $root_dir);
        static::option('base_path', $base_path);
        static::option('base_uri', $base_uri); // set it manually if you use url_rewriting
        static::option('limonade_dir', File::path($lim_dir));
        static::option('limonade_views_dir', File::path($lim_dir, 'limonade', 'views'));
        static::option('limonade_public_dir', File::path($lim_dir, 'limonade', 'public'));
        static::option('public_dir', File::path($root_dir, 'public'));
        static::option('views_dir', File::path($root_dir, 'views'));
        static::option('controllers_dir', File::path($root_dir, 'controllers'));
        static::option('lib_dir', File::path($root_dir, 'lib'));
        static::option('error_views_dir', static::option('limonade_views_dir'));
        static::option('env', ENV_PRODUCTION);
        static::option('debug', true);
        static::option('session', LIM_SESSION_NAME); // true, false or the name of your session
        static::option('encoding', 'utf-8');
        static::option('signature', LIM_NAME); // X-Limonade header value or false to hide it
        static::option('gzip', false);
        static::option('x-sendfile', 0); // 0: disabled,
        // X-SENDFILE: for Apache and Lighttpd v. >= 1.5,
        // X-LIGHTTPD-SEND-FILE: for Apache and Lighttpd v. < 1.5

        # 1. Set handlers
        # 1.1 Set error handling
        ini_set('display_errors', 1);
        set_error_handler('Limonade\Error::handler_dispatcher', E_ALL ^ E_NOTICE);

        # 1.2 Register shutdown public static function
        register_shutdown_function('Limonade\Core::stop_and_exit');

        # 2. Set user configuration
        Utils::call_if_exists('configure');

        # 2.1 Set gzip compression if defined
        if (is_bool(Core::option('gzip')) && Core::option('gzip')) {
            ini_set('zlib.output_compression', '1');
        }

        # 2.2 Set X-Limonade header
        if ($signature = Core::option('signature')) {
            header("X-Limonade: $signature");
        }

        # 3. Loading libs
        Utils::require_once_dir(Core::option('lib_dir'));

        # 4. Starting session
        if (!defined('SID') && Core::option('session')) {
            if (!is_bool(Core::option('session'))) {
                session_name(Core::option('session'));
            }
            if (!session_start()) {
                trigger_error('An error occured while trying to start the session', E_USER_WARNING);
            }
        }

        # 5. Set some default methods if needed
        if (!function_exists('after')) {
            function after($output, $route)
            {
                return $output;
            }
        }
        if (!function_exists('route_missing')) {
            function route_missing($request_method, $request_uri)
            {
                Error::halt(NOT_FOUND, "($request_method) $request_uri");
            }
        }

        Utils::call_if_exists('initialize');

        # 6. Check request
        if ($rm = Request::request_method()) {
            if (Request::request_is_head()) {
                ob_start();
            } // then no output

            if (!Request::request_method_is_allowed($rm)) {
                Error::halt(HTTP_NOT_IMPLEMENTED, "The requested method <code>'$rm'</code> is not implemented");
            }

            # 6.1 Check matching route
            if ($route = Router::find($rm, Request::request_uri())) {
                Core::params($route['params']);

                # 6.2 Load controllers dir
                if (!function_exists('autoload_controller')) {
                    function autoload_controller($callback)
                    {
                        Utils::require_once_dir(Core::option('controllers_dir'));
                    }
                }
                autoload_controller($route['function']);

                if (is_callable($route['function'])) {
                    # 6.3 Call before public static function
                    Utils::call_if_exists('before', $route);

                    # 6.4 Call matching controller public static function and output result
                    $output = call_user_func_array($route['function'], array_values($route['params']));
                    if ($output === null) {
                        $output = Utils::call_if_exists('autorender', $route);
                    }
                    echo after(Error::error_notices_render() . $output, $route);
                } else {
                    Error::halt(SERVER_ERROR, "Routing error: undefined function '{$route['function']}'", $route);
                }
            } else {
                route_missing($rm, Request::request_uri());
            }
        } else {
            Error::halt(HTTP_NOT_IMPLEMENTED, "The requested method <code>'$rm'</code> is not implemented");
        }
    }

    /**
     * Returns limonade environment variables:
     *
     * 'SERVER', 'FILES', 'REQUEST', 'SESSION', 'ENV', 'COOKIE',
     * 'GET', 'POST', 'PUT', 'DELETE'
     *
     * If a null argument is passed, reset and rebuild environment
     *
     * @param null @reset reset and rebuild environment
     * @return array
     */
    public static function env($reset = null)
    {
        static $env = [];
        if (func_num_args() > 0) {
            $args = func_get_args();
            if ($args[0] === null) {
                $env = [];
            }
        }

        if (empty($env)) {
            if (empty($GLOBALS['_SERVER'])) {
                // Fixing empty $GLOBALS['_SERVER'] bug
                // http://sofadesign.lighthouseapp.com/projects/29612-limonade/tickets/29-env-is-empty
                $GLOBALS['_SERVER'] =& $_SERVER;
                $GLOBALS['_FILES'] =& $_FILES;
                $GLOBALS['_REQUEST'] =& $_REQUEST;
                $GLOBALS['_SESSION'] =& $_SESSION;
                $GLOBALS['_ENV'] =& $_ENV;
                $GLOBALS['_COOKIE'] =& $_COOKIE;
            }

            $glo_names = ['SERVER', 'FILES', 'REQUEST', 'SESSION', 'ENV', 'COOKIE'];

            $vars = array_merge($glo_names, Request::request_methods());
            foreach ($vars as $var) {
                $varname = "_$var";
                if (!array_key_exists($varname, $GLOBALS)) {
                    $GLOBALS[$varname] = [];
                }
                $env[$var] =& $GLOBALS[$varname];
            }

            $method = Request::request_method($env);
            if ($method === HTTP_METHOD_PUT || $method === HTTP_METHOD_DELETE) {
                $varname = "_$method";
                if (array_key_exists('_method', $_POST) && $_POST['_method'] == $method) {
                    foreach ($_POST as $k => $v) {
                        if ($k === '_method') {
                            continue;
                        }
                        $GLOBALS[$varname][$k] = $v;
                    }
                } else {
                    parse_str(file_get_contents('php://input'), $GLOBALS[$varname]);
                }
            }
        }
        return $env;
    }

    /**
     * Returns application root file path
     *
     * @return string
     */
    public static function app_file()
    {
        static $file;
        if (empty($file)) {
            $debug_backtrace = debug_backtrace();
            $stacktrace = array_pop($debug_backtrace);
            $file = $stacktrace['file'];
        }
        return File::path($file);
    }

    /**
     * Set and returns options values
     *
     * If multiple values are provided, set $name option with an array of those values.
     * If there is only one value, set $name option with the provided $values
     *
     * @param string $name
     * @param mixed $values,...
     * @return mixed option value for $name if $name argument is provided, else return all options
     */
    public static function option($name = null, $values = null)
    {
        static $options = [];
        $args = func_get_args();
        $name = array_shift($args);
        if ($name === null) {
            return $options;
        }
        if (!empty($args)) {
            $options[$name] = count($args) > 1 ? $args : $args[0];
        }
        if (array_key_exists($name, $options)) {
            return $options[$name];
        }
    }

    /**
     * Set and returns params
     *
     * Depending on provided arguments:
     *
     *  * Reset params if first argument is null
     *
     *  * If first argument is an array, merge it with current params
     *
     *  * If there is a second argument $value, set param $name (first argument) with $value
     * <code>
     *  params('name', 'Doe') // set 'name' => 'Doe'
     * </code>
     *  * If there is more than 2 arguments, set param $name (first argument) value with
     *    an array of next arguments
     * <code>
     *  params('months', 'jan', 'feb', 'mar') // set 'month' => array('months', 'jan', 'feb', 'mar')
     * </code>
     *
     * @param mixed $name_or_array_or_null could be null || array of params || name of a param (optional)
     * @param mixed $value,... for the $name param (optional)
     * @return mixed all params, or one if a first argument $name is provided
     */
    public static function params($name_or_array_or_null = null, $value = null)
    {
        static $params = [];
        $args = func_get_args();

        if (func_num_args() > 0) {
            $name = array_shift($args);
            if ($name === null) {
                # Reset params
                $params = [];
                return $params;
            }
            if (is_array($name)) {
                $params = array_merge($params, $name);
                return $params;
            }
            $nargs = count($args);
            if ($nargs > 0) {
                $value = $nargs > 1 ? $args : $args[0];
                $params[$name] = $value;
            }
            return array_key_exists($name, $params) ? $params[$name] : null;
        }

        return $params;
    }

    /**
     * Stop and exit limonade application
     *
     * @access private
     * @param boolean exit or not
     * @return void
     */
    public static function stop_and_exit($exit = true)
    {
        Utils::call_if_exists('before_exit');
        $flash_sweep = true;
        $headers = headers_list();
        foreach ($headers as $header) {
            // If a Content-Type header exists, flash_sweep only if is text/html
            // Else if there's no Content-Type header, flash_sweep by default
            if (stripos($header, 'Content-Type:') === 0) {
                $flash_sweep = stripos($header, 'Content-Type: text/html') === 0;
                break;
            }
        }
        if ($flash_sweep) {
            Helpers::flash_sweep();
        }
        if (defined('SID')) {
            session_write_close();
        }
        if (Request::request_is_head()) {
            ob_end_clean();
        }
        if ($exit) {
            exit;
        }
    }
}
