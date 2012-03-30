<?php
/**
 * Class file for the Bdlm_View class
 *
 * @copyright 2010 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage View
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
 * @version 0.1a
 */
abstract class Bdlm_View implements Bdlm_View_Interface {

	/**
	 * Store error messages for later display
	 * @var array $_errors
	 */
	private static $_errors = array();
	/**
	 * Store user messages for later display
	 * @var array $_messages
	 */
	private static $_messages = array();
	/**
	 * Store additional header elements
	 * @var array $_html_head_elements
	 */
	private static $_html_head_elements = array();
	/**
	 * Web page <title> element
	 * @var string $page_title
	 */
	private static $_page_title = 'Example Application';
	/**
	 * Store the HTML footer content
	 * @var string $_html_footer
	 */
	private static $_html_footer = null;
	/**
	 * Store the HTML header content
	 * @var string $_html_header
	 */
	private static $_html_header = null;
	/**
	 * Store the page footer content
	 * @var string $_page_footer
	 */
	private static $_page_footer = null;
	/**
	 * Store the page header content
	 * @var string $_page_header
	 */
	private static $_page_header = null;
	/**
	 * Store the main page content
	 * @var string $_page_content
	 */
	private static $_page_content = null;
	/**
	 * Store the OWASP ESAPI instance
	 * @var Bdlm_Esapi $_esapi
	 */
	private static $_esapi = null;

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
	 * Add elements custom elements to the page header (<script> tags, etc.);
	 * @param string $element HTML to add to the <head> section of the HTML
	 * @return string HTML to add to the <head> section of the HTML
	 */
	public function headElements($element = null) {
		if (!is_null($element)) {
			self::$_html_head_elements[(string) $element] = (string) $element; // make it the key also to avoid duplication
		}
		return implode("\n", self::$_html_head_elements);
	}

	/**
	 * Load/set the footer HTML, generally just the closing tags
	 * @param string $footer HTML to be used as the closing HTML, loads from the HTML templates by default
	 * @return void
	 */
	public function htmlFooter($footer = null) {
		if (!is_null($footer)) {
			self::$_html_footer = (string) $footer;
		}
		if (is_null(self::$_html_footer)) {
			ob_start();
			require_once(APP_HTML_DIR.'htmlFooter.php');
			self::$_html_footer = ob_get_clean();
		}
		return self::$_html_footer;
	}

	/**
	 * Load/set the header HTML, mainly the doctype and head elements (css, javascript libs, etc.)
	 * @param string $header HTML to be used as the starting HTML, loads from the HTML templates by default
	 * @return void
	 */
	public function htmlHeader($header = null) {
		if (!is_null($header)) {
			self::$_html_header = (string) $header;
		}
		if (is_null(self::$_html_header)) {
			ob_start();
			require_once(APP_HTML_DIR.'htmlHeader.php');
			self::$_html_header = ob_get_clean();
		}
		return self::$_html_header;
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
	public function httpvar($name, $default = "", $type = "get", $encode_for = null) {
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
	 * Add messages to a view
	 * Display is determined by the active module
	 */
	public function messages($message = null) {
		if (!is_null($message)) {
			self::$_messages[] = (string) $message;
		}
		return self::$_messages;
	}

	/**
	 * Load/set the page content, this is the core of the page rendering.
	 * @param string $content HTML to use in the content section.  Loads the specified module ($_GET['module']) by default.
	 * @return string HTML
	 */
	public function pageContent($content = null) {

		if (!is_null($content)) {
			self::$_page_content = (string) $content;

		} else {

			//
			// If there isn't a valid module someone did something wrong or
			// a user is messing with the URL.  Redirect.
			//
			if (!is_dir(APP_MODULE_DIR.APP_MODULE_NAME)) {
				redirect('/');
			}

			ob_start();
			if (is_file(APP_MODULE_DIR.APP_MODULE_NAME."/controller.php")) {
				require_once(APP_MODULE_DIR.APP_MODULE_NAME."/controller.php");
			}
			if (is_file((APP_MODULE_DIR.APP_MODULE_NAME."/view.php"))) {
				require_once(APP_MODULE_DIR.APP_MODULE_NAME."/view.php");
			}
			self::$_page_content = ob_get_clean();
		}
		return self::$_page_content;
	}

	/**
	 * Load/set the page footer HTML, generally any footer information (copyright info, etc.).
	 * @param string $footer HTML to be used as the page footer, loads from the HTML templates by default
	 * @return void
	 */
	public function pageFooter($footer = null) {
		if (!is_null($footer)) {
			self::$_page_footer = (string) $footer;
		}
		if (is_null(self::$_page_footer)) {
			ob_start();
			require_once(APP_HTML_DIR.'pageFooter.php');
			self::$_page_footer = ob_get_clean();
		}
		return self::$_page_footer;
	}

	/**
	 * Load/set the page header HTML, generally any header information (logo, login/registration links, etc.).
	 * @param string $header HTML to be used as the page header, loads from the HTML templates by default
	 * @return void
	 */
	public function pageHeader($header = null) {
		if (!is_null($header)) {
			self::$_page_header = (string) $header;
		}
		if (is_null(self::$_page_header)) {
			ob_start();
			require_once(APP_HTML_DIR.'pageHeader.php');
			self::$_page_header = ob_get_clean();
		}
		return self::$_page_header;
	}

	/**
	 * Render the page based on the requested module.
	 * @param bool $print_output Default true, whether or not to print the output directly
	 * @return string
	 */
	public function render($print_output = true) {
		$output = '';
		$output .= self::pageHeader();
		$output .= self::pageContent();
		$output .= self::pageFooter();
		$output .= self::htmlFooter();

		//
		// Do this last so all the other pieces have a chance to call headElements() first
		//
		$output = self::htmlHeader().$output;

		if ($print_output) {
			echo $output;
		}
		return $output;
	}

	/**
	 * Get/set the page title
	 * @return string
	 */
	public function title($title = null) {
		if (!is_null($title)) {
			self::$_page_title = (string) $title;
		}
		return self::esapi()->encoder()->encodeForHTML(self::$_page_title);
	}

}