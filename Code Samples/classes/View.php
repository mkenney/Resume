<?php
/**
 * Class file for the Bdlm_View class
 *
 * @copyright 2010 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage View
 * @version $Id$
 */

/**
 * Generic View class
 * This class contains generic functions for managing page rendering (views).
 * It must be extended to implement the View interface.
 *
 * @copyright 2010 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage View
 * @version 0.04b
 */
abstract class Bdlm_View implements Bdlm_View_Interface {

	/**
	 * @var string $_charset
	 */
	private static $_charset = 'UTF-8';
	/**
	 * Store error messages for later display
	 * @var array $_errors
	 */
	private static $_errors = array();
	/**
	 * Store the OWASP ESAPI instance
	 * @var Bdlm_Esapi $_esapi
	 */
	private static $_esapi = null;
	/**
	 * Store additional footer elements
	 * @var array $_html_foot_elements
	 */
	private static $_html_foot_elements = array();
	/**
	 * Store additional header elements
	 * @var array $_html_head_elements
	 */
	private static $_html_head_elements = array();
	/**
	 * Store user messages for later display
	 * @var array $_messages
	 */
	private static $_messages = array();
	/**
	 * Store the main page content
	 * @var string $_page_content
	 */
	private static $_page_content = null;
	/**
	 * Web page HTML template
	 * @var string $page_template
	 */
	private static $_page_template = 'default';
	/**
	 * Web page <title> element
	 * @var string $page_title
	 */
	private static $_page_title = 'Example Application';
	/**
	 * Is the page rendering right now?
	 * @var boolean
	 */
	private static $_rendering = false;

	/**
	 * This extends Bdlm_Object so the constructor can't be private
	 * @return void
	 */
	final public function __construct() {throw new Bdlm_Exception('This class is a singleton');}

	/**
	 * Add error messages to a view
	 * Display is determined by the active module
	 */
	static public function errors($error = null) {
		if (!is_null($error)) {
			self::$_errors[] = (string) $error;
		}
		return self::$_errors;
	}

	/**
	 * Set/get the local Bdlm_Esapi instance
	 * Bdlm_Esapi implements and extends the Owasp ESAPI - https://www.owasp.org/
	 * @param Bdlm_Esapi $esapi
	 * @return Bdlm_Esapi
	 */
	static public function esapi(Bdlm_Esapi $esapi = null) {
		if (!is_null($esapi)) {
			self::$_esapi = $esapi;
		}
		if (!self::$_esapi instanceof Bdlm_Esapi) {
			self::$_esapi = new Bdlm_Esapi();
		}
		return self::$_esapi;
	}

	/**
	 * Get/set the charset that should be used on this page
	 * Unless otherwise specified, this charset is applied to CSS and JS render
	 * calls.
	 * @param string $charset the character set to use, default is UTF-8
	 * @return string
	 */
	static public function charset($charset = null) {
		if (!is_null($charset)) {
			self::$_charset = (string) $charset;
		}
		return self::$_charset;
	}

	/**
	 * Get/set the content for the page.
	 *
	 * The argument is provided just for consistency.  You wouldn't really do that,
	 * you would just echo whatever you wanted in the template.  It does cache
	 * the passed content, if any.
	 *
	 * @param string $content Set the content portion of the UI
	 * @param boolean $print_outputProvided for API consistency
	 * @return string
	 */
	static public function content($content = null, $print_output = false) {
		self::loadContent($content);
		if (true === $print_output) {
			echo self::$_page_content;
		}
		return self::$_page_content;
	}

	/**
	 * Add elements custom elements to the page footer (<script> tags, etc.)
	 *
	 * This should be the last View call in the template and should be inside the
	 * body tag.  Best used for external JS files that shouldn't hold up page
	 * rendering.
	 *
	 * @param string $element HTML to add to the <head> section of the HTML
	 * @return string HTML to add to the <head> section of the HTML
	 */
	static public function footElements($element = null, $print_output = false) {
		if (!is_null($element)) {
			self::$_html_foot_elements[(string) $element] = (string) $element; // make it the key also to avoid duplication
		}
		$output = implode("\n", self::$_html_foot_elements);
		if (true === $print_output) {
			echo $output;
		}
		return $output;
	}

	/**
	 * Add elements custom elements to the page header (<script> tags, etc.);
	 *
	 * This should be the last View call in the head tag.  Use to add head tags
	 * (Javascript, CSS, etc.)
	 *
	 * @param string $element HTML to add to the <head> section of the HTML
	 * @return string HTML to add to the <head> section of the HTML
	 */
	static public function headElements($elements = null, $print_output = false) {
		if (!is_null($elements)) {
			if (!is_array($elements)) {
				self::$_html_head_elements[(string) $elements] = (string) $elements; // make it the key also to avoid duplication
			} else {
				foreach ($elements as $k => $v) {
					self::$_html_head_elements[(string) $v] = (string) $v; // make it the key also to avoid duplication
				}
			}
		}
		$output = implode("\n\n", self::$_html_head_elements);
		if (true === $print_output) {
			echo $output;
		}
		return $output;
	}

	/**
	 * Get an HTML variable from this page load.
	 * You must specify the request type if other than GET.
	 *
	 * @param string $name The variable name.
	 * @param string $default A default value to use if a value for $name cannot be found, if any.
	 * @param string $type Either "get" or "post", default "get"
	 * @return string The value of the HTTP variable.
	 * @todo Additional request types
	 */
	static public function httpvar($name, $default = "", $type = "get", $encode_for = null) {
		$name = (string) $name;
		$default = (is_array($default) ? (array) $default : (string) $default);
		$type = strtolower($type);

		$ret_val = null;

		switch ($type) {
			case "get":
				if (isset($_GET[$name])) {
					$ret_val = $_GET[$name];
				}
			break;

			case "post":
				if (isset($_POST[$name])) {
					$ret_val = $_POST[$name];
				}
			break;

			default:
				logevent("fatal", "Invalid request type '$type', must be either 'get' or 'post'.");
			break;
		}
		if (is_null($ret_val)) {
			$ret_val = $default;
		}

		//
		// Output encoding
		//
		switch (strtolower($encode_for)) {
			case '':
				// do nothing
			break;

			case 'html':          $ret_val = self::esapi()->encoder()->encodeForHTML($ret_val);              break;
			case 'attributename': $ret_val = self::esapi()->encoder()->encodeForHTMLAttributeName($ret_val); break;
			case 'attribute':     $ret_val = self::esapi()->encoder()->encodeForHTMLAttribute($ret_val);     break;
			case 'javascript':    $ret_val = self::esapi()->encoder()->encodeForJavaScript($ret_val);        break;
			case 'css':           $ret_val = self::esapi()->encoder()->encodeForCSS($ret_val);               break;

			default:
				logevent("fatal", "Invalid encoding type '$encode_for'.");
			break;
		}

		return $ret_val;
	}

	/**
	 * Load/set the page content
	 *
	 * This loadContent() wrapper is the core of the page rendering functionality
	 * and will auto-load controller.php and view.php if $content is null.  Content
	 * is cached so after the first run, the only way to update or change the content
	 * is to include it in the $content argument.
	 *
	 * @param string $content HTML to use in the content section.  Loads the specified module ($_GET['module']) by default.
	 * @return string HTML
	 */
	static public function loadContent($content = null) {

		if (!is_null($content)) {
			self::$_page_content = (string) $content;

		} elseif (is_null(self::$_page_content)) {

			//
			// If there isn't a valid module someone did something wrong or
			// a user is messing with the URL.  404.
			//
			if (!is_dir(APP_MODULE_DIR.APP_MODULE_NAME)) {
				self::$_page_content = self::renderHttpStatus(404);


			} else {
				ob_start();
				if (is_file(APP_MODULE_DIR.APP_MODULE_NAME."/controller.php")) {
					require_once(APP_MODULE_DIR.APP_MODULE_NAME."/controller.php");
				}
				if (is_file((APP_MODULE_DIR.APP_MODULE_NAME."/view.php"))) {
					require_once(APP_MODULE_DIR.APP_MODULE_NAME."/view.php");
				}
				self::$_page_content = ob_get_clean();
			}
		}
		return self::$_page_content;
	}

	/**
	 * Add messages to a view
	 * Display is determined by the active module
	 */
	static public function messages($message = null) {
		if (!is_null($message)) {
			self::$_messages[] = (string) $message;
		}
		return self::$_messages;
	}

	/**
	 * Render the page based on the requested module.
	 * @param bool $print_output Default true, whether or not to print the output directly
	 * @return string
	 */
	static public function render($print_output = false) {
		header('Content-Type: text/html; charset='.self::charset());

		//
		// Load the page content for the current module
		//
		self::loadContent();

		self::$_rendering = true;

		ob_start();
		// All templates should call View::content();
		require_once(APP_HTML_DIR.'templates/'.self::template().'.php');
		$output = ob_get_clean();
		self::$_rendering = false;

		if ($print_output) {
			echo $output;
		}

		return $output;
	}

	/**
	 * Render a CSS file inline to avoid HTTP requests
	 *
	 * First, it looks for $css_file in html/css/, then if it's not found it looks
	 * in documentroot/css/.  If it's still not found a Bdlm_Exception is thrown
	 * and logged.
	 *
	 * @param string $css_file Path to CSS file
	 * @param string $charset The character encoding to use for the rendered, defaults to self::charset()
	 * @param boolean $print_output Whether or not to print the output as well as returning it.  Default true.
	 * @return string
	 * @throws Bdlm_Exception
	 */
	static public function renderCss($css_file, $charset = '', $print_output = false) {
		if ('.css' !== substr($css_file, -4)) {
			logevent('fatal', "Only .css files can be rendered ('{$css_file}')");
		}

		$css_path = '';

		// html/css/
		if (file_exists(APP_HTML_DIR."{$css_file}")) {
			$css_path = APP_HTML_DIR."{$css_file}";

		// documentroot/css/
		} elseif (file_exists(APP_DOCROOT."{$css_file}")) {
			$css_path = APP_DOCROOT."{$css_file}";

		} else {
			logevent('fatal', "The file '{$css_file}' does not exist in ".APP_HTML_DIR." or ".APP_DOCROOT);
		}

		$charset = ($charset ? "@charset ".strtoupper($charset).";" : "@charset ".strtoupper(self::charset()).";");
		$css = file_get_contents($css_path);

		$output = <<<CSS
<style type="text/css" {$charset}>{$css}</style>
CSS;
		if (true === $print_output) {
			echo $output;
		}
		return $output;
	}

	/**
	 * Render an error page.
	 * @param bool $status_code Required, the error number to display a page for (404, etc.)
	 * @param bool $print_output Default true, whether or not to print the output directly
	 * @return string
	 * @todo Add the rest of the 4xx and 5xx status codes and default errdocs
	 */
	static public function renderHttpStatus($status_code, $print_output = false) {

        if (!file_exists(APP_HTML_DIR."errdocs/{$status_code}.php")) {
			header('HTTP/1.1 500 Internal Server Error');
            throw new Bdlm_Exception(APP_HTML_DIR."errdocs/{$status_code}.php does not exist!");
        }

		switch ($status_code) {
			case '400': header('HTTP/1.1 400 Bad Request');                     break;
			case '401': header('HTTP/1.1 401 Unauthorized');                    break;
			case '402': header('HTTP/1.1 402 Payment Required');                break;
			case '403': header('HTTP/1.1 403 Forbidden');                       break;
			case '404': header('HTTP/1.1 404 Not Found');                       break;
			case '405': header('HTTP/1.1 405 Method Not Allowed');              break;
			case '406': header('HTTP/1.1 406 Not Acceptable');                  break;
			case '407': header('HTTP/1.1 407 Proxy Authentication Required');   break;
			case '408': header('HTTP/1.1 408 Request Timeout');                 break;
			case '409': header('HTTP/1.1 409 Conflict');                        break;
			case '410': header('HTTP/1.1 410 Gone');                            break;
			case '411': header('HTTP/1.1 411 Length Required');                 break;
			case '412': header('HTTP/1.1 412 Precondition Failed');             break;
			case '413': header('HTTP/1.1 413 Request Entity Too Large');        break;
			case '414': header('HTTP/1.1 414 Request-URI Too Long');            break;
			case '415': header('HTTP/1.1 415 Unsupported Media Type');          break;
			case '416': header('HTTP/1.1 416 Requested Range Not Satisfiable'); break;
			case '417': header('HTTP/1.1 417 Expectation Failed');              break;
			case '500': header('HTTP/1.1 500 Internal Server Error');           break;
			case '501': header('HTTP/1.1 501 Not Implemented');                 break;
			case '502': header('HTTP/1.1 502 Bad Gateway');                     break;
			case '503': header('HTTP/1.1 503 Service Unavailable');             break;
			case '504': header('HTTP/1.1 504 Gateway Timeout');                 break;
			case '505': header('HTTP/1.1 505 HTTP Version Not Supported');      break;
		}

        ob_start();
        require_once(APP_HTML_DIR."errdocs/{$status_code}.php");
        $output = ob_get_clean();

		return self::loadContent($output);
	}

	/**
	 * Render a jascript file inline to avoid HTTP requests
	 *
	 * First, it looks for $js_file in html/javascript/, then if it's not found
	 * it looks in documentroot/javascript/.  If it's still not found a Bdlm_Exception
	 * is thrown and logged.
	 *
	 * @param string $js_file Path to JS file
	 * @param string $charset The character encoding to use for the rendered, defaults to self::charset()
	 * @param boolean $print_output Whether or not to print the output as well as returning it.  Default true.
	 * @return string
	 * @throws Bdlm_Exception
	 */
	static public function renderJs($js_file, $charset = '', $print_output = false) {
		if ('.js' !== substr($js_file, -3)) {
			logevent('fatal', 'Only .js files can be rendered');
		}

		$js_path = '';

		// html/javascript/
		if (file_exists(APP_HTML_DIR."{$js_file}")) {
			$js_path = APP_HTML_DIR."{$js_file}";

		// documentroot/javascript/
		} elseif (file_exists(APP_DOCROOT."{$js_file}")) {
			$js_path = APP_DOCROOT."{$js_file}";

		} else {
			logevent('fatal', "The file '{$js_file}' does not exist in ".APP_HTML_DIR." or ".APP_DOCROOT);
		}

		$charset = ($charset ? 'charset="'.strtolower($charset).'";' : 'charset="'.strtolower(self::charset()).'";');
		$javascript = file_get_contents($js_path);

		$output = <<<JS
<script type="text/javascript" language="javascript" {$charset}>{$javascript}</script>
JS;

		if (true === $print_output) {
			echo $output;
		}
		return $output;
	}

	/**
	 * Get/set the page template
	 * Defaults to 'default'
	 * @param string $template Specifies the template html/templates/{$template}.php
	 * @return string The current template name
	 */
	static public function template($template = null) {
		if (!is_null($template)) {
			self::$_page_template = (string) $template;
		}
		return self::$_page_template;
	}

	/**
	 * Get/set the page title
	 * @param string $title
	 * @return string
	 */
	static public function title($title = null) {
		if (!is_null($title)) {
			self::$_page_title = (string) $title;
		}
		$output = self::esapi()->encoder()->encodeForHTML(self::$_page_title);
		if ($print_output) {
			echo $output;
		}
		return $output;
	}

	/**
	 * Include a widget from html/widgets/
	 * @param string $widget The name of the widget to load
	 * @todo Don't call them widgets
	 */
	static public function widget($widget) {
		if (!file_exists(APP_HTML_DIR."widgets/{$widget}.php")) {
			logevent('fatal', "The specified widget '{$widget}' does not exist.");
		}

		require(APP_HTML_DIR."widgets/{$widget}.php");
	}

}