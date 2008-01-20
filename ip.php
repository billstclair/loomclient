<?php

require_once "Socket.php";
require_once "LoomClient.php";

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
$qty = mq($_POST['zip']);
$type = mq($_POST['type']);
$location = mq($_POST['location']);
$folderkv = mq($_POST['folderkv']);
$valueskv = mq($_POST['valueskv']);
$take = mq($_POST['take']);
$give = mq($_POST['give']);

$client = new LoomClient();

$onload = 'qty';
$page = 'main';
$folder = '';
$values = '';
$message = '';

if ($folderkv != '') $folder = $client->parsekv($folderkv, TRUE);
if ($valueskv != '') $values = $client->parsekv($valueskv, TRUE);

if ($passphrase == '' || !login()) {
  $onload = 'passphrase';
  $page = 'login';
} else if ($values == '') {
  $values = scanFolder($folder);
  $valueskv = $client->array2kv($values);
}

?><html>
<head>
<meta name="viewport" content="width=device-width" user-scalable="no" minimum-scale="1.0" maximum-scale="1.0"/>
<title>Loom Folder</title>
</head>
<body onload="document.forms[0].<? echo $onload; ?>.focus()">
<?

if ($page == 'main') doMain();

if ($page == 'login') drawLogin();
elseif ($page == 'main') drawMain();

?></body>
</html>
<?

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
        $res = $client->move($id, $count, $loc, $folder_loc, $url);
        $loc_orig = $location;
        $loc_dest = $folder_name;
      } else if ($give != '') {
        $res = $client->move($id, $count, $folder_loc, $loc, $url);
        $loc_dest = $location;
        $loc_orig = $folder_name;
      } else $transferred = FALSE;
      if ($transferred) {
        $status = $res['status'];
        if ($status == 'success') {
          $value_orig = applyScale($res['value_orig'], $min_precision, $scale);
          $value_dest = applyScale($res['value_dest'], $min_precision, $scale);
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

function drawLogin() {

?>
<p>Welcome to Loom for iPhone. Note that in order to use this
confidently, you must trust that the PHP scripts at billstclair.com do
not steal your passphrase. I promise that unless somebody hacks my
site, the code running is what you can download from
<a href="index.html">here</a>, but don't trust that unless you know
me.</p>
<form method="post" action="" autocomplete="off">
<table width="320px">
<tr>
<td>Passphrase:</td>
<td><input type="password" name="passphrase" size="35" /></td>
</tr>
<tr>
<td></td>
<td><input type="submit" name="login" text="Login" /></td>
</tr>
</table>
<p>It can take 10 or 20 seconds to read the initial values of all your
folder's locations, more if you have lots of locations and asset
types. Please be patient. After the initial load, things should be
pretty quick.</p>
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

function drawMain() {
  global $passphrase, $folder, $values, $folder_name;
  global $qty, $type, $location;
  global $message;

  drawValues($folder_name, $values[$folder_name]);
?>
<form method="post" action="" autocomplete="off">
<?
hiddenValue('passphrase'); echo "\n";
hiddenValue('folderkv'); echo "\n";
hiddenValue('valueskv');
?>
<table border="0" width="320px">
<tr>
<td align="right">Qty:</td>
<td><input type="text" size="15" name="zip" value="<? echo $qty; ?>" style="text-align:right;"></td>
</tr><tr>
<td></td>
<td>
<select name="type">
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
<select name="location">
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
<input type="submit" name="take" value="Take"/>
<input type="submit" name="give" value="Give"/>
<input type="submit" name="refresh" value="Refresh"/>
</td>
</tr>
<?
  if ($message != '') {
    echo '<tr><td></td><td><span style="color: red; font-weight: bold;">' . hsc($message) . "</span></td></tr>\n";
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

function login() {
  global $client, $passphrase, $folder, $folderkv, $folder_name, $folder_loc;

  if ($folder == '') {
    $loc = $client->hash2location($client->sha256($passphrase));
    $res = $client->touch_archive($loc, $url);
    if ($res['status'] != 'success') return FALSE;
    $folder = parseFolder($loc, $res['content']);
    $folderkv = $client->array2kv($folder);
  }
  $folder_name = $folder['name'];
  $folder_loc = $folder['loc'];
  return TRUE;
}

function blankToZero($x) {
  if ($x == '') return 0;
  return $x;
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

function applyScale($value, $min_precision, $scale) {
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

// This is currently n-squared for pretty big n, since it has to do
// a web call for each location/type pair.
// Patrick has promised a scan() function in the web API that would
// allow it to be done with a single call.
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
        $value = applyScale($res['value'], $min_precision, $scale);
        $loc_values[$typename] = $value;
      }
    }
    $values[$locname] = $loc_values;
  }
  return $values;
}
