<?php

  /*
   * Some AES encryption. Works in PHP 4 or 5.
   * Scrounged on the web, and made to work.
   */

class Cipher {
  var $securekey, $iv;
  var $enc, $mode;

  function Cipher($textkey) {
    $this->enc = MCRYPT_RIJNDAEL_128;
    $this->mode = MCRYPT_MODE_ECB;
    $this->securekey = mhash(MHASH_SHA256,$textkey);
    $iv_size = mcrypt_get_iv_size($this->enc, $this->mode);
    $this->iv = mcrypt_create_iv($iv_size, MCRYPT_DEV_RANDOM);
  }

  function encrypt($input) {
    return mcrypt_encrypt($this->enc, $this->securekey, $input, $this->mode, $this->iv);
  }

  function decrypt($input) {
    return trim(mcrypt_decrypt($this->enc, $this->securekey, $input, $this->mode, $this->iv));
  }

  function encrypt2hex($input) {
    return bin2hex($this->encrypt($input));
  }

  function decrypthex($input) {
    return $this->decrypt(pack("H*", $input));
  }
}

/* test code
$cipher = new Cipher('secret passphrase');

$encryptedtext = $cipher->encrypt2hex(pack("H*", "1650f617c024d6441461b2538c6d9540"));
echo "->encrypt = $encryptedtext<br />";

$decryptedtext = bin2hex($cipher->decrypthex($encryptedtext));
echo "->decrypt = $decryptedtext<br />";

var_dump($cipher);
*/
?>
