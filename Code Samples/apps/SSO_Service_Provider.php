<?php
/**
 * Bdlm Simple Single Sign-On Authentication
 * @author Michael Kenney <mkenney@webbedlam.com>
 */

// This is the shared key
$key = 'ilikepie';

// Load the token element from the POST data
$value = base64_decode($_POST['token']);

// First 128bits are the IV, the rest is encrypted data
$iv = substr($value, 0, 128 / 8);
$encrypted = substr($value, 128 / 8);

// RIJNDAEL/AES256
// 128 bit is inaccurate. 128bit block with 256bit key is AES256 (which is what we are using)
// CBC mode (cipher block chaining)
$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $encrypted, MCRYPT_MODE_CBC, $iv);

// PKCS11 padding pads the final block with a byte with value of number of bytes of padding
$decrypted = substr($decrypted, 0, strlen($decrypted) - ord($decrypted[strlen($decrypted) - 1]));

// First 256bits are an SHA256 hash of the data
$hash = substr($decrypted, 0, 256 / 8);

// Remaining bits are the data, trim null padding
$data = trim(substr($decrypted, 256 / 8));

// Create a validation hash
$validation_hash = hash("sha256", $data, true);

// This checks for tampering.
if ($hash != $validation_hash) {
	die("An authentication error has occoured, please contact your system administrator: $hash != $validation_hash");
}

// Parse the data into an array for easy access
$parsed = array();
parse_str($data, $parsed);

// Allow a 300 second grace period in case of clock skew between servers.
// This prevents replay attacks.
// The timestamp passed should be in GMT
$offset = abs(strtotime("now") - strtotime($parsed['timestamp']));
if ($offset > 300) {
	die("Clock skew detected, please contact your system administrator. {$parsed['timestamp']}:".strtotime($parsed['timestamp']));
}

echo "Success!<br /><br />";
echo "Data:<pre>"; print_r($parsed); echo "</pre>";
echo "token: {$_POST['token']}<br />\n";
echo "raw token: $value<br />\n";
echo "decrypted: $decrypted<br />\n";
echo "data: $data<br />\n";
echo "submitted hash: $hash<br />\n";
echo "validation hash: $validation_hash<br />\n";
echo "current timestamp: ".strtotime("now")."<br />\n";
echo "submitted timestamp: ".strtotime($parsed['timestamp'])."<br />\n";
echo "<br /><b>The Identity Provider token has been validated.  User is authentic.</b>";
