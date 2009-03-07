<?php

  // Cryptographically secure random number generation

class LoomRandom {

  var $urandom_filehandle;      // /dev/urandom file handle
  var $use_urandom;             // false if we can't open /dev/urandom

  function LoomRandom() {
    $this->urandom_filehandle = false;
    $this->use_urandom = true;
  }

  // Return $num random bytes from /dev/urandom
  function urandom_bytes($num) {
    if ($num < 0)
      err("NUM must be nonnegative in urandom_bytes");
    if ($this->use_urandom && !$this->urandom_filehandle) {
      $file = @fopen("/dev/urandom", "r");
      if (!$file) $this->use_urandom = false;
      else $this->urandom_filehandle = $file;
    }
    $res = '';
    if ($this->use_urandom) {
      while (strlen($res) < $num) {
        $res .= fread($this->urandom_filehandle, $num - strlen($res));
      }
    } else {
      for ($i=0; $i<$num; $i++) {
        $res .= chr(mt_rand(0, 255));
      }
    }
    return $res;
  }

  // Return a random 128-bit location, as hex
  function random_id() {
    $res = bin2hex($this->urandom_bytes(16));
    if (strlen($res) < 32) $res = str_repeat("0", 32 - strlen($res)) . $res;
    return $res;
  }

}

/* testing code

$random = new LoomRandom();
echo $random->random_id() . "\n";

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
