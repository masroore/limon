<?php


namespace Limonade;

class Helpers
{
    /**
     * Returns an url composed of params joined with /
     * A param can be a string or an array.
     * If param is an array, its members will be added at the end of the return url
     * as GET parameters "&key=value".
     *
     * @param string or array $param1, $param2 ...
     * @return string
     */
    public static function url_for($params = null)
    {
        $paths = [];
        $params = func_get_args();
        $GET_params = [];
        foreach ($params as $param) {
            if (is_array($param)) {
                $GET_params = array_merge($GET_params, $param);
                continue;
            }
            if (filter_var($param, FILTER_VALIDATE_URL)) {
                $paths[] = $param;
                continue;
            }
            $p = explode('/', $param);
            foreach ($p as $v) {
                if ($v != "") {
                    $paths[] = str_replace('%23', '#', rawurlencode($v));
                }
            }
        }

        $path = rtrim(implode('/', $paths), '/');

        if (!filter_var($path, FILTER_VALIDATE_URL)) {
            # it's a relative URL or an URL without a schema
            $base_uri = Core::option('base_uri');
            $path = File::path($base_uri, $path);
        }

        if (!empty($GET_params)) {
            $is_first_qs_param = true;
            $path_as_no_question_mark = strpos($path, '?') === false;

            foreach ($GET_params as $k => $v) {
                $qs_separator = $is_first_qs_param && $path_as_no_question_mark ?
                    '?' : '&amp;';
                $path .= $qs_separator . rawurlencode($k) . '=' . rawurlencode($v);
                $is_first_qs_param = false;
            }
        }

        if (DIRECTORY_SEPARATOR !== '/') {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }

        return $path;
    }

    /**
     * An alias of {@link htmlspecialchars()}.
     * If no $charset is provided, uses option('encoding') value
     *
     * @param string $str
     * @param string $quote_style
     * @param string $charset
     * @return string|void
     */
    public static function h($str, $quote_style = ENT_NOQUOTES, $charset = null)
    {
        if ($charset === null) {
            $charset = strtoupper(option('encoding'));
        }
        return htmlspecialchars($str, $quote_style, $charset);
    }

    /**
     * Set and returns flash messages available for the current action, included those
     * defined in the previous action with {@link flash()}
     * Those messages will also be passed to the views and made available in the
     * <code>$flash</code> variable.
     *
     * If multiple values are provided, set <code>$name</code> variable with an array of those values.
     * If there is only one value, set <code>$name</code> variable with the provided $values
     * or if it's <code>$name</code> is an array, merge it with current messages.
     *
     * @param string, array $name
     * @param mixed $values,...
     * @return mixed variable value for $name if $name argument is provided, else return all variables
     */
    public static function flash_now($name = null, $value = null)
    {
        static $messages = null;
        if ($messages === null) {
            $fkey = LIM_SESSION_FLASH_KEY;
            $messages = [];
            if (defined('SID') && array_key_exists($fkey, $_SESSION)) {
                $messages = $_SESSION[$fkey];
            }
        }
        $args = func_get_args();
        $name = array_shift($args);
        if ($name === null) {
            return $messages;
        }
        if (is_array($name)) {
            return $messages = array_merge($messages, $name);
        }
        if (!empty($args)) {
            $messages[$name] = count($args) > 1 ? $args : $args[0];
        }
        if (!array_key_exists($name, $messages)) {
            return null;
        }

        return $messages[$name];
        #return $messages;
    }

    /**
     * Delete current flash messages in session, and set new ones stored with
     * flash function.
     * Called before application exit.
     *
     * @access private
     * @return void
     */
    public static function flash_sweep()
    {
        if (defined('SID')) {
            $fkey = LIM_SESSION_FLASH_KEY;
            $_SESSION[$fkey] = Helpers::flash();
        }
    }

    /**
     * Set and returns flash messages that will be available in the next action
     * via the {@link flash_now()} function or the view variable <code>$flash</code>.
     *
     * If multiple values are provided, set <code>$name</code> variable with an array of those values.
     * If there is only one value, set <code>$name</code> variable with the provided $values
     * or if it's <code>$name</code> is an array, merge it with current messages.
     *
     * @param string, array $name
     * @param mixed $values,...
     * @return mixed variable value for $name if $name argument is provided, else return all variables
     */
    public static function flash($name = null, $value = null)
    {
        if (!defined('SID')) {
            trigger_error("Flash messages can't be used because session isn't enabled", E_USER_WARNING);
        }
        static $messages = [];
        $args = func_get_args();
        $name = array_shift($args);
        if ($name === null) {
            return $messages;
        }
        if (is_array($name)) {
            return $messages = array_merge($messages, $name);
        }
        if (!empty($args)) {
            $messages[$name] = count($args) > 1 ? $args : $args[0];
        }
        if (!array_key_exists($name, $messages)) {
            return null;
        }

        return $messages[$name];
        #return $messages;
    }

    /**
     * Stops capturing block of text
     *
     * @return void
     */
    public static function end_content_for()
    {
        Helpers::content_for();
    }

    /**
     * Starts capturing block of text
     *
     * Calling without params stops capturing (same as end_content_for()).
     * After capturing the captured block is put into a variable
     * named $name for later use in layouts. If second parameter
     * is supplied, its content will be used instead of capturing
     * a block of text.
     *
     * @param string $name
     * @param string $content
     * @return void
     */
    public static function content_for($name = null, $content = null)
    {
        static $_name = null;
        if ($name === null && $_name !== null) {
            Core::set($_name, ob_get_clean());
            $_name = null;
        } elseif ($name !== null && !isset($content)) {
            $_name = $name;
            ob_start();
        } elseif (isset($name, $content)) {
            Core::set($name, $content);
        }
    }

    /**
     * Shows current memory and execution time of the application.
     *
     * @access public
     * @return array
     */
    public static function benchmark()
    {
        $current_mem_usage = memory_get_usage();
        $execution_time = microtime() - LIM_START_MICROTIME;

        return [
            'current_memory' => $current_mem_usage,
            'start_memory' => LIM_START_MEMORY,
            'average_memory' => (LIM_START_MEMORY + $current_mem_usage) / 2,
            'execution_time' => $execution_time
        ];
    }
}
