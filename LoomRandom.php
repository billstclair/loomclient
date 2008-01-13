<?php

  // Cryptographically secure random number generation

class LoomRandom {

  var $urandom_filehandle;      // /dev/urandom file handle

  function LoomRandom() {
    $this->urandom_filehandle = false;
  }

  // Return $num random bytes from /dev/urandom
  function urandom_bytes($num) {
    if ($num < 0)
      err("NUM must be nonnegative in urandom_bytes");
    if (!$this->urandom_filehandle) {
      $file = fopen("/dev/urandom", "r");
      if (!$file) err("Unable to open /dev/urandom");
      $this->urandom_filehandle = $file;
    }
    $res = '';
    while (strlen($res) < $num)
      $res .= fread($this->urandom_filehandle, $num - strlen($res));
    return $res;
  }

  // Return a random 128-bit location, as hex
  function random_id() {
    return bin2hex($this->urandom_bytes(16));
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
