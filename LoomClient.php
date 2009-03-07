<?php
require_once "bcbitwise.php";
require_once "Socket.php";
require_once "LoomRandom.php";

/*
 * Client to the webapp at http://loom.cc/
 * See https://loom.cc/?function=help&topic=grid_tutorial&mode=advanced
 */

class LoomClient
{
  var $url_prefix;              // Who you gonna call?
  var $bits128;                 // 128 1-bits = 2**128 - 1
  var $socket;                  // our connection to the Loom server
  var $random;                  // An instance of LoomRandom

  function LoomClient($prefix = 'https://loom.cc/') {
    if (substr($prefix, -1) != '/') $prefix .= '/';
    $this->url_prefix = $prefix;
    $this->bits128  = bcsub(bcleftshift(1, 128), 1);
    $this->socket = FALSE;
    $this->random = new LoomRandom();
  }

  // This is all you really need to call
  // The functions below are just syntactic sugar for calling get()
  /* Save the old non-persistent connection version
  function get($keys, &$url) {
    $url = $this->url($this->url_prefix, $keys);
    // Kluge around protocol warning
    // I really want try/finally here, but PHP doesn't have it
    $erpt = $this->disable_warnings();
    $kv = file_get_contents($url);
    $this->reenable_warnings($erpt);
    return $this->parsekv($kv);
  }
  */

  // The base of the Loom interface
  // Send an HTTP GET, with args in $keys.
  // Return whatever Loom returns.
  function rawget($keys, &$url) {
    return $this->rawGetOrPost($keys, 'get', $url);
  }

  function rawpost($keys, &$url) {
    return $this->rawGetOrPost($keys, 'post', $url);
  }

  function rawGetOrPost($keys, $getOrPost, &$url) {
    if (!$this->socket) {
      // Should make this less brittle
      if (substr($this->url_prefix, 0, 5) == 'https') {
        $host = substr($this->url_prefix, 8, -1);
        $ssl = TRUE;
      } else {
        $host = substr($this->url_prefix, 7, -1);
        $ssl = FALSE;
      }
      $this->socket = new Socket($host, $ssl);
      $this->socket->connect();
    }
    $uri = $this->url('', $keys);
    $url = $this->url_prefix . $uri;
    $uri = '/' . $uri;
    if ($getOrPost == 'post') $this->socket->post($uri);
    else $this->socket->get($uri);
    return $this->socket->body;
  }

  // This is all you really need to call
  // The functions below are just syntactic sugar for calling get()
  function get($keys, &$url) {
    $kv = $this->rawget($keys, $url);
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

  // $locs and $types are lists of space-separated hex IDs.
  // If $zeroes is true, will return 0 values.
  function scan($locs, $types, $zeroes, &$url) {
    $a = array('function' => 'grid',
               'action' => 'scan',
               'locs' => $locs,
               'types' => $types);
    if ($zeroes) $a['zeroes'] = '1';
    return $this->get($a, $url);
  }

  // $locs is array(locname => id, ...)
  // $types is array(typename => array('id' => id,
  //                                   'name' => typename,
  //                                   'min_precision' => min_precision,
  //                                   'scale' => scale),
  //                 ...)
  // Returns array(locname => array(typename => value, ...), ...)
  // Returns FALSE if it gets an error from the loom server
  function namedScan($locs, $types, $zeroes, &$url) {
    $loca = array();
    $locstring = "";
    foreach ($locs as $locname => $id) {
      if ($locstring != '') $locstring .= ' ';
      $locstring .= $id;
      $loca[$id] = $locname;
    }

    $typea = array();
    $typestring = "";
    foreach ($types as $typename => $attributes) {
      $id = $attributes['id'];
      if ($typestring != '') $typestring .= ' ';
      $typestring .= $id;
      $typea[$id] = $attributes;
    }

    $res = $this->scan($locstring, $typestring, $zeroes, &$url);

    $resa = array();
    foreach ($loca as $id => $locname) {
      $vals = explode(' ', $res["loc/$id"]);
      $vala = array();
      foreach ($vals as $val) {
        $val = explode(':', $val);
        $value = $val[0];
        $id = $val[1];
        $attributes = $typea[$id];
        $typename = $attributes['name'];
        $min_precision = $attributes['min_precision'];
        $scale = $attributes['scale'];
        $vala[$typename] = $this->applyScale($value, $min_precision, $scale);
      }
      $resa[$locname] = $vala;
    }
    return $resa;
  }

  function applyScale($value, $min_precision, $scale) {
    if ($value < 0) $value = bcadd($value, 1);
    if ($scale > 0) $value = bcdiv($value, bcpow(10, $scale, 0), $scale);

    $dotpos = strpos($value, '.');

    if ($dotpos > 0) {
      while (substr($value, -1) == '0') {
        $value = substr($value, 0, strlen($value)-1);
      }
      if (substr($value, -1) == '.') {
        $value = substr($value, 0, strlen($value)-1);
        $dotpos = 0;
      }
    }

    if ($min_precision > 0) {
      if ($dotpos == 0) {
        $value .= ".";
        $dotpos = strlen($value);
      } else $dotpos++;
      $places = strlen($value) - $dotpos;
      if ($min_precision > $places) {
        $value .= str_repeat("0", $min_precision - $places);
      }
    }
    return $value;
  }

  function unscale($value, $scale) {
    if ($scale >= 0) return bcmul($value, bcpow(10, $scale, 0), 0);
    return $value;
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

  // Vending machines aren't implemented yet at Loom.cc,
  // So fake it.
  // Returns four different machines for first byte of $id mod 4 being 0, 1, 2, or 3
  // Machine id of all 0 is invalid.
  function query_vend($id, $key, &$url) {
    $res = array('function' => 'queryvend',
                 'id' => $id,
                 'status' => 'success');
    if ($id == 0) {
      $res['status'] = 'error';
      return;
    }
    $val = 3 & hexdec(substr($id, 0, 1));
    $assets = $this->knownAssets();
    $dollars = $assets['Cartwheel USD'];
    $goldgrams = $assets['Patrick GoldGrams'];
    $tokens = $assets['usage tokens'];
    if ($val == 0) {
      $res['input'] = $this->unscale(30, $dollars['scale']) . ':' .
        $dollars['id'] . ' ' .
        $this->unscale(15, $tokens['scale']) . ':' . $tokens['id'];
      $res['output'] = $this->unscale(1, $goldgrams['scale']) . ':' .
        $goldgrams['id'];
    } else if ($val == 1) {
      $res['input'] = $this->unscale(1, $goldgrams['scale']) . ':' .
        $goldgrams['id'];
      $res['output'] = $this->unscale(30, $dollars['scale']) . ':' .
        $dollars['id'];
    } else if ($val == 2) {
      $res['input'] = $this->unscale(1, $dollars['scale']) . ':' .
        $dollars['id'];
      $res['output'] = $this->unscale(100, $tokens['scale']) . ':' .
        $tokens['id'];
    } else {
      $res['input'] = $this->unscale(100, $tokens['scale']) . ':' .
        $tokens['id'];
      $res['output'] = $this->unscale(1, $dollars['scale']) . ':' .
        $dollars['id'];
    }
    return $res;
  }

  // From https://loom.cc//?function=view&hash=db53fd011eb53ee9c4dbb5a88198cb3e34e1778feeaf3b2a118e619425ecbf6a
  // Returns an array mapping the id and name of each known asset to
  // an array mapping 'id', 'scale', 'precision', and 'name' to that asset's
  // properties.
  function knownAssets() {
    $curs = array(array('id' => '00000000000000000000000000000000',
                        'scale' => 0,
                        'precision' => 0,
                        'name' => 'usage tokens'),
                  array('id' => 'a67b4f153e85d7cc6c846893dac1155c',
                        'scale' => 2,
                        'precision' => 2,
                        'name' => 'Cartwheel USD'),
                  array('id' => '1650f617c024d6441461b2538c6d9540',
                        'scale' => 7,
                        'precision' => 3,
                        'name' => 'GoldNowBanc GoldGrams'),
                  array('id' => 'c9c5ccc9957c3c8c0bec320c16da451a',
                        'scale' => 2,
                        'precision' => 2,
                        'name' => 'GoldNowBanc USD'),
                  array('id' => '40c28abac43d0001fd2aa5b705929852',
                        'scale' => 2,
                        'precision' => 0,
                        'name' => 'MetroPipe Tunneler Pro Tokens 1 Month'),
                  array('id' => 'd43b8563ea999c1f53279b60142be5fe',
                        'scale' => 2,
                        'precision' => 2,
                        'name' => 'Capulin Currency $'),
                  array('id' => 'f0d4c8bd09e9c8c276af9ddd7c514495',
                        'scale' => 7,
                        'precision' => 3,
                        'name' => 'Wontongold Grams'),
                  array('id' => '26ef701a952fe3d641a69bf859db71c2',
                        'scale' => 7,
                        'precision' => 3,
                        'name' => 'Patrick GoldGrams'));
    $res = array();
    foreach ($curs as $cur) {
      $res[$cur['id']] = $cur;
      $res[$cur['name']] = $cur;
    }
    return $res;
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

  function parseFolder($location, $folder) {
    global $client;

    $paren_pos = strpos($folder, "(");
    if ($paren_pos || $folder[0] == '(') {
      $kv = substr($folder, $paren_pos);
      $res = array();
      $keys = $client->parsekv($kv);
      $keytypes = explode(' ', $keys['list_type']);
      $types = array();
      foreach ($keytypes as $keytype) {
        $type = array('name' => $keys["type_name.$keytype"],
                      'id' => $keytype,
                      'min_precision' => blankToZero($keys["type_min_precision.$keytype"]),
                      'scale' => blankToZero($keys["type_scale.$keytype"]));
        //$types[$keytype] = $type;
        $types[$type['name']] = $type;
      }
      ksort($types);
      $res['types'] = $types;
      $keylocs = explode(' ', $keys['list_loc']);
      $locs = array();
      foreach ($keylocs as $keyloc) {
        $name = $keys["loc_name.$keyloc"];
        if ($name != '') {
          if ($keyloc == $location) {
            $folder_name = $name;
          }
          $locs[$name] = $keyloc;
          //$locs[$keyloc] = $name;
        }
      }
      ksort($locs);
      $res['locs'] = $locs;
      $res['name'] = $folder_name;
      $res['loc'] = $location;
      return $res;
    }
    return FALSE;
  }

  // Convert our array() representation of the folder
  // into the string to write into the archive
  // Not used. We let Loom itself munge the folder string.
  function folderArchiveString($folder) {
    $res = "Content-type: loom/folder\n\n(\n";
    $types = "";
    $ids = "";
    foreach ($folder['types'] as $name => $type) {
      $id = $type['id'];
      if ($ids != '') $ids .= ' ';
      $ids .= $id;
      $min_precision = $type['min_precision'];
      $scale = $type['scale'];
      $types .= ":type_name.$id\n=" . $this->quote_cstring($name) . "\n";
      if ($min_precision != '0') {
        $types .= ":type_min_precision.$id\n=$min_precision\n";
      }
      if ($scale != '0') {
        $types .= ":type_scale.$id\n=$scale\n";
      }
    }
    $res .= ":list_type\n=$ids\n";
    $res .= $types;

    $ids = "";
    $locations = "";
    foreach ($folder['locs'] as $name => $location) {
      if ($ids != '') $ids .= ' ';
      $ids .= $location;
      $locations .= ":loc_name.$location\n=" . $this->quote_cstring($name) . "\n";
    }
    $res .= ":list_loc\n=$ids\n";
    $res .= $locations;

    $res .= ")\n";

    return $res;
  }

  // Return the session associated with a folder location
  // Create a new session if one doesn't already exist,
  // buying the location to store it as necessary.
  // Returns false if it can't buy the session location
  function folderSession($folder_location) {
    $loc = $this->leftPadHex(bcxorhex($folder_location, "1"), 32);
    $res = $this->touch_archive($loc, $url);
    if ($res['status'] == 'success') {
      return $res['content'];
    }
    $session = $this->random->random_id();
    $res = $this->buy_archive($session, $folder_location, $url);
    if ($res['status'] != 'success') return false;
    // Probably don't need this, but you never know
    $this->buy_archive($loc, $folder_location, $url);
    $res = $this->write_archive($loc, $folder_location, $session, $url);
    if ($res['status'] != 'success') return false;
    return $session;
  }

  // This doesn't yet test that it worked.
  // I'll wait for Patrick to make a KV-returning version,
  // instead of attempting to parse the returned HTML
  function renameFolderLocation($session, $oldname, $newname) {
    return $this->rawget(array('function' => 'folder_locations',
                               'session' => $session,
                               'old_name' => $oldname,
                               'new_name' => $newname,
                               'save' => 'Save'),
                         $url);
  }

  // This doesn't yet test that it worked.
  // I'll wait for Patrick to make a KV-returning version,
  // instead of attempting to parse the returned HTML
  function newFolderLocation($session, $newname, $newlocation) {
    return $this->rawget(array('function' => 'folder_locations',
                               'session' => $session,
                               'add_location' => '1',
                               'loc' => $newlocation,
                               'name' => $newname,
                               'save' => 'Save'),
                         $url);
  }

  // Logout from Loom, destroying the old session
  function logout($session) {
    return $this->rawget(array('function' => 'folder',
                               'logout' => '1',
                               'session' => $session),
                         $url);
  }

  function parsekv($kv, $recursive=FALSE) {
    $lines = explode("\n", $kv);
    $first = true;
    $res = array();
    $stackptr = 0;
    $stack = array();
    foreach ($lines as $line) {
      $line = trim($line);
      //echo "$line<br>\n";
      if ($first && ($line != '(')) {
        return $res;           // Could throw exception in PHP 5
      }
      $first = false;
      if ($line == ')') {
        if (!$recursive || $stackptr == 0) return $res;
        $child = $res;
        $res = $stack[--$stackptr];
        $key = $stack[--$stackptr];
        $res[$key] = $child;
        //echo "popped: $stackptr<pre>\n"; print_r($res); echo "</pre>\n";
      } else {
        if (substr($line, 0, 1) == ':') $key = substr($line, 1);
        elseif (substr($line, 0, 1) == '=') {
          $value = substr($line, 1);
          if ($recursive && $value == "(") {
            $child = array();
            $stack[$stackptr++] = $key;
            $stack[$stackptr++] = $res;
            //echo "pushed: $stackptr<br>\n";
            $res = $child;
          } else {        
            $value = $this->unquote_cstring($value);
            $res[$key] = $value;
          }
        }
      }
    }
  }

  function array2kv($array, $res="(\n") {
    foreach ($array as $key => $value) {
      $res .= ":$key\n";
      if (is_array($value)) $res = $this->array2kv($value, $res . "=(\n");
      else $res .= "=" . $this->quote_cstring($value) . "\n";
    }
    $res .= ")\n";
    return $res;
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
      if (strlen($hash) > 64) $hash = substr($hash, 1, 64);
    }
    return $this->leftPadHex($hash, 64);
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
    return $this->leftPadHex(bcdechex($location), 32);
  }

  function leftPadHex($hex, $chars) {
    if (strlen($hex) < $chars) {
      $hex = str_repeat("0", $chars - strlen($hex)) . $hex;
    }
    return $hex;
  }

  function xorLocations($l1, $l2) {
    return $this->leftPadHex(bcxorhex($l1, $l2), 32);
  }

  // Returns true if $location is occupied for $type, i.e. if touch() will succeed
  function isLocationOccuppied($type, $location) {
    $res = $this->touch($type, $location, $url);
    return $res['status'] == 'success';
  }

  // Returns true if $location is vacant for $type,
  // i.e. if touch() fails with vacant
  function isLocationVacant($type, $location) {
    $res = $this->touch($type, $location, $url);
    return ($res['status'] == 'fail') && ($res['error_loc'] == 'vacant');
  }

  // Return a random vacant location.
  // If can't find one after 10 tries, return false.
  function randomVacantLocation($type) {
    for ($i=0; $i<10; $i++) {
      $id = $this->random->random_id();
      if ($this->isLocationVacant($type, $id)) return $id;
    }
    return false;
  }

  // Test that an ID is a valid 32-character hex string
  function isValidID($id) {
    return (strlen($id) == 32) && preg_match('/[a-f0-9]{32}/', $id);
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
 * The Original Code is Trubanc.com
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
