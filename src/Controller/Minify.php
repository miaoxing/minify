<?php

namespace Miaoxing\Minify\Controller;

use FirePHP;
use Minify as Min;
use Minify_Controller_MinApp;
use Minify_DebugDetector;
use Minify_Loader;
use Minify_Logger;
use Wei\Response;

class Minify extends \Wei\BaseController
{
    public function indexAction($req, Response $res)
    {
        if (!$req['f']) {
            return $res->setContent('Forbidden')->setStatusCode(403);
        }

        /**
         * Configuration for "min", the default application built with the Minify
         * library
         *
         * @package Min
         */


        /**
         * Allow use of the Minify URI Builder app. Only set this to true while you need it.
         */
        $min_enableBuilder = false;

        /**
         * If non-empty, the Builder will be protected with HTTP Digest auth.
         * The username is "admin".
         */
        $min_builderPassword = 'admin';


        /**
         * Set to true to log messages to FirePHP (Firefox Firebug addon).
         * Set to false for no error logging (Minify may be slightly faster).
         * @link http://www.firephp.org/
         *
         * If you want to use a custom error logger, set this to your logger
         * instance. Your object should have a method log(string $message).
         */
        $min_errorLogger = true;


        /**
         * To allow debug mode output, you must set this option to true.
         *
         * Once true, you can send the cookie minDebug to request debug mode output. The
         * cookie value should match the URIs you'd like to debug. E.g. to debug
         * /min/f=file1.js send the cookie minDebug=file1.js
         * You can manually enable debugging by appending "&debug" to a URI.
         * E.g. /min/?f=script1.js,script2.js&debug
         *
         * In 'debug' mode, Minify combines files with no minification and adds comments
         * to indicate line #s of the original files.
         */
        $min_allowDebugFlag = false;


        /**
         * For best performance, specify your temp directory here. Otherwise Minify
         * will have to load extra code to guess. Some examples below:
         */
        //$min_cachePath = 'c:\\WINDOWS\\Temp';
        //$min_cachePath = '/tmp';
        //$min_cachePath = preg_replace('/^\\d+;/', '', session_save_path());
        /**
         * To use APC/Memcache/ZendPlatform for cache storage, require the class and
         * set $min_cachePath to an instance. Example below:
         */
        //require dirname(__FILE__) . '/lib/Minify/Cache/APC.php';
        //$min_cachePath = new Minify_Cache_APC();

        // 没有memcache则留空,自动使用文件缓存
        if (class_exists('Memcache')) {
            $min_cachePath = new \Minify_Cache_Memcache(wei()->memcache->getObject());
        }

        /**
         * Leave an empty string to use PHP's $_SERVER['DOCUMENT_ROOT'].
         *
         * On some servers, this value may be misconfigured or missing. If so, set this
         * to your full document root path with no trailing slash.
         * E.g. '/home/accountname/public_html' or 'c:\\xampp\\htdocs'
         *
         * If /min/ is directly inside your document root, just uncomment the
         * second line. The third line might work on some Apache servers.
         */
        $min_documentRoot = '';
        //$min_documentRoot = substr(__FILE__, 0, -15);
        //$min_documentRoot = $_SERVER['SUBDOMAIN_DOCUMENT_ROOT'];


        /**
         * Cache file locking. Set to false if filesystem is NFS. On at least one
         * NFS system flock-ing attempts stalled PHP for 30 seconds!
         */
        $min_cacheFileLocking = true;


        /**
         * Combining multiple CSS files can place @import declarations after rules, which
         * is invalid. Minify will attempt to detect when this happens and place a
         * warning comment at the top of the CSS output. To resolve this you can either
         * move the @imports within your CSS files, or enable this option, which will
         * move all @imports to the top of the output. Note that moving @imports could
         * affect CSS values (which is why this option is disabled by default).
         */
        $min_serveOptions['bubbleCssImports'] = false;


        /**
         * Cache-Control: max-age value sent to browser (in seconds). After this period,
         * the browser will send another conditional GET. Use a longer period for lower
         * traffic but you may want to shorten this before making changes if it's crucial
         * those changes are seen immediately.
         *
         * Note: Despite this setting, if you include a number at the end of the
         * querystring, maxAge will be set to one year. E.g. /min/f=hello.css&123456
         */
        $min_serveOptions['maxAge'] = 1800;


        /**
         * To use Google's Closure Compiler API to minify Javascript (falling back to JSMin
         * on failure), uncomment the following line:
         */
        //$min_serveOptions['minifiers']['application/x-javascript'] = array('Minify_JS_ClosureCompiler', 'minify');


        /**
         * If you'd like to restrict the "f" option to files within/below
         * particular directories below DOCUMENT_ROOT, set this here.
         * You will still need to include the directory in the
         * f or b GET parameters.
         *
         * // = shortcut for DOCUMENT_ROOT
         */
        //$min_serveOptions['minApp']['allowDirs'] = array('//js', '//css');

        /**
         * Set to true to disable the "f" GET parameter for specifying files.
         * Only the "g" parameter will be considered.
         */
        $min_serveOptions['minApp']['groupsOnly'] = false;


        /**
         * By default, Minify will not minify files with names containing .min or -min
         * before the extension. E.g. myFile.min.js will not be processed by JSMin
         *
         * To minify all files, set this option to null. You could also specify your
         * own pattern that is matched against the filename.
         */
        //$min_serveOptions['minApp']['noMinPattern'] = '@[-\\.]min\\.(?:js|css)$@i';


        /**
         * If you minify CSS files stored in symlink-ed directories, the URI rewriting
         * algorithm can fail. To prevent this, provide an array of link paths to
         * target paths, where the link paths are within the document root.
         *
         * Because paths need to be normalized for this to work, use "//" to substitute
         * the doc root in the link paths (the array keys). E.g.:
         * <code>
         * array('//symlink' => '/real/target/path') // unix
         * array('//static' => 'D:\\staticStorage')  // Windows
         * </code>
         */
        $min_symlinks = array();


        /**
         * If you upload files from Windows to a non-Windows server, Windows may report
         * incorrect mtimes for the files. This may cause Minify to keep serving stale
         * cache files when source file changes are made too frequently (e.g. more than
         * once an hour).
         *
         * Immediately after modifying and uploading a file, use the touch command to
         * update the mtime on the server. If the mtime jumps ahead by a number of hours,
         * set this variable to that number. If the mtime moves back, this should not be
         * needed.
         *
         * In the Windows SFTP client WinSCP, there's an option that may fix this
         * issue without changing the variable below. Under login > environment,
         * select the option "Adjust remote timestamp with DST".
         * @link http://winscp.net/eng/docs/ui_login_environment#daylight_saving_time
         */
        $min_uploaderHoursBehind = 0;


        /**
         * Path to Minify's lib folder. If you happen to move it, change
         * this accordingly.
         */
        $min_libPath = realpath('vendor/mrclay/minify/min') . '/lib';

        // try to disable output_compression (may not have an effect)
        ini_set('zlib.output_compression', '0');

        define('MINIFY_MIN_DIR', realpath('vendor/mrclay/minify/min'));

        // load config
        require MINIFY_MIN_DIR . '/config.php';

        if (isset($_GET['test'])) {
            include MINIFY_MIN_DIR . '/config-test.php';
        }

        require "$min_libPath/Minify/Loader.php";
        Minify_Loader::register();

        Min::$uploaderHoursBehind = $min_uploaderHoursBehind;
        Min::setCache(
            isset($min_cachePath) ? $min_cachePath : ''
            ,$min_cacheFileLocking
        );

        if ($min_documentRoot) {
            $_SERVER['DOCUMENT_ROOT'] = $min_documentRoot;
            Minify::$isDocRootSet = true;
        }

        $min_serveOptions['minifierOptions']['text/css']['symlinks'] = $min_symlinks;
        // auto-add targets to allowDirs
        foreach ($min_symlinks as $uri => $target) {
            $min_serveOptions['minApp']['allowDirs'][] = $target;
        }

        if ($min_allowDebugFlag) {
            $min_serveOptions['debug'] = Minify_DebugDetector::shouldDebugRequest($_COOKIE, $_GET, $_SERVER['REQUEST_URI']);
        }

        if ($min_errorLogger) {
            if (true === $min_errorLogger) {
                $min_errorLogger = FirePHP::getInstance(true);
            }
            Minify_Logger::setLogger($min_errorLogger);
        }

        // check for URI versioning
        if (preg_match('/&\\d/', $_SERVER['QUERY_STRING'])) {
            $min_serveOptions['maxAge'] = 31536000;
        }
        if (isset($_GET['g'])) {
            // well need groups config
            $min_serveOptions['minApp']['groups'] = (require MINIFY_MIN_DIR . '/groupsConfig.php');
        }
        if (isset($_GET['f']) || isset($_GET['g'])) {
            // serve!
            if (! isset($min_serveController)) {
                $min_serveController = new Minify_Controller_MinApp();
            }
            Min::serve($min_serveController, $min_serveOptions);

        }
        //require 'vendor/mrclay/minify/min/index.php';
        $this->app->preventPreviousDispatch();
    }
}
