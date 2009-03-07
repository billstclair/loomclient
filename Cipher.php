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

/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1/GPL 2.0/LGPL 2.1/Apache 2.0
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is LoomClient PHP library
 *
 * The Initial Developer of the Original Code is
 * Bill St. Clair.
 * Portions created by the Initial Developer are Copyright (C) 2008
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Bill St. Clair <bill@billstclair.com>
 *
 * Alternatively, the contents of this file may be used under the
 * terms of the GNU General Public License Version 2 or later (the
 * "GPL"), the GNU Lesser General Public License Version 2.1 or later
 * (the "LGPL"), or The Apache License Version 2.0 (the "AL"), in
 * which case the provisions of the GPL, LGPL, or AL are applicable
 * instead of those above. If you wish to allow use of your version of
 * this file only under the terms of the GPL, the LGPL, or the AL, and
 * not to allow others to use your version of this file under the
 * terms of the MPL, indicate your decision by deleting the provisions
 * above and replace them with the notice and other provisions
 * required by the GPL or the LGPL. If you do not delete the
 * provisions above, a recipient may use your version of this file
 * under the terms of any one of the MPL, the GPL the LGPL, or the AL.
 ****** END LICENSE BLOCK ***** */
?>
