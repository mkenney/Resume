<?php
/**
 * Bdlm Simple Single Sign-On Authentication
 * Create an authentication token based on the payload data
 * @author Michael Kenney <mkenney@webbedlam.com>
 */

// Data form
if (!count($_GET)) {
	date_default_timezone_set('UTC');
?>
<html><head></head><body>
	<form name="loginform" id="loginform" action="" method="get">
		fname: <input type="text" name="fname" /><br />
		lname: <input type="text" name="lname" /><br />
		email: <input type="text" name="email" /><br />
		timestamp: <?php echo date('c', time()).' ('.time().')'; ?><input type="hidden" name="timestamp" value="<?php echo urlencode(date('c', time())); ?>" /><br />
		<button onclick="document.loginform.submit();">Create login token</button>
	</form>
</body></html>
<?php

// Token creation
} else {

	// This is the shared key
	$key = 'ilikepie';

	// Create a query string out of the data
	// I.E. fname=Michael&lname=Kenney&email=mkenney@webbedlam.com&timestamp=1295029720
	$data = urldecode(http_build_query($_GET));

	// Create a hash of the query string so the service provider can validate that the data has not been tampered with
	$hash = hash("sha256", $data, true);

	// PHP method for initializing the Mcrypt modules
	$crypt = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'ctr', '');

	// Initialization vector for encryption
	$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($crypt), MCRYPT_RAND);

	// Encrypt the data and hash together for validation on the service provider
	$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $hash.$data, 'cbc', $iv);

	// Create the token to submit to the service provider.
	// Include the IV so the service provider can decrypt the data.
	$token = base64_encode($iv.$encrypted);
?>
<html><head></head><body>
<?php
echo "iv = $iv<br />\n";
echo "data: $data<br />\n";
echo "hash: $hash<br />\n";
echo "encrypted payload: $encrypted<br />\n";
echo "token: $token<br />\n";
?>
	<form name="loginform" id="loginform" action="SSO_Service_Provider.php" method="post">
		<input type="hidden" name="token" value="<?php echo $token;?>" />
		<button onclick="document.loginform.submit();">Login</button>
	<form>
</body></html>
<?php
}