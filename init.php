<?php

## CONSTANTS __________________________________________________________________
/**
 * Limonade version
 */
define('LIMONADE', '0.5.0');
define('LIM_NAME', 'limonade');
define('LIM_START_MICROTIME', (float)substr(microtime(), 0, 10));
define('LIM_SESSION_NAME', LIM_NAME);
define('LIM_SESSION_FLASH_KEY', '_lim_flash_messages');
define('LIM_START_MEMORY', memory_get_usage());
define('E_LIM_HTTP', 32768);
define('E_LIM_PHP', 65536);
define('E_LIM_DEPRECATED', 35000);
define('NOT_FOUND', 404);
define('SERVER_ERROR', 500);
define('ENV_PRODUCTION', 10);
define('ENV_DEVELOPMENT', 100);
define('X-SENDFILE', 10);
define('X-LIGHTTPD-SEND-FILE', 20);

include "system\Core.php";
include "system\Error.php";
include "system\File.php";
include "system\Helpers.php";
include "system\Http.php";
include "system\Request.php";
include "system\Router.php";
include "system\Utils.php";
include "system\View.php";

use Limonade\Core;
use Limonade\File;
use Limonade\Router;
use Limonade\View;

# C. Disable error display
#    by default, no error reporting; it will be switched on later in run().
#    ini_set('display_errors', 1); must be called explicitly in app file
#    if you want to show errors before running app
ini_set('display_errors', 0);

## SETTING INTERNAL ROUTES _____________________________________________________

/**
 * Internal controller that responds to route /_lim_css/*.css
 *
 * @access private
 * @return string
 */
function render_limonade_css()
{
    Core::option('views_dir', File::path(Core::option('limonade_public_dir'), 'css'));
    $fpath = File::path(Core::params('_lim_css_filename') . '.css');
    return View::css($fpath, null); // with no layout
}

/**
 * Internal controller that responds to route /_lim_public/**
 *
 * @access private
 * @return void
 */
function render_limonade_file()
{
    $fpath = File::path(Core::option('limonade_public_dir'), Core::params('_lim_public_file'));
    return View::render_file($fpath, true);
}

Router::dispatch(['/_lim_css/*.css', ['_lim_css_filename']], 'render_limonade_css');
Router::dispatch(['/_lim_public/**', ['_lim_public_file']], 'render_limonade_file');

function dd()
{
    var_dump(func_get_args());
    die(1);
}
