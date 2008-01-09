<?php

/*
 * Client to the webapp at http://loom.cc/
 * See https://loom.cc/?function=help&topic=grid_tutorial&mode=advanced
 */

class LoomClient
{
  var $url_prefix;              // Who you gonna call?

  function LoomClient($prefix = 'https://loom.cc/') {
    if (substr($prefix, -1) != '/') $prefix .= '/';
    $this->url_prefix = $prefix;
  }

  // This is all you really need to call
  // The functions below are just syntactic sugar for calling get()
  function get($keys, &$url) {
    $url = $this->url($keys);
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

  function url($keys) {
    $str = $this->url_prefix;
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
        $value = substr($line, 1);
        $res[$key] = $value;
      }
    }
  }

  // This enable a kluge in get() to turn off the protocl warning that
  // results from doing a HTTP GET to http://loom.cc/
  function disable_warnings() {
    $erpt = error_reporting();
    error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
    return $erpt;
  }

  function reenable_warnings($erpt) {
    error_reporting($erpt);
  }
}


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

// Copyright 2007 Bill St. Clair
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
