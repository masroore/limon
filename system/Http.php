<?php


namespace Limonade;

### Constants: HTTP status codes

define('HTTP_CONTINUE', 100);
define('HTTP_SWITCHING_PROTOCOLS', 101);
define('HTTP_PROCESSING', 102);
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_ACCEPTED', 202);
define('HTTP_NON_AUTHORITATIVE', 203);
define('HTTP_NO_CONTENT', 204);
define('HTTP_RESET_CONTENT', 205);
define('HTTP_PARTIAL_CONTENT', 206);
define('HTTP_MULTI_STATUS', 207);

define('HTTP_MULTIPLE_CHOICES', 300);
define('HTTP_MOVED_PERMANENTLY', 301);
define('HTTP_MOVED_TEMPORARILY', 302);
define('HTTP_SEE_OTHER', 303);
define('HTTP_NOT_MODIFIED', 304);
define('HTTP_USE_PROXY', 305);
define('HTTP_TEMPORARY_REDIRECT', 307);

define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_PAYMENT_REQUIRED', 402);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_NOT_ACCEPTABLE', 406);
define('HTTP_PROXY_AUTHENTICATION_REQUIRED', 407);
define('HTTP_REQUEST_TIME_OUT', 408);
define('HTTP_CONFLICT', 409);
define('HTTP_GONE', 410);
define('HTTP_LENGTH_REQUIRED', 411);
define('HTTP_PRECONDITION_FAILED', 412);
define('HTTP_REQUEST_ENTITY_TOO_LARGE', 413);
define('HTTP_REQUEST_URI_TOO_LARGE', 414);
define('HTTP_UNSUPPORTED_MEDIA_TYPE', 415);
define('HTTP_RANGE_NOT_SATISFIABLE', 416);
define('HTTP_EXPECTATION_FAILED', 417);
define('HTTP_UNPROCESSABLE_ENTITY', 422);
define('HTTP_LOCKED', 423);
define('HTTP_FAILED_DEPENDENCY', 424);
define('HTTP_UPGRADE_REQUIRED', 426);

define('HTTP_INTERNAL_SERVER_ERROR', 500);
define('HTTP_NOT_IMPLEMENTED', 501);
define('HTTP_BAD_GATEWAY', 502);
define('HTTP_SERVICE_UNAVAILABLE', 503);
define('HTTP_GATEWAY_TIME_OUT', 504);
define('HTTP_VERSION_NOT_SUPPORTED', 505);
define('HTTP_VARIANT_ALSO_VARIES', 506);
define('HTTP_INSUFFICIENT_STORAGE', 507);
define('HTTP_NOT_EXTENDED', 510);

define('HTTP_METHOD_GET', 'GET');
define('HTTP_METHOD_HEAD', 'HEAD');
define('HTTP_METHOD_POST', 'POST');
define('HTTP_METHOD_DELETE', 'DELETE');
define('HTTP_METHOD_PUT', 'PUT');


class Http
{
    /**
     * Output proper HTTP header for a given HTTP code
     *
     * @param string $code
     * @return void
     */
    public static function status($code = 500)
    {
        if (!headers_sent()) {
            $str = Http::response_status_code($code);
            header($str);
        }
    }

    /**
     * Returns an HTTP response status string for a given code
     *
     * @param string $num
     * @return string
     */
    public static function response_status_code($num)
    {
        if ($str = Http::response_status($num)) {
            return "HTTP/1.1 $num $str";
        }
    }

    /**
     * Returns HTTP response status for a given code.
     * If no code provided, return an array of all status
     *
     * @param string $num
     * @return array|string
     */
    public static function response_status($num = null)
    {
        $status = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',

            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            226 => 'IM Used',

            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => 'Reserved',
            307 => 'Temporary Redirect',

            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            426 => 'Upgrade Required',

            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            510 => 'Not Extended'
        ];
        if ($num === null) {
            return $status;
        }
        return array_key_exists($num, $status) ? $status[$num] : '';
    }

    /**
     * Http redirection
     *
     * Same use as {@link url_for()}
     * By default HTTP status code is 302, but a different code can be specified
     * with a status key in array parameter.
     *
     * <code>
     * redirecto('new','url'); # 302 HTTP_MOVED_TEMPORARILY by default
     * redirecto('new','url', array('status' => HTTP_MOVED_PERMANENTLY));
     * </code>
     *
     * @param string or array $param1, $param2...
     * @return void
     */
    public static function redirect($params)
    {
        # [NOTE]: (from php.net) HTTP/1.1 requires an absolute URI as argument to » Location:
        # including the scheme, hostname and absolute path, but some clients accept
        # relative URIs. You can usually use $_SERVER['HTTP_HOST'],
        # $_SERVER['PHP_SELF'] and dirname() to make an absolute URI from a relative
        # one yourself.

        # TODO make absolute uri
        if (!headers_sent()) {
            $status = HTTP_MOVED_TEMPORARILY; # default for a redirection in PHP
            $params = func_get_args();
            $n_params = [];
            # extract status param if exists
            foreach ($params as $param) {
                if (is_array($param)) {
                    if (array_key_exists('status', $param)) {
                        $status = $param['status'];
                        unset($param['status']);
                    }
                }
                $n_params[] = $param;
            }
            $uri = Helpers::url_for(...$n_params);
            Core::stop_and_exit(false);
            header('Location: ' . $uri, true, $status);
            exit;
        }
    }

    /**
     * Checks if an HTTP response code is valid
     *
     * @param string $num
     * @return bool
     */
    public static function response_status_is_valid($num)
    {
        $r = Http::response_status($num);
        return !empty($r);
    }

    /**
     * Check if the _Accept_ header is present, and includes the given `type`.
     *
     * When the _Accept_ header is not present `true` is returned. Otherwise
     * the given `type` is matched by an exact match, and then subtypes. You
     * may pass the subtype such as "html" which is then converted internally
     * to "text/html" using the mime lookup table.
     *
     * @param string $type
     * @param array $env
     * @return bool
     */
    public static function ua_accepts($type, $env = null)
    {
        if ($env === null) {
            $env = Core::env();
        }
        $accept = array_key_exists('HTTP_ACCEPT', $env['SERVER']) ? $env['SERVER']['HTTP_ACCEPT'] : null;

        if (!$accept || $accept === '*/*') {
            return true;
        }

        if ($type) {
            // Allow "html" vs "text/html" etc
            if (!strpos($type, '/')) {
                $type = File::mime_type($type);
            }

            // Check if we have a direct match
            if (strpos($accept, $type) > -1) {
                return true;
            }

            // Check if we have type/*
            $type_parts = explode('/', $type);
            $type = $type_parts[0] . '/*';
            return (strpos($accept, $type) > -1);
        }

        return false;
    }
}
