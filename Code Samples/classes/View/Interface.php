<?php
/**
 * View interface file for Bedlam CORE
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Views
 */

/**
 * View class interface
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Object
 * @version 0.1
 */
interface Bdlm_View_Interface {

	/**
	 * Add error messages to a view
	 * Display is determined by the active module
	 */
	static public function errors($error = null);

	/**
	 * Get/set the content for the page.
	 *
	 * The argument is provided just for consistency.  You wouldn't really do that,
	 * you would just echo whatever you wanted in the template.  It does cache
	 * the passed content.
	 *
	 * @param string $content Set the content portion of the UI
	 * @param boolean $print_outputProvided for API consistency
	 * @return string
	 */
	static public function content($content = null, $print_output = false);

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
	static public function footElements($element = null, $print_output = false);

	/**
	 * Add elements custom elements to the page header (<script> tags, etc.);
	 *
	 * This should be the last View call in the head tag.  Use to add head tags
	 * (Javascript, CSS, etc.)
	 *
	 * @param string $element HTML to add to the <head> section of the HTML
	 * @return string HTML to add to the <head> section of the HTML
	 */
	static public function headElements($element = null, $print_output = false);

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
	static public function httpvar($name, $default = "", $type = "get", $encode_for = null);

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
	static public function loadContent($content = null);

	/**
	 * Add messages to a view
	 * Display is determined by the active module
	 */
	static public function messages($message = null);

	/**
	 * Render the page based on the requested module.
	 * @param bool $print_output Default true, whether or not to print the output directly
	 * @return string
	 */
	static public function render($print_output = true);

	/**
	 * Render a CSS file inline to avoid HTTP requests
	 * @param string $css_file Path to CSS file
	 * @param string $charset The character encoding to use for the rendered
	 * @param boolean $print_output Whether or not to print the output as well as returning it.  Default true.
	 * @return type
	 */
	static public function renderCss($css_file, $charset = '', $print_output = true);

	/**
	 * Render an error page.
	 * @param bool $status_code Required, the error number to display a page for (404, etc.)
	 * @param bool $print_output Default true, whether or not to print the output directly
	 * @return string
	 * @todo Add the rest of the 4xx and 5xx status codes and default errdocs
	 */
	static public function renderHttpStatus($status_code, $print_output = true);

	/**
	 * Render a jascript file inline to avoid HTTP requests
	 * @param string $js_file Path to JS file
	 * @param string $charset The character encoding to use for the rendered
	 * @param boolean $print_output Whether or not to print the output as well as returning it.  Default true.
	 * @return type
	 */
	static public function renderJs($js_file, $charset = '', $print_output = true);

	/**
	 * Get/set the page template
	 * Defaults to 'default'
	 * @param string $template Specifies the template html/templates/{$template}.php
	 * @return string The current template name
	 */
	static public function template($template = null);

	/**
	 * Get/set the page title
	 * @param string $title
	 * @return string
	 */
	static public function title($title = null);

	/**
	 * Include a widget from /html/widgets/
	 * @param string $widget The name of the widget to load
	 * @todo Don't call them widgets
	 */
	static public function widget($widget);
}