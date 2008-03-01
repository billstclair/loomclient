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

// Copyright 2008 Bill St. Clair
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions
// and limitations under the License.

?>
