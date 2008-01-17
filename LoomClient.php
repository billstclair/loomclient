<?php
require_once "bcbitwise.php";

/*
 * Client to the webapp at http://loom.cc/
 * See https://loom.cc/?function=help&topic=grid_tutorial&mode=advanced
 */

class LoomClient
{
  var $url_prefix;              // Who you gonna call?
  var $bits128;                 // 128 1-bits = 2**128 - 1

  function LoomClient($prefix = 'https://loom.cc/') {
    if (substr($prefix, -1) != '/') $prefix .= '/';
    $this->url_prefix = $prefix;
    $this->bits128  = bcsub(bcleftshift(1, 128), 1);
  }

  // This is all you really need to call
  // The functions below are just syntactic sugar for calling get()
  function get($keys, &$url) {
    $url = $this->url($this->url_prefix, $keys);
    // Kluge around protocol warning
    // I really want try/finally here, but PHP doesn't have it
    $erpt = $this->disable_warnings();
    $kv = file_get_contents($url);
    $this->reenable_warnings($erpt);
    return $this->parsekv($kv);
  }

  function buy($type, $location, $usage, &$url) {
    return $this->get(array('function' => 'grid',
                            'action' => 'buy',
                            'type' => $type,
                            'loc' => $location,
                            'usage' => $usage),
                      $url);
  }

  function sell($type, $location, $usage, &$url) {
    return $this->get(array('function' => 'grid',
                            'action' => 'sell',
                            'type' => $type,
                            'loc' => $location,
                            'usage' => $usage),
                      $url);
  }

  function issuer($type, $orig, $dest, &$url) {
    return $this->get(array('function' => 'grid',
                            'action' => 'issuer',
                            'type' => $type,
                            'orig' => $orig,
                            'dest' => $dest),
                      $url);
  }

  function touch($type, $location, &$url) {
    return $this->get(array('function' => 'grid',
                            'action' => 'touch',
                            'type' => $type,
                            'loc' => $location),
                      $url);
  }

  function look($type, $hash, &$url) {
    return $this->get(array('function' => 'grid',
                            'action' => 'look',
                            'type' => $type,
                            'hash' => $hash),
                      $url);
  }

  function move($type, $quantity, $origin, $destination, &$url) {
    return $this->get(array('function' => 'grid',
                            'action' => 'move',
                            'type' => $type,
                            'qty' => $quantity,
                            'orig' => $origin,
                            'dest' => $destination),
                      $url);
  }

  function buy_archive($loc, $usage, &$url) {
    return $this->get(array('function' => 'archive',
                            'action' => 'buy',
                            'loc' => $loc,
                            'usage' => $usage),
                      $url);
  }

  function sell_archive($loc, $usage, &$url) {
    return $this->get(array('function' => 'archive',
                            'action' => 'sell',
                            'loc' => $loc,
                            'usage' => $usage),
                      $url);
  }

  function touch_archive($loc, &$url) {
    return $this->get(array('function' => 'archive',
                            'action' => 'touch',
                            'loc' => $loc),
                      $url);
  }

  function look_archive($hash, &$url) {
    return $this->get(array('function' => 'archive',
                            'action' => 'look',
                            'hash' => $hash),
                      $url);
  }

  function write_archive($loc, $usage, $content, &$url) {
    return $this->get(array('function' => 'archive',
                            'action' => 'write',
                            'loc' => $loc,
                            'usage' => $usage,
                            'content' => $content),
                      $url);
  }

  function url($prefix, $keys) {
    $str = $prefix;
    $delim = '?';
    foreach($keys as $key => $value) {
      $str .= $delim . $key . '=' . urlencode($value);
      $delim = '&';
    }
    return $str;
  }

  function parsekv($kv) {
    $lines = explode("\n", $kv);
    $first = true;
    $res = array();
    foreach ($lines as $line) {
      if ($first && ($line != '(')) {
        return $res;           // Could throw exception in PHP 5
      }
      $first = false;
      if ($line == ')') return $res;
      if (substr($line, 0, 1) == ':') $key = substr($line, 1);
      if (substr($line, 0, 1) == '=') {
        $value = $this->unquote_cstring(substr($line, 1));
        $res[$key] = $value;
      }
    }
  }

  function quote_cstring($cstring) {
    $res = '';
    for ($i=0; $i<strlen($cstring); $i++) {
      $chr = substr($cstring, $i, 1);
      if ($chr == "\n") $res .= '\n';
      elseif ($chr == '"') $res .= '\"';
      elseif ($chr == "\t") $res .= '\t';
      elseif ($chr == "\\") $res .= "\\\\";
      elseif ($chr < ' ' || $chr > '~') $res .= '\\'.sprintf('%03o', ord($chr));
      else $res .= $chr;
    }
    return $res;
  }

  function unquote_cstring($cstring) {
    $res = '';
    $len = strlen($cstring);
    $i = 0;
    while ($i<$len) {
      $chr = substr($cstring, $i, 1);
      if ($chr == "\\") {
        $i++;
        if ($i >= $len) {
          $res .= $chr;
          break;
        }
        $chr = substr($cstring, $i, 1);
        if ($chr == 'n') $res .= "\n";
        elseif ($chr == '"') $res .= '"';
        elseif ($chr == 't') $res .= "\t";
        elseif ($chr == "\\") $res .= "\\";
        elseif ($chr >= '0' and $chr <= '9') {
          if ($len < ($i + 3)) {
            $res .= substr($cstring, $i-1);
            break;
          }
          sscanf(substr($cstring, $i, 3), '%o', &$n);
          $res .= chr($n);
          $i += 2;
        }
        else $res .= "\\" . $chr;
      }
      else {
        $res .= $chr;
      }
      $i++;
    }
    return $res;
  }

  // This enables a kluge in get() to turn off the protocol warning that
  // results from doing a HTTP GET to http://loom.cc/
  function disable_warnings() {
    $erpt = error_reporting();
    error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
    return $erpt;
  }

  function reenable_warnings($erpt) {
    error_reporting($erpt);
  }

  // Return the sha256 hash of a string.
  // The result is encoded as hex, and guaranteed to be 64 charaacters,
  // with leading zeroes added, if necessary.
  function sha256($str) {
    if (function_exists('hash_init')) {
      // Modern PHP
      $ctx = hash_init('sha256');
      hash_update($ctx, $str);
      $hash = hash_final($ctx);
    } else if (function_exists('mhash')) {
      // Old PHP with mhash compiled in
      $hash = bin2hex(mhash(MHASH_SHA256, $str));
    } else {
      // Not a hash, really, but the best we can do
      $hash = bin2hex($str);
      if (strlen($hash) > 32) $hash = substr($hash, 1, 32);
    }
    if (strlen($hash) < 32) {
      $hash = str_repeat(32 - strlen($hash)) . $hash;
    }
    return $hash;
  }

  // PHP has bin2hex($x). An easier to remember name for pack("H*", $x)
  // Note that this does NOT get you a string that looks like a decimal number.
  // It's raw bits, 8 bits per characetr.
  function hex2bin($x) {
    return pack("H*", $x);
  }

  // Loom changes an SHA256 hash to a location by xoring the two halves
  // Input and output are both encoded as hex
  // Won't work correctly 
  function hash2location($hash) {
    $value = bchexdec($hash);
    $bits128 = $this->bits128;
    $location = bcxor(bcrightshift($value, 128), bcand($value, $bits128));
    return bcdechex($location);
  }

} // End of LoomClient class


/* Testing code. Uncomment to run.
$api = new LoomClient();
$values = $api->move('12345678123456781234567812345678',
                     12,
                     '22345678123456781234567812345678',
                     '32345678123456781234567812345678',
                     &$url);
echo $url . "\n";
print_r($values);
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
