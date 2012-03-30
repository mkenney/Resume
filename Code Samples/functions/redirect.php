<?php
/**
 * Common system functions
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Functions
 */

/**
 * Redirect browsers using various methods
 *
 * The redirection method ($type) may be one of:<br />
 * 'html'/void - The default action is to use HTML META refresh redirection<br />
 * 'header' - header() redirection<br />
 * 'javascript' - document.location redirection<br />
 *
 * @param string $url Target URL
 * @param string $type Redirection method
 * If "html" then a META refresh is used
 * If "javascript" then the document.location value is updated
 * If "post" then post data is included using a form.
 * @param string $status The HTTP/1.1 redirection status code to use
 * @return void
 *
 * @copyright 2005 - present Michael Kenney <mkenney@webbedlam.com>
 * @author Michael Kenney <mkenney@webbedlam.com>
 * @package Bedlam_CORE
 * @subpackage Functions
 * @version 0.24
 */
function redirect($url = '', $type = null, $status = '') {

	//
	// Close open sessions
	//
	if (session_id() != '') {
		session_write_close();
	}

	//
	// Redirection status headers
	//
	switch ($status) {
		case '301':
			header('HTTP/1.1 301 Moved Permanently');
		break;

		case '302':
			header('HTTP/1.1 302 Found');
		break;

		case '303':
			header('HTTP/1.1 303 See Other');
		break;

		case '304':
			header('HTTP/1.1 304 Not Modified');
		break;

		//
		// Most browsers don't play nicely with this one
		//
		//case '305':
		//	header('HTTP/1.1 305 Use Proxy');
		//break;

		//
		// No longer used
		//
		//case '306':
		//	header('HTTP/1.1 306 Switch Proxy');
		//break;

		case '307':
			header('HTTP/1.1 307 Temporary Redirect');
		break;
	}

	//
	// Check redirect URI, default to current location
	//
	$url = ($url == '') ? 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] : $url;

	switch (strtolower($type)) {

		//
		// HTML redirection
		//
		case 'html':
			echo '<meta http-equiv="Refresh" content="0; url='.$url.'">';
		break;

		//
		// JavaScript redirection
		//
		case 'javascript':
			echo '<script type="text/javascript">document.location.href="'.$url.'";</script>\n';
		break;

		//
		// Include POST data,
		//
		case 'post':
			$form_id = uniqid('redirect_');
			$form = '<form id="'.$form_id.'" action="'.$url.'" method="post">';
			foreach ($_POST as $k => $v) {
				$form .= '<input type="hidden" name="'.$k.'" value="'.$v.'" />';
			}
			$form .= '</form><script type="text/javascript">document.'.$form_id.'.submit();</script>';

			echo $form;
		break;

		//
		// Header redirection (default)
		//
		case 'header':
		// break; omitted

		default:
			header("Location: ".$url);
		break;
	}

	exit;
}
