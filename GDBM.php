<?php

  // GDBM class with live compression

class GDBM {

  var $oldfile;                 // The name of the "Old" file
  var $newfile;                 // The name of the "New" file
  var $oldr;                    // Resource pointer for $oldfile
  var $newr;                    // Resource pointer for $newfile
  var $copycount;               // Number of keys to copy per access
  var $error;                   // error string for last operation

  function GDBM($oldfile, $newfile, $copycount=20, $handler="gdbm") {
    $this->oldfile = $oldfile;
    $this->newfile = $newfile;
    $this->copycount = $copycount;
    $this->handler = $handler;

    $oldr = dba_open($oldfile, 'cl', $handler);

    if (!$oldr) {
      $this->error = "Could not open old database";
      return;
    }
    
    $this->oldr = $oldr;

    if ($newfile == '') {
      $this->newfile = false;
      $this->newr = false;
    } else {
      if (file_exists($newfile)) {
        $this->newr = dba_open($newfile, 'wl', $handler);
      } else $this->newr = false;
    }
  }

  // Get the value for a key.
  // If it's in the new database, return that value.
  // If the deleted key is in the new database, return false.
  // Otherwise, return the value in the old databae.
  function get($key) {
    $value = false;
    if ($this->newr) {
      $this->copysome(true);
      if ($this->newr) $value = dba_fetch($key, $this->newr);
    }
    if (!$value) $value = dba_fetch($key, $this->oldr);
    return $value;
  }

  // Replace or set the value of $key to $value.
  // If $value is blank or false, delete $key from the database
  // Return the new value
  function put($key, $value) {
    if ($value == '' || !$value) {
      // Blank or false value = delete the key
      dba_delete($key, $this->oldr);
      if ($this->newr) {
        dba_delete($key, $this->newr);
        $this->copysome(true);
      }
    } else {
      if ($this->newr) {
        dba_replace($key, $value, $this->newr);
        dba_delete($key, $this->oldr);
        $this->copysome(true);
      } else {
        dba_replace($key, $value, $this->oldr);
      }
    }
    return $value;
  }

  // Create a new database, if there isn't one, and start copying to it
  function startCopying() {
    if (!$this->newr && $this->newfile) {
      $this->newr = dba_open($this->newfile, 'cl', $this->handler);
    }
    return $this->newr;
  }

  // True if we're currently copying old to new
  function isCopying() {
    return $this->newr;
  }

  // Finish copying old to new, delete old, rename new to old, and reopen
  // Do NOT start copying again.
  function finishCopying() {
    while ($this->newr) $this->copysome(false);
  }

  // Close the database(s). Finish copying first if $finish_copying is true
  function close($finish_copying=false) {
    if ($finish_copying) $this->finishCopying();
    if ($this->oldr) {
      dba_close($this->oldr);
      $this->oldr = false;
    }
    if ($this->newr) {
      dba_close($this->newr);
      $this->newr = false;
    }
  }

  // Reopen the database after a close.
  // Does nothing if alredy open
  function reopen() {
    if (!$this->oldr) {
      $this->oldr = dba_open($this->oldfile, 'wl', $this->handler);
      if (!$this->oldr) $this->error = "Could not reopen old file";
    }
    return $this->oldr;
  }

  // Return the message for the last error that happened, and clear it
  function errorMessage() {
    $res = $this->error;
    $this->error = '';
    return $res;
  }

  // Copy $this->copycount keys from old to new database
  function copysome($reopen) {
    if ($this->newr) {
      for ($i=0; $i<$this->copycount; $i++) {
        $key = dba_firstkey($this->oldr);
        if ($key) $this->copyone($key);
        else {
          // We're done copying.
          $this->flipDBs($reopen);
          return;
        }
      }
    }
  }

  // Copy one key from old to new database
  function copyone($key) {
    if ($this->newr) {
      if (!dba_fetch($key, $this->newr)) {
        $value = dba_fetch($key, $this->oldr);
        if ($value) dba_replace($key, $value, $this->newr);
      }
      dba_delete($key, $this->oldr);
    }
  }

  // Delete the old database, and rename new to old
  function flipDBs($reopen) {
    $this->close(false);
    $oldsize = filesize($this->oldfile);
    $newsize = filesize($this->newfile);
    $this->error = "old size: $oldsize, new size: $newsize";
    if (unlink($this->oldfile)) {
      if (rename($this->newfile, $this->oldfile)) {
        if ($reopen) $this->reopen();
      } else $this->error = "Could not rename new file to old file";
    } else $this->error = "Could not unlink old file";
  }

}

// Test code. Uncomment to run.
/*
if (file_exists('old.db')) unlink('old.db');
if (file_exists('new.db')) unlink('new.db');
$db = new GDBM('old.db', 'new.db', 1);
$cnt = 99;
$loops = 10;
for ($i=1; $i<=$cnt; $i++) {
  $db->put($i, $i);
}
$db->startCopying();
for ($j=0; $j<$loops; $j++) {
  for ($i=1; $i<=$cnt; $i++) {
    if (($j % 2) == 1 && ($i % 10) == 3) $db->put($i, '');
    else $db->put($i, 10 * $db->get($i));
    if (!$db->isCopying()) {
      echo "Restarting copying, j=$j, i=$i\n";
      echo "  " . $db->errorMessage() . "\n";
      $db->startCopying();
    }
  }
}
for ($i=1; $i<=$cnt; $i++) {
  echo "$i: " . $db->get($i) . "\n";
  if (!$db->isCopying()) $db->startCopying();
}
$db->close(true);
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
