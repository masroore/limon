<?php


namespace Limonade;

class Router
{
    /**
     * An alias of {@link dispatch_get()}
     *
     * @param $path_or_array
     * @param $function
     * @param array $options
     * @return void
     */
    public static function dispatch($path_or_array, $function, $options = [])
    {
        static::dispatch_get($path_or_array, $function, $options);
    }

    /**
     * Add a GET route. Also automatically defines a HEAD route.
     *
     * @param string $path_or_array
     * @param string $function
     * @param array $options (optional). See {@link Router::route()} for available options.
     * @return void
     */
    public static function dispatch_get($path_or_array, $function, $options = [])
    {
        Router::route(HTTP_METHOD_GET, $path_or_array, $function, $options);
        Router::route(HTTP_METHOD_HEAD, $path_or_array, $function, $options);
    }

    /**
     * Add route if required params are provided.
     * Delete all routes if null is passed as a unique argument
     * Return all routes
     *
     * @param string $method
     * @param string|array $path_or_array
     * @param callback $func
     * @param array $options (optional)
     * @return array
     * @see static::route_build()
     * @access private
     */
    public static function route()
    {
        static $routes = [];
        $nargs = func_num_args();
        if ($nargs > 0) {
            $args = func_get_args();
            if ($nargs === 1 && $args[0] === null) {
                $routes = [];
            } elseif ($nargs < 3) {
                trigger_error('Missing arguments for Router::route()', E_USER_ERROR);
            } else {
                $method = $args[0];
                $path_or_array = $args[1];
                $func = $args[2];
                $options = $nargs > 3 ? $args[3] : [];

                $routes[] = Router::build($method, $path_or_array, $func, $options);
            }
        }
        return $routes;
    }

    /**
     * Build a route and return it
     *
     * @access private
     * @param string $method allowed http method (one of those returned by {@link request_methods()})
     * @param string|array $path_or_array
     * @param callback $func callback function called when route is found. It can be
     *   a function, an object method, a static method or a closure.
     *   See {@link http://php.net/manual/en/language.pseudo-types.php#language.types.callback php documentation}
     *   to learn more about callbacks.
     * @param array $options (optional). Available options:
     *   - 'params' key with an array of parameters: for parametrized routes.
     *     those parameters will be merged with routes parameters.
     * @return array array with keys "method", "pattern", "names", "function", "options"
     */
    private static function build($method, $path_or_array, $func, $options = [])
    {
        $method = strtoupper($method);
        if (!in_array($method, Request::request_methods())) {
            trigger_error("'$method' request method is unkown or unavailable.", E_USER_WARNING);
        }

        if (is_array($path_or_array)) {
            $path = array_shift($path_or_array);
            $names = $path_or_array[0];
        } else {
            $path = $path_or_array;
            $names = [];
        }

        $single_asterisk_subpattern = "(?:/([^\/]*))?";
        $double_asterisk_subpattern = '(?:/(.*))?';
        $optionnal_slash_subpattern = '(?:/*?)';
        $no_slash_asterisk_subpattern = "(?:([^\/]*))?";

        if (strpos($path, '^') === 0) {
            if ($path{strlen($path) - 1} !== '$') {
                $path .= '$';
            }
            $pattern = '#' . $path . '#i';
        } elseif (empty($path) || $path === '/') {
            $pattern = sprintf('#^%s$#', $optionnal_slash_subpattern);
        } else {
            $parsed = [];
            $elts = explode('/', $path);

            $parameters_count = 0;

            foreach ($elts as $elt) {
                if (empty($elt)) {
                    continue;
                }

                $name = null;

                # extracting double asterisk **
                if ($elt === '**'):
                    $parsed[] = $double_asterisk_subpattern;
                $name = $parameters_count;

                # extracting single asterisk *
                elseif ($elt === '*'):
                    $parsed[] = $single_asterisk_subpattern;
                $name = $parameters_count;

                # extracting named parameters :my_param
                elseif ($elt[0] === ':'):
                    if (preg_match('/^:([^\:]+)$/', $elt, $matches)) {
                        $parsed[] = $single_asterisk_subpattern;
                        $name = $matches[1];
                    }; elseif (strpos($elt, '*') !== false):
                    $sub_elts = explode('*', $elt);
                $parsed_sub = [];
                foreach ($sub_elts as $sub_elt) {
                    $parsed_sub[] = preg_quote($sub_elt, "#");
                    $name = $parameters_count;
                }
                //
                $parsed[] = '/' . implode($no_slash_asterisk_subpattern, $parsed_sub); else:
                    $parsed[] = '/' . preg_quote($elt, '#');

                endif;

                /* set parameters names */
                if ($name === null) {
                    continue;
                }
                if (!array_key_exists($parameters_count, $names) || $names[$parameters_count] === null) {
                    $names[$parameters_count] = $name;
                }
                $parameters_count++;
            }

            $pattern = sprintf('#^%s%s?$#i', implode('', $parsed), $optionnal_slash_subpattern);
        }

        return ['method' => $method,
            'pattern' => $pattern,
            'names' => $names,
            'function' => $func,
            'options' => $options];
    }

    /**
     * Add a POST route
     *
     * @param string $path_or_array
     * @param string $function
     * @param array $options (optional). See {@link Router::route()} for available options.
     * @return void
     */
    public static function dispatch_post($path_or_array, $function, $options = [])
    {
        Router::route(HTTP_METHOD_POST, $path_or_array, $function, $options);
    }

    /**
     * Add a PUT route
     *
     * @param string $path_or_array
     * @param string $function
     * @param array $options (optional). See {@link Router::route()} for available options.
     * @return void
     */
    public static function dispatch_put($path_or_array, $function, $options = [])
    {
        Router::route(HTTP_METHOD_PUT, $path_or_array, $function, $options);
    }

    /**
     * Add a DELETE route
     *
     * @param string $path_or_array
     * @param string $function
     * @param array $options (optional). See {@link Router::route()} for available options.
     * @return void
     */
    public static function dispatch_delete($path_or_array, $function, $options = [])
    {
        Router::route(HTTP_METHOD_DELETE, $path_or_array, $function, $options);
    }

    /**
     * An alias of Router::route(null): reset all routes
     *
     * @access private
     * @return void
     */
    public static function reset()
    {
        Router::route(null);
    }

    /**
     * Find a route and returns it.
     * If not found, returns false.
     * Routes are checked from first added to last added.
     *
     * @access private
     * @param string $method
     * @param string $path
     * @return array|bool
     *  {@link route_build()} ("method", "pattern", "names", "function", "options")
     *  + the processed "params" key
     */
    public static function find($method, $path)
    {
        $routes = Router::route();
        $method = strtoupper($method);
        foreach ($routes as $route) {
            if ($method === $route['method'] && preg_match($route['pattern'], $path, $matches)) {
                $options = $route['options'];
                $params = array_key_exists('params', $options) ? $options['params'] : [];
                if (count($matches) > 1) {
                    array_shift($matches);
                    $n_matches = count($matches);
                    $names = array_values($route['names']);
                    $n_names = count($names);
                    if ($n_matches < $n_names) {
                        $a = array_fill(0, $n_names - $n_matches, null);
                        $matches = array_merge($matches, $a);
                    } elseif ($n_matches > $n_names) {
                        $names = range($n_names, $n_matches - 1);
                    }
                    $params = array_replace($params, array_combine($names, $matches));
                }
                $route['params'] = $params;
                return $route;
            }
        }
        return false;
    }
}
