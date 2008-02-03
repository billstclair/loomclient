<?php

  // GDBM class with live compression

class GDBM {

  var $oldfile;                 // The name of the "Old" file
  var $newfile;                 // The name of hte "New" file
  var $oldr;                    // Resource pointer for $oldfile
  var $newr;                    // Resource pointer for $newfile
  var $copycount;               // Number of keys to copy per access
  var $lastkey;                 // the last key copied
  var $error;                   // error flag for last operation

  var $delete_flag;             // Leading char for deleted entry marker
  var $escape;                  // Escape character for $delete_flag and itself

  function GDBM($oldfile, $newfile, $copycount=10, $handler="gdbm") {
    $this->oldfile = $oldfile;
    $this->newfile = $newfile;
    $this->copycount = $copycount;
    $this->handler = $handler;

    $this->delete_flag = 'X';
    $this->escape = '\\';

    $oldr = dba_open($oldfile, 'cl', $handler);

    if (!$oldr) {
      $this->error = "Could not open old database";
      return;
    }
    
    $this->oldr = $oldr;

    $newr = dba_open($newfile, 'wl', $handler);
    $this->newr = $newr;

    // Fetch where we left off last time
    if ($newr) {
      $this->copykey = dba_fetch($newr, $this->delete_flag);

      // If didn't leave off last time, fetch the first key in the old database
      if (!$this->copykey) $this->copykey = dba_firstkey($oldr);
    }
  }

  // Escape a key value
  function escape($key) {
    $firstchar = substr($key, 0, 1);
    if ($firstchar == $this->delete_flag || $firstchar == $this->escape) {
      $key = $this->escape . $key;
    }
    return $key;
  }

  // Get the value for a key.
  // If it's in the new database, return that value.
  // If the deleted key is in the new database, return false.
  // Otherwise, return the value in the old databae.
  function get($key) {
    if ($key == '') return '';
    $key = $this->escape($key);
    if ($this->newr) {
      $res = dba_fetch($key, $this->newr);
      if ($res) return $res;
      if (dba_fetch($this->delete_flag . $key, $this->newr)) return false;
    }
    return dba_fetch($key, $this->oldr);
  }

  // Replace or set the value of $key to $value.
  // If $value is blank or false, delete $key from the database
  // Return the new value
  function put($key, $value) {
    if ($key == '') return '';
    $key = $this->escape($key);
    if ($value == '' || !$value) {
      // Blank or false value = delete the key
      if ($this->newr) {
        dba_delete($key, $this->newr);
        dba_replace($this->delete_flag . $key, '1',  $this->newr);
        $this->copysome();
      } else {
        dba_delete($key, $this->oldr);
      }
    } else {
      if ($this->newr) {
        dba_delete($this->delete_flag . $key, $this->newr);
        dba_replace($key, $value, $this->newr);
        $this->copysome();
      } else {
        dba_replace($key, $value, $this->oldr);
      }
    }
    return $value;
  }

  function startCopying() {
  }

  function copysome() {
  }

  function isCopying() {
  }

  function finishCopying() {
  }

  function close() {
  }

}



?>