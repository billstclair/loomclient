<?php

require_once "Socket.php";
require_once "LoomClient.php";
require_once "Cipher.php";
require_once "bcbitwise.php";

  /*
   * iPhone interface to the Loom folder.
   * Don't run this unencrypted.
   * See http://www.whoopis.com/howtos/apache-rewrite.html
   * for unstructions on setting up a .htaccess file to rewrite
   * http://... to https://...
   */

function mq($x) {
  if (get_magic_quotes_gpc()) return stripslashes($x);
  else return $x;
}

$passphrase = mq($_POST['passphrase']);
$session = mq($_POST['session']);
$qty = mq($_POST['zip']);
$type = mq($_POST['type']);
$location = mq($_POST['location']);
$folderkv = mq($_POST['folderkv']);
$valueskv = mq($_POST['valueskv']);
$take = mq($_POST['take']);
$give = mq($_POST['give']);
$page = mq($_POST['page']);
$greendot = mq($_POST['greendot']);
$showfolder = mq($_POST['showfolder']);
$newname = mq($_POST['newname']);
$oldname = mq($_POST['oldname']);
$newlocation = mq($_POST['newlocation']);
$savename = mq($_POST['savename']);
$delete = mq($_POST['delete']);
$add_location = mq($_POST['add_location']);
$commit = mq($_POST['commit']);

$client = new LoomClient();

// Redefine $encrypt_key if you want to use one.
// It should be a 32-character hex string, as generated
// by the grid-tutotial.php "Tools" page.
$client->disable_warnings();
$encrypt_key = '';
include "ip-config.php";
$client->reenable_warnings();

$cipher = '';
$session_cipher = '';

if ($encrypt_key != '') {
  makeCiphers();
  if ($session_cipher != '') {
    if ($folderkv != '') {
      $folderkv = $session_cipher->decrypthex($folderkv);
    }
    if ($valueskv != '') {
      $valueskv = $session_cipher->decrypthex($valueskv);
    }
  }
}

$onload = 'zip';
$folder = '';
$values = '';
$message = '';

if ($page == 'refresh') {
   $folderkv = '';
   $valueskv = '';
   $page = 'main';
} else {
  if ($folderkv != '') $folder = $client->parsekv($folderkv, TRUE);
  if ($valueskv != '') $values = $client->parsekv($valueskv, TRUE);
}

if (($session == '' && $passphrase == '') || !login()) {
  $onload = 'passphrase';
  $page = 'login';
} else {
   if ($values == '') {
    $values = scanFolder($folder);
    $valueskv = $client->array2kv($values);
  }
}

$title = "Loom Folder";

if ($page == 'main') doMain();
elseif ($page == 'locations') doLocations();
elseif ($page == 'add_location') doAddLocation();
elseif ($page == 'logout') doLogout();

drawHead();

if ($page == 'login') drawLogin();
elseif ($page == 'main') drawMain();
elseif ($page == 'locations') drawLocations();
elseif ($page == 'add_location') drawAddLocation();

drawTail();

function doMain() {
  global $qty, $type, $location, $take, $give;
  global $client, $folder, $folder_loc, $folder_name;
  global $values, $valueskv;
  global $message;

  $id = '';

  if ($type != '-- choose asset --') {
    $t = $folder['types'][$type];
    if ($t) {
      $id = $t['id'];
      $min_precision = $t['min_precision'];
      if ($min_precision == '') $min_precision = 0;
      $scale = $t['scale'];
      if ($scale == '') $scale = 0;
    }
  }
  if ($location != '-- choose location --') {
    $loc = $folder['locs'][$location];
  }
  if ($id != '' && $loc != '') {
    if ($scale != '') $count = bcmul($qty, bcpow(10, $scale), 0);
    $transferred = TRUE;
    if ($count != '' && $id != '' && $loc != '') {
      if ($take != '') {
        $client->buy($id, $folder_loc, $folder_loc, $url);
        $res = $client->move($id, $count, $loc, $folder_loc, $url);
        $loc_orig = $location;
        $loc_dest = $folder_name;
      } else if ($give != '') {
        $client->buy($id, $loc, $folder_loc, $url);
        $res = $client->move($id, $count, $folder_loc, $loc, $url);
        $loc_dest = $location;
        $loc_orig = $folder_name;
      } else $transferred = FALSE;
      if ($transferred) {
        $status = $res['status'];
        if ($status == 'success') {
          $value_orig = $client->applyScale($res['value_orig'], $min_precision, $scale);
          $value_dest = $client->applyScale($res['value_dest'], $min_precision, $scale);
          $values[$loc_orig][$type] = $value_orig;
          ksort($values[$loc_orig]);
          $values[$loc_dest][$type] = $value_dest;
          ksort($values[$loc_dest]);
          $valueskv = $client->array2kv($values);
        } else $message = "Insufficient funds";
      }
    }
  }
}

function doLocations() {
  // Need to investigate how to delete.
  // The "session" is an archive location containing the folder location

  global $client, $folder, $values, $valueskv;
  global $newname, $oldname;
  global $savename, $delete, $add_location;
  global $session;
  global $message, $title, $onload, $page;

  $onload = 'newname';
  $title = 'Loom Locations';

  if ($savename != '') {
    if ($newname == $oldname) return;
    if ($folder['locs'][$newname] != '') {
      $message = "Duplicate Location Name";
      return;
    }
    $res = $client->renameFolderLocation($session, $oldname, $newname);
    //echo $res;
    fullRefresh();
  }

  else if ($add_location != '') {
    $title = 'Loom Add Location';
    $page = 'add_location';
  }

  else refreshFolder();

}

function makeCiphers() {
  global $cipher, $encrypt_key, $session, $session_cipher;
  if ($encrypt_key != '') {
    if ($cipher == '') {
      $cipher = new Cipher($encrypt_key);
      if ($session != '') $session = $cipher->decrypthex($session);
    }
    if ($session_cipher == '' && $session != '') {
      $session_key = bcxorhex($session, $encrypt_key);
      $session_cipher = new Cipher($session_key);
    }
  }
}

function doAddLocation() {
  global $session, $commit, $page, $newname, $newlocation;
  global $client, $message, $folder;

  $page = 'locations';

  if ($commit != 'commit') $message = 'Cancelled';
  else if ($newname == '') $message = 'Blank name';
  else if ($folder['locs'][$newname] != '') $message = "Name already exists";
  else if ($newlocation != '' && !$client->isValidID($newlocation)) {
    $message = 'Invalid location ID';
  } else {
    if ($newlocation == '') $newlocation = $client->random->random_id();
    $client->newFolderLocation($session, $newname, $newlocation);
    fullRefresh();
    $message = "Location created: $newname";
  }
}

function doLogout() {
  global $page, $folderkv, $valueskv;
  global $client, $session;

  $page = 'login';
  $folderkv = '';
  $valueskv = '';
  $client->logout($session);
}

function drawHead() {
  global $title, $onload;
?>
<html>
<head>
<meta name="viewport" content="width=device-width" user-scalable="no" minimum-scale="1.0" maximum-scale="1.0"/>
<title><? echo $title; ?></title>

<script language="JavaScript">
function submitPage(page) {
  document.forms["mainform"].page.value = page;
  document.mainform.submit();
}

function greenDot(greendot) {
  document.forms["mainform"].greendot.value = greendot;
  document.mainform.submit();
}

function hideAddressbar() {
  window.scrollTo(0, 1);
}

function doOnLoad(selected) {
  selected.select();
  setTimeout(hideAddressbar, 250);
}

<? additionalHTMLScripts(); ?>

</script>

<style type="text/css">
body { font-family: verdana, arial, sans-serif; font-size: 12pt }
div { font-size:12pt }
p { font-size:12pt }
h1 { font-size:14pt }
h2 { font-size:12pt }
h3 { font-size:10pt }
td { font-size:12pt }
ul { font-size:12pt }
li { padding-bottom: 7px }
pre { font-family: verdana, arial, sans-serif; }
A:link, A:visited { color:blue; text-decoration:none }
A:hover { color:blue; text-decoration:underline }
A:active { color:#FAD805; text-decoration:underline }
.tt { font-family: Courier; font-size:10pt }
.mono { font-family: monospace; font-size: 11pt }
.large_mono { font-family: monospace; font-size: 10pt }
.giant_mono { font-family: monospace; font-size: 14pt }
.tiny_mono { font-family: monospace; font-size: 6pt }
.normal { font-size:10pt }
.smaller { font-size:6pt }
.small { font-size:8pt }
.large { font-size:12pt }
.alarm { color:red; font-weight: bold }
.focus_value { background-color:#DDDDDD }
.color_heading { margin-top:12px; padding:1px; background-color:#DDDDDD; width:100% }
A.label_link { font-weight:bold; }
A.highlight_link { font-weight:bold; }
A.cancel { background-color:#FFDDDD }
A.plain:link, A.plain:visited { color:black; text-decoration:none }
A.plain:hover { color:blue; text-decoration:underline }
A.plain:active { color:#FAD805; text-decoration:underline }
A.name_dot { font-size:16pt; font-weight:bold; color:green; }
</style>

</head>
<body onload="doOnLoad(document.forms[0].<? echo $onload; ?>)">
<table width="320px">
<tr><td>
<?
}

function additionalHTMLScripts() {
  global $page;

  if ($page == 'add_location') {

?>
function Do0(form) { form.newlocation.value  = form.newlocation.value +"0"  ;}
function Do1(form) { form.newlocation.value  = form.newlocation.value +"1"  ;}
function Do2(form) { form.newlocation.value  = form.newlocation.value +"2"  ;}
function Do3(form) { form.newlocation.value  = form.newlocation.value +"3"  ;}
function Do4(form) { form.newlocation.value  = form.newlocation.value +"4"  ;}
function Do5(form) { form.newlocation.value  = form.newlocation.value +"5"  ;}
function Do6(form) { form.newlocation.value  = form.newlocation.value +"6"  ;}
function Do7(form) { form.newlocation.value  = form.newlocation.value +"7"  ;}
function Do8(form) { form.newlocation.value  = form.newlocation.value +"8"  ;}
function Do9(form) { form.newlocation.value  = form.newlocation.value +"9"  ;}
function DoA(form) { form.newlocation.value  = form.newlocation.value +"a"  ;}
function DoB(form) { form.newlocation.value  = form.newlocation.value +"b"  ;}
function DoC(form) { form.newlocation.value  = form.newlocation.value +"c"  ;}
function DoD(form) { form.newlocation.value  = form.newlocation.value +"d"  ;}
function DoE(form) { form.newlocation.value  = form.newlocation.value +"e"  ;}
function DoF(form) { form.newlocation.value  = form.newlocation.value +"f"  ;}
function DoBksp(form) 
{ var T  = form.newlocation.value; 
  var L  = T.length; 
  var T2  = T.substr(0,L-1); 
  form.newlocation.value  = T2;
}
function DoClr(form)  { form.newlocation.value = ""; }
function DoEnter(form)  { form.submit(); }
function DoCancel(form)  {
  form.commit.value = 'Cancel';
  form.submit();
}
function submitOnRet(e, form) {
  var keynum;
  var keychar;
  if (window.event) keynum = e.keyCode; // IE
  else if (e.which) keynum = e.which;   // Netscape/Firefox/Opera
  keychar = String.fromCharCode(keynum);
  if (keychar != "\n" && keychar != "\r") return true;
  DoEnter(form);
  return false;
}
</script>
<?
  }
}

function drawTail() {
?></td></tr>
</table>
</body>
</html>
<?
}

function drawLogin() {

  $host = $_SERVER["HTTP_HOST"];
  if (!$host || $host == '') $host = $_SERVER["SERVER_NAME"];

?>
<p>Welcome to
<a href="https://loom.cc/">Loom</a> for iPhone. Note that in order to use this
confidently, you must trust that the PHP scripts at <? echo $host; ?> do
not steal your passphrase. I promise that unless somebody hacks my
site, the code running is what you can download from
<a href="./">here</a>, but don't trust that unless you know
me.</p>
<form method="post" action="" autocomplete="off">
<input type="hidden" name="page" value="main"/>
<table width="99%">
<tr>
<td>Passphrase:</td>
<td><input type="password" name="passphrase" size="35" /></td>
</tr>
<tr>
<td></td>
<td><input type="submit" name="login" text="Login" /></td>
</tr>
</table>
<p>Because my SSL certificate is from
<a href="http://www.cacert.org/">CAcert.org</a>, a free certificate authority (CA), you'll see a warning message on your iPhone when you come here: "The certificate for this website is invalid..." Click the "Continue" button to go ahead. Your regular browser should allow you to easily add the CA certificate to eliminate warnings, but the iPhone browser does not.</p>

<p>One way to use this, without giving me the keys to your kingdom, is to create a separate "Mobile" folder in Loom. Transfer assets that you think you might need on the road to a drop-point shared between your main folder and the "Mobile" folder, and only aim this page at the "Mobile" folder. You can always login to your main folder directly via
<a href="https://loom.cc/">
loom.cc</a>, if you need something there. The regular interface isn't as convenient as this one, but it works on the mobile browser.</p>
</form>
<?
}

function hsc($text) {
  return htmlspecialchars($text);
}

function hiddenValue($name) {
   eval('global $' . $name . ';');
   echo '<input type="hidden" name="' . $name .
        '" value="' . hsc(eval('return $' . $name . ';')) . '"/>' . "\n";
}

function drawValues($name, $typevalues) {
  if (is_array($typevalues)) {
    $str = '';
    foreach ($typevalues as $type => $value) {
      if ($value != 0) {
        if ($str == '') {
          $str .= "<b>$name</b>\n";
          $str .= '<table border="0"><tr><td width="50px">&nbsp;</td><td>';
          $str .= '<table border="0">' . "\n";
        }
        $str .= '<tr><td align="right">' . $value . "<td><td>$type</td></tr>\n";
      }
    }
    if ($str != '') {
      echo $str;
      echo "</table></td></tr></table>\n";
    }
  }
}

function writeSessionInfo() {
  global $cipher, $session_cipher;
  global $session, $folderkv, $valueskv;

  $enc_session = $session;
  $enc_folderkv = $folderkv;
  $enc_valueskv = $valueskv;

  if ($cipher) $enc_session = $cipher->encrypt2hex($enc_session);
  if ($session_cipher) {
    $enc_folderkv = $session_cipher->encrypt2hex($enc_folderkv);
    $enc_valueskv = $session_cipher->encrypt2hex($enc_valueskv);
  }
  echo '<input type="hidden" name="session" value="' . hsc($enc_session) . '"/>' . "\n";
  echo '<input type="hidden" name="folderkv" value="' . hsc($enc_folderkv) . '"/>' . "\n";
  echo '<input type="hidden" name="valueskv" value="' . hsc($enc_valueskv) . '"/>' . "\n";
}

function drawMain() {
  global $session, $folder, $values, $folder_name;
  global $qty, $type, $location;
  global $message;

  $page = 'main';

?>
<table border="0" width="99%" cellpadding="3">
<tr>
<td colspan="2" style="background-color: #c0c0c0; text-align: center;"><span style="font-weight: normal; font-size: 110%;"><a href="javascript:submitPage('refresh');">Refresh</a>
&nbsp;
<a href="javascript:submitPage('locations');">Locations</a>
&nbsp;
<a href="javascript:submitPage('logout');">Logout</a>
</span></td>
</tr>
</table>
<?
  drawValues($folder_name, $values[$folder_name]);
?>
<table border="0" width="99%">
<form name="mainform" method="post" action="" autocomplete="off">
<?
writeSessionInfo();
hiddenValue('page');
?>
<tr>
<td align="right">Qty:</td>
<td><input style="font-size: 12pt;" type="text" size="25" name="zip" value="<? echo $qty; ?>" style="text-align:right;"></td>
</tr><tr>
<td></td>
<td>
<select name="type" style="font-size: 10pt;">
<option value="">-- choose asset --</option>
<?
foreach ($folder['types'] as $typename => $typearray) {
  echo '<option value="' . hsc($typename) . '"';
  if ($type == $typename) echo ' selected="selected"';
  echo '>' . hsc($typename) . "</option>\n";
}
?>
</select>
</td>
</tr><tr>
<td></td>
<td>
<select name="location" style="font-size: 10pt;">
<option value="">-- choose location --</option>
<?
foreach($values as $loc => $value) {
  if ($loc != $folder_name) {
    echo '<option value="' . hsc($loc) . '"';
    if ($loc == $location) echo ' selected="selected"';
    echo '>' . hsc($loc) . "</option>\n";
  }
}
?>
</select>
</td>
</tr><tr>
<td></td>
<td>
<input style="font-size: 10pt;" type="submit" name="take" value="Take"/>
<input style="font-size: 10pt;" type="submit" name="give" value="Give"/>
</td>
</tr>
<?
  if ($message != '') {
    echo '<tr><td></td><td class="alarm">' . hsc($message) . "</td></tr>\n";
  }
?></table>
</form>
<?
  foreach ($values as $name => $typevalues) {
    if ($name != $folder_name) {
      drawValues($name, $typevalues);
    }
  }
?>
<?
}

function drawLocations() {
  global $session, $folder, $values, $folder_name;
  global $qty, $type, $location, $greendot;
  global $message;

  $page = 'locations';
?>
<table border="0" width="99%" cellpadding="3">
<tr>
<td colspan="2" style="background-color: #c0c0c0; text-align: center;"><span style="font-weight: normal; font-size: 110%;"><a href="javascript:submitPage('main');">Folder</a>
&nbsp;
<a href="javascript:submitPage('locations');"><b>Locations</b></a>
&nbsp;
<a href="javascript:submitPage('logout');">Logout</a>
</span></td>
</tr>
</table>
<table border="0" width="99%">
<form name="mainform" method="post" action="" autocomplete="off">
<?
  writeSessionInfo();
  echo '<input type="hidden" name="zip" value="' . $qty . '"/>' . "\n";
  hiddenValue('type');
  hiddenValue('location');
  hiddenValue('newname');
  hiddenValue('newlocation');
  hiddenValue('page');
?>
<input type="hidden" name="greendot" value=""/>
<table>
<tr>
<td valign="top"><a class=name_dot href="javascript: greenDot('<? echo $folder_name; ?>')" title="Edit Name">&nbsp;&bull;&nbsp;</a></td>
<?
  echo "<td>";
  if ($greendot != $folder_name) echo "<b>$folder_name</b>";
  else {
    echo '<input style="font-size: 12pt;" type="text" size="25" name="newname" value="' . hsc($folder_name) . '"/><br/>' . "\n";
    echo '<input type="hidden" name="oldname" value="' . hsc($folder_name) . '"/>' . "\n";
    echo '<input type="submit" name="savename" value="Save"/>' . "\n";
    echo '<input type="submit" name="cancel" value="Cancel"/>' . "\n";
  }
  echo "</td></tr>\n";
  $locs = $folder['locs'];
  foreach ($locs as $name => $loc) {
    if ($name != $folder_name) {
?>
<tr>
<td valign="top"><a class=name_dot href="javascript: greenDot('<? echo $name; ?>')" title="Edit Name or Delete Folder">&nbsp;&bull;&nbsp;</a></td>
<?
      echo "<td>";
      if ($greendot != $name) echo $name;
      else {
        echo '<input style="font-size: 12pt;" type="text" size="25" name="newname" value="' . hsc($name) . '"/><br/>' . "\n";
        echo '<input type="hidden" name="oldname" value="' . hsc($name) . '"/>' . "\n";
        echo '<input type="submit" name="savename" value="Save"/>' . "\n";
        echo '<input type="submit" name="cancel" value="Cancel"/>' . "\n";
        echo "&nbsp;&nbsp;";
        echo '<input type="submit" disabled name="delete" value="Delete..."/><br/>' . "\n";
        echo '</td></tr><tr><td colspan="2"><span class="mono">' . "<b>$loc</b></span><br/>\n";
      }
      echo "</td></tr>\n";
    }
  }
  if ($message != '') {
    echo '<tr><td colspan="2" class="alarm">' . hsc($message) . "</td></tr>\n";
  }
?>
<tr><td colspan="2"><input type="submit" name="add_location" value="Add Location"/></td></tr>
</table>

<p>Click the green dot by a folder name to see its hex value, or to rename or delete it.</p>
<?
}

function drawAddLocation() {
  global $newname, $newlocation;
  global $commit;

  $commit = 'commit';

?>
<form name="entryForm" method="post" action=""><b>
<?
writeSessionInfo();
hiddenValue('page');
hiddenValue('commit');
?>
Enter name:<br/>
<input type="text" name="newname" style="font-size: 14pt;" size="25" value="<? echo $newname; ?>" onkeydown="submitOnRet(event, this.form)"/><br/>
Enter Location, blank for random:<br/>
<input type="text" style="font-size: 11pt;" size="32" name="newlocation" value="<? echo $newlocation; ?>" onkeydown="submitOnRet(event, this.form)"/>
<table>
<tr>
<td><input type="button" style="font-size: 18pt;" value="7" onClick="Do7(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="8" onClick="Do8(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="9" onClick="Do9(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="F" onClick="DoF(this.form)"></td>
</tr>
<tr>
<td><input type="button" style="font-size: 18pt;" value="4" onClick="Do4(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="5" onClick="Do5(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="6" onClick="Do6(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="E" onClick="DoE(this.form)"></td>
</tr>
<tr>
<td><input type="button" style="font-size: 18pt;" value="1" onClick="Do1(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="2" onClick="Do2(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="3" onClick="Do3(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="D" onClick="DoD(this.form)"></td>
</tr>
<tr>
<td><input type="button" style="font-size: 18pt;" value="0" onClick="Do0(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="A" onClick="DoA(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="B" onClick="DoB(this.form)"></td>
<td><input type="button" style="font-size: 18pt;" value="C" onClick="DoC(this.form)"></td>
</tr>
<tr>
</table>
<input type="button" style="font-size: 18pt;" value="Del" onClick="DoBksp(this.form)">
<input type="button" style="font-size: 18pt;" value="Clr" onClick="DoClr(this.form)">
<input type="button" style="font-size: 18pt;" value="Enter" onClick="DoEnter(this.form)"><br/>
<input type="button" style="font-size: 18pt;" value="Cancel" onClick="DoCancel(this.form)"><br/>
</b></form>
<?
}

function login() {
  global $folder, $folder_name, $folder_loc;

  if ($folder == '') return refreshFolder();
  $folder_name = $folder['name'];
  $folder_loc = $folder['loc'];
  return TRUE;
}

function refreshFolder() {
  global $client, $passphrase, $session;
  global $folder, $folderkv, $folder_name, $folder_loc;

  $loc = FALSE;

  if ($session != '') {
    $res = $client->touch_archive($session, $url);
    if ($res['status'] == 'success') $loc = $res['content'];
  }

  if (!$loc) {
    if ($passphrase != '') {
      $loc = $client->hash2location($client->sha256($passphrase));
      $session = $client->folderSession($loc);
      makeCiphers();
    } else return FALSE;
  }

  $res = $client->touch_archive($loc, $url);
  if ($res['status'] != 'success') return FALSE;
  $folder = $client->parseFolder($loc, $res['content']);
  $folderkv = $client->array2kv($folder);

  $folder_name = $folder['name'];
  $folder_loc = $folder['loc'];
  return TRUE;
}

function fullRefresh() {
  global $client, $folder, $values, $valueskv;

  refreshFolder();
  $values = scanFolder($folder);
  $valueskv = $client->array2kv($values);
}

function blankToZero($x) {
  if ($x == '') return 0;
  return $x;
}

// This is currently n-squared for pretty big n, since it has to do
// a web call for each location/type pair.
// Patrick has promised a scan() function in the web API that would
// allow it to be done with a single call.
/* Now uses Patrick's scan() API call
function scanFolder($folder) {
  global $client;

  $types = $folder['types'];
  $locs = $folder['locs'];
  $values = array();
  foreach ($locs as $locname => $loc) {
    $loc_values = array();
    foreach ($types as $typename => $type) {
      $id = $type['id'];
      $min_precision = $type['min_precision'];
      $scale = $type['scale'];
      $res = $client->touch($id, $loc, $url);
      if ($res['status'] == 'success') {
        $value = $client->applyScale($res['value'], $min_precision, $scale);
        $loc_values[$typename] = $value;
      }
    }
    $values[$locname] = $loc_values;
  }
  return $values;
}
*/

function scanFolder($folder) {
  global $client;

  return $client->namedScan($folder['locs'], $folder['types'], FALSE, $url);
}


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
