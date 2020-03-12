<?php


namespace Limonade;

class Utils
{
    /**
     * Calls a function if exists
     *
     * @param callback $func a function stored in a string variable,
     *   or an object and the name of a method within the object
     *   See {@link http://php.net/manual/en/language.pseudo-types.php#language.types.callback php documentation}
     *   to learn more about callbacks.
     * @param mixed $arg ,.. (optional)
     * @return mixed
     */
    public static function call_if_exists($func)
    {
        $args = func_get_args();
        $func = array_shift($args);
        if (is_callable($func)) {
            return call_user_func_array($func, $args);
        }
    }

    /**
     * Define a constant unless it already exists
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public static function define_unless_exists($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * An alias of {@link value_or_default()}
     *
     *
     * @param mixed $value
     * @param mixed $default
     * @return mixed
     */
    public static function v($value, $default)
    {
        return Utils::value_or_default($value, $default);
    }

    /**
     * Return a default value if provided value is empty
     *
     * @param mixed $value
     * @param mixed $default default value returned if $value is empty
     * @return mixed
     */
    public static function value_or_default($value, $default)
    {
        return empty($value) ? $default : $value;
    }

    /**
     * Load php files with require_once in a given dir
     *
     * @param string $path Path in which are the file to load
     * @param string $pattern a regexp pattern that filter files to load
     * @param bool $prevents_output security option that prevents output
     * @return array paths of loaded files
     */
    public static function require_once_dir($path, $pattern = '*.php', $prevents_output = true)
    {
        if ($path[strlen($path) - 1] !== '/') {
            $path .= '/';
        }
        $filenames = glob($path . $pattern);
        if (!is_array($filenames)) {
            $filenames = [];
        }
        if ($prevents_output) {
            ob_start();
        }
        foreach ($filenames as $filename) {
            require_once $filename;
        }
        if ($prevents_output) {
            ob_end_clean();
        }
        return $filenames;
    }
}
