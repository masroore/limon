<?php


namespace Limonade;

class Request
{
    /**
     * Checks if a request method or current one is allowed
     *
     * @param string $m
     * @return bool
     */
    public static function request_method_is_allowed($m = null)
    {
        if ($m === null) {
            $m = static::request_method();
        }
        return in_array(strtoupper($m), Request::request_methods(), true);
    }

    /**
     * Returns current request method for a given environment or current one
     *
     * @param array $env
     * @return string
     */
    public static function request_method($env = null)
    {
        if ($env === null) {
            $env = Core::env();
        }
        $m = array_key_exists('REQUEST_METHOD', $env['SERVER']) ? $env['SERVER']['REQUEST_METHOD'] : null;
        if ($m === HTTP_METHOD_POST && array_key_exists('_method', $env['POST'])) {
            $m = strtoupper($env['POST']['_method']);
        }
        if (!in_array(strtoupper($m), Request::request_methods(), true)) {
            trigger_error("'$m' request method is unknown or unavailable.", E_USER_WARNING);
            $m = false;
        }
        return $m;
    }

    /**
     * Returns allowed request methods
     *
     * @return array
     */
    public static function request_methods()
    {
        return [HTTP_METHOD_GET, HTTP_METHOD_POST, HTTP_METHOD_PUT, HTTP_METHOD_DELETE, HTTP_METHOD_HEAD];
    }

    /**
     * Checks if request method is GET
     *
     * @param string $env
     * @return bool
     */
    public static function request_is_get($env = null)
    {
        return static::request_method($env) === HTTP_METHOD_GET;
    }

    /**
     * Checks if request method is POST
     *
     * @param string $env
     * @return bool
     */
    public static function request_is_post($env = null)
    {
        return static::request_method($env) === HTTP_METHOD_POST;
    }

    /**
     * Checks if request method is PUT
     *
     * @param string $env
     * @return bool
     */
    public static function request_is_put($env = null)
    {
        return static::request_method($env) === HTTP_METHOD_PUT;
    }

    /**
     * Checks if request method is DELETE
     *
     * @param string $env
     * @return bool
     */
    public static function request_is_delete($env = null)
    {
        return static::request_method($env) === HTTP_METHOD_DELETE;
    }

    /**
     * Checks if request method is HEAD
     *
     * @param string $env
     * @return bool
     */
    public static function request_is_head($env = null)
    {
        return static::request_method($env) === HTTP_METHOD_HEAD;
    }

    /**
     * Returns current request uri (the path that will be compared with routes)
     *
     * (Inspired from codeigniter URI::_fetch_uri_string method)
     *
     * @param array $env
     * @return string
     */
    public static function request_uri($env = null)
    {
        static $uri = null;
        if ($env === null) {
            if ($uri !== null) {
                return $uri;
            }
            $env = Core::env();
        }

        if (array_key_exists('uri', $env['GET'])) {
            $uri = $env['GET']['uri'];
        } elseif (array_key_exists('u', $env['GET'])) {
            $uri = $env['GET']['u'];
        }
        // bug: dot are converted to _... so we can't use it...
        // else if (count($env['GET']) == 1 && trim(key($env['GET']), '/') != '')
        // {
        //  $uri = key($env['GET']);
        // }
        else {
            $app_file = Core::app_file();
            $path_info = isset($env['SERVER']['PATH_INFO']) ? $env['SERVER']['PATH_INFO'] : @getenv('PATH_INFO');
            $query_string = isset($env['SERVER']['QUERY_STRING']) ? $env['SERVER']['QUERY_STRING'] : @getenv('QUERY_STRING');

            // Is there a PATH_INFO variable?
            // Note: some servers seem to have trouble with getenv() so we'll test it two ways
            if (trim($path_info, '/') != '' && $path_info != "/" . $app_file) {
                if (strpos($path_info, '&') !== 0) {
                    # exclude GET params
                    $params = explode('&', $path_info);
                    $path_info = array_shift($params);
                    # populate $_GET
                    foreach ($params as $param) {
                        if (strpos($param, '=') > 0) {
                            list($k, $v) = explode('=', $param);
                            $env['GET'][$k] = $v;
                        }
                    }
                }
                $uri = $path_info;
            } // No PATH_INFO?... What about QUERY_STRING?
            elseif (trim($query_string, '/') != '') {
                $uri = $query_string;
                $get = $env['GET'];
                if (count($get) > 0) {
                    # exclude GET params
                    $keys = array_keys($get);
                    $first = array_shift($keys);
                    if (strpos($query_string, $first) === 0) {
                        $uri = $first;
                    }
                }
            } elseif (array_key_exists('REQUEST_URI', $env['SERVER']) && !empty($env['SERVER']['REQUEST_URI'])) {
                $request_uri = rtrim(rawurldecode($env['SERVER']['REQUEST_URI']), '?/') . '/';
                $base_path = $env['SERVER']['SCRIPT_NAME'];

                if ($request_uri . 'index.php' === $base_path) {
                    $request_uri .= 'index.php';
                }
                $uri = str_replace($base_path, '', $request_uri);
            } elseif ($env['SERVER']['argc'] > 1 && trim($env['SERVER']['argv'][1], '/') != '') {
                $uri = $env['SERVER']['argv'][1];
            }
        }

        $uri = rtrim($uri, '/'); # removes ending /
        if (empty($uri)) {
            $uri = '/';
        } elseif ($uri[0] !== '/') {
            $uri = '/' . $uri; # add a leading slash
        }
        return rawurldecode($uri);
    }
}
