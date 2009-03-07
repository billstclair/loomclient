<?php
require_once "LoomClient.php";
require_once "LoomRandom.php";
require_once "bcbitwise.php";
//require_once "Diceware.php";   // required below only when used (it's big)

function mq($x) {
  if (get_magic_quotes_gpc()) return stripslashes($x);
  else return $x;
}

$page = mq($_GET['page']);
if ($page == '') $page = 'grid';
$common_type = mq($_POST['common_type']);
$default_server = 'https://loom.cc/';
$loom_server = mq($_REQUEST['loom_server']);
if ($loom_server == '') $loom_server = $default_server;
if (substr($loom_server, -1) != '/') $loom_server .= '/';
$buy_loc = mq($_POST['buy_loc']);
$buy_usage = mq($_POST['buy_usage']);
$issuer_orig = mq($_POST['issuer_orig']);
$issuer_dest = mq($_POST['issuer_dest']);
$touch_loc = mq($_POST['touch_loc']);
$look_hash = mq($_POST['look_hash']);
$move_qty = mq($_POST['move_qty']);
$move_orig = mq($_POST['move_orig']);
$move_dest = mq($_POST['move_dest']);
$content = mq($_POST['content']);
$id = mq($_POST['id']);
$passphrase = mq($_POST['passphrase']);
$idhash = mq($_POST['idhash']);

function maybe_echo_server($q) {
  global $loom_server, $default_server;
  if ($loom_server != $default_server) {
    echo $q . 'loom_server=' . urlencode($loom_server);
  }
}
?>
<html>
<head>
<title>Loom Grid &amp; Archive Tutorials in PHP</title>
<link rel="shortcut icon" href="krugerand.png"/>
<style type="text/css">
body { font-family: verdana, arial, sans-serif; font-size: 10pt }
div { font-size:10pt }
p { font-size:10pt }
h1 { font-size:12pt }
h2 { font-size:10pt }
h3 { font-size:9pt }
td { font-size:10pt }
ul { font-size:10pt }
li { padding-bottom: 7px }
pre { font-family: verdana, arial, sans-serif; }
A:link, A:visited { color:blue; text-decoration:none }
A:hover { color:blue; text-decoration:underline }
A:active { color:#FAD805; text-decoration:underline }
.tt { font-family: Courier; font-size:10pt }
.mono { font-family: monospace; font-size: 8pt }
.large_mono { font-family: monospace; font-size: 10pt }
.giant_mono { font-family: monospace; font-size: 14pt }
.tiny_mono { font-family: monospace; font-size: 6pt }
.normal { font-size:10pt }
.smaller { font-size:6pt }
.small { font-size:8pt }
.large { font-size:12pt }
.alarm { color:red }
.focus_value { background-color:#DDDDDD }
.color_heading { margin-top:12px; padding:1px; background-color:#DDDDDD; width:100% }
A.label_link { font-weight:bold; }
A.highlight_link { font-weight:bold; }
A.cancel { background-color:#FFDDDD }
A.plain:link, A.plain:visited { color:black; text-decoration:none }
A.plain:hover { color:blue; text-decoration:underline }
A.plain:active { color:#FAD805; text-decoration:underline }
A.name_dot { font-size:14pt; font-weight:bold; color:green; }
</style></head>
<body>
<!--
<pre>
<?php
 print_r($_POST);
?>
</pre>
-->
This is a PHP translation of the Loom.cc
<?php
if ($page == 'grid') {
  echo '<a href="https://loom.cc/?function=grid_tutorial&mode=advanced">grid tutorial</a>.';
 } elseif ($page == 'archive') {
   echo '<a href="https://loom.cc/?function=archive_tutorial&mode=advanced">archive tutorial</a>.';
 } else {
  echo 'Tools page (login required).';
 }
?>
 Note that if you enter any real locations containing valuable assets in this page, those locations are being transferred through my web site to loom.cc. All trasmissions are encrypted, so it's unlikely that anybody can steal your valuable information en-route, but you ARE trusting me to not snarf your locations with my PHP code. I promise that unless somebody hacks my site, the code that is running is the code I've posted at the link below, which does NOT save your information anywhere, just passes it on to loom.cc. But if you don't trust me, don't give away anything important.
<p>
Download source at
<a href="../loomclient.tar.gz">loomclient.tar.gz</a>
<p>
<hr>
<a href="index.html">Loom Index</a> |
<a href="grid-tutorial.php<? maybe_echo_server('?'); ?>">
<?php
if ($page == 'grid') {
  echo '<b>Grid</b>';
 } else echo 'Grid';
?>
</a> |
<a href="grid-tutorial.php?page=archive<? maybe_echo_server('&'); ?>">
<?php
if ($page == 'archive') {
  echo '<b>Archive</b>';
 } else echo 'Archive';
?>
</a> |
<a href="grid-tutorial.php?page=tools<? maybe_echo_server('&'); ?>">
<?php
if ($page == 'tools') {
  echo '<b>Tools</b>';
 } else echo 'Tools';
?>
</a>
<hr>
<?php
// Process the post

$client = new LoomClient($loom_server);
$res = '';

if ($page == 'grid') {
  $buy_message = '';
  $issuer_message = '';
  $touch_message = '';
  $look_message = '';
  $move_message = '';
  if ($_POST['buy'] != '') {
    $res = $client->buy($common_type, $buy_loc, $buy_usage, &$url);
    checkResult($res, 'usage_balance', 'error_loc', &$buy_message);
  } elseif ($_POST['sell'] != '') {
    $res = $client->sell($common_type, $buy_loc, $buy_usage, &$url);
    checkResult($res, 'usage_balance', 'error_loc', &$buy_message);
  } elseif ($_POST['issuer'] != '') {
    $res = $client->issuer($common_type, $issuer_orig, $issuer_dest, &$url);
    checkResult($res, 'status', 'status', &$issuer_message);
  } elseif ($_POST['touch'] != '') {
    $res = $client->touch($common_type, $touch_loc, &$url);
    checkResult($res, 'value', 'error_loc', &$touch_message);
  } elseif ($_POST['look'] != '') {
    $res = $client->look($common_type, $look_hash, &$url);
    checkResult($res, 'value', 'error_loc', &$look_message);
  } elseif ($_POST['move'] != '') {
    $res = $client->move($common_type, $move_qty, $move_orig, $move_dest, &$url);
    checkResult($res, 'value_dest', 'error_qty', &$move_message);
  } elseif ($_POST['move_back'] != '') {
    $res = $client->move($common_type, $move_qty, $move_dest, $move_orig, &$url);
    checkResult($res, 'value_orig', 'error_qty', &$move_message);
  }
} else if ($page == 'archive') {
  if ($_POST['look_archive'] != '') {
    $res = $client->look_archive($look_hash, &$url);
    if ($res != '') $content = htmlspecialchars($res['content']);
  } elseif ($_POST['touch_archive'] != '') {
    $res = $client->touch_archive($touch_loc, &$url);
    if ($res != '') {
      $content = htmlspecialchars($res['content']);
      if ($res['hash'] != '') $look_hash = $res['hash'];
    }
  } elseif ($_POST['buy_archive'] != '') {
    $res = $client->buy_archive($touch_loc, $buy_usage, &$url);
  } elseif ($_POST['sell_archive'] != '') {
    $res = $client->sell_archive($touch_loc, $buy_usage, &$url);
  } elseif ($_POST['write_archive'] != '') {
    $res = $client->write_archive($touch_loc, $buy_usage, html_entity_decode($content), &$url);
    if ($res['hash'] != '') $look_hash = $res['hash'];
  }
} else {
  if ($_POST['random_id'] != '') {
    $random = new LoomRandom();
    $id = $random->random_id();
    $idhash = '';
    $passphrase = '';
  } elseif ($_POST['id_hash'] != '') {
    $idhash = $client->sha256($client->hex2bin($id));
  } elseif ($_POST['random_passphrase'] != '') {
    require_once "Diceware.php";
    if (!isset($diceware)) $diceware = new Diceware();
    $passphrase = $diceware->random_words(5);
    $id = '';
    $idhash = '';
  } elseif ($_POST['hash_passphrase'] != '') {
    $hash = $client->sha256($passphrase);
    $id = $client->hash2location($hash);
    $idhash = '';
  }
}

if ($page == 'grid') print_grid();
else if ($page == 'archive') print_archive();
else print_tools();

if ($res != '') {
echo "<p><b>URL</b><p>$url<p><b>Result (in KV format)</b><p><pre>";
echo "(\n";
foreach ($res as $key => $value) {
  echo ":" . htmlspecialchars($client->quote_cstring($key)) . "\n";
  echo "=" . htmlspecialchars($client->quote_cstring($value)) . "\n";
}
echo ")\n";
echo '</pre>';
}
?>
</body>
</html>

<?php

function checkResult($res, $success_key, $fail_key, &$var) {
  $status = $res['status'];
  if ($status == 'success') $var = $res[$success_key];
  else $var = $res[$fail_key];
  if ($var == '') $var = $status;
}

function hsc($text) {
  return htmlspecialchars($text);
}

function print_grid() {
  global $common_type, $loom_server, $buy_loc, $buy_usage, $issuer_orig;
  global $issuer_dest, $touch_loc, $look_hash, $move_qty, $move_orig, $move_dest;
  global $buy_message, $issuer_message, $touch_message, $look_message, $move_message;

?>
<p>
This is an interactive tutorial to help you set up and test grid operations.
Be careful using sensitive locations in this tutorial, since they do show
up in plain text on this screen. The grid API is <a href="https://loom.cc/?function=help&amp;topic=grid&amp;mode=advanced">fully documented</a>.

<p>
<form method=post action="grid-tutorial.php" autocomplete=off>
<table border=1 cellpadding=10 style='border-collapse:collapse'>

<tr>
<td>
<table border=0 style='border-collapse:collapse'>

<colgroup>
<col width=150>
<col width=600>
</colgroup>


<tr>
<td>
Asset Type:
</td>
<td>
<input type=text class=tt name=common_type size=36 value=<? echo hsc($common_type); ?>>
</td>
</tr>

<tr>
<td>
Loom server:
</td>
<td>
<input type=text name=loom_server size=50 value=<? echo hsc($loom_server); ?>>
</td>
</tr>

</table>

</td>
</tr>

<tr>
<td>
<table border=0 style='border-collapse:collapse'>
<colgroup>
<col width=150>
<col width=600>
</colgroup>


<tr>
<td>
<input type=submit name=buy value="Buy">
<input type=submit name=sell value="Sell">
</td>

<td class=small>
Buy location for one usage token, or sell location for refund.
</td>
</tr>

<tr>
<td>
Location:
</td>
<td>
<input type=text class=tt name=buy_loc size=36 value=<? echo hsc($buy_loc); ?>>
<? echo $buy_message; ?>
</td>
</tr>

<tr>
<td>
Usage location:

</td>
<td>
<input type=text class=tt name=buy_usage size=36 value=<? echo hsc($buy_usage); ?>>
</td>
</tr>

</table>

</td>
</tr>

<tr>
<td>
<table border=0 style='border-collapse:collapse'>
<colgroup>
<col width=150>
<col width=600>

</colgroup>


<tr>
<td>
<input type=submit name=issuer value="Issuer">
</td>
<td class=small>
Change the issuer location for a type.
</td>
</tr>

<tr>
<td>
Current Issuer:
</td>
<td>

<input type=text class=tt name=issuer_orig size=36 value=<? echo hsc($issuer_orig);?>>
<? echo $issuer_message; ?>
</td>
</tr>

<tr>
<td>
New Issuer:
</td>
<td>
<input type=text class=tt name=issuer_dest size=36 value=<? echo hsc($issuer_dest); ?>>
</td>
</tr>

</table>

</td>
</tr>

<tr>
<td>
<table border=0 style='border-collapse:collapse'>
<colgroup>
<col width=150>
<col width=600>
</colgroup>


<tr>
<td>
<input type=submit name=touch value="Touch">
</td>
<td class=small>
Touch a location directly to see its value.
</td>

</tr>

<tr>
<td>
Location:
</td>
<td>
<input type=text class=tt name=touch_loc size=36 value=<? echo hsc($touch_loc); ?>>
<? echo $touch_message; ?>
</td>
</tr>

</table>

</td>
</tr>

<tr>

<td>
<table border=0 style='border-collapse:collapse'>
<colgroup>
<col width=150>
<col width=600>
</colgroup>


<tr>
<td>
<input type=submit name=look value="Look">
</td>
<td class=small>
Look at a location by its hash.
</td>
</tr>

<tr>
<td>
Hash:
</td>
<td>
<input type=text class=tt name=look_hash size=72 value=<? echo hsc($look_hash); ?>>
<? echo $look_message; ?>
</td>
</tr>

</table>

</td>
</tr>

<tr>
<td>
<table border=0 style='border-collapse:collapse'>

<colgroup>
<col width=150>
<col width=600>
</colgroup>


<tr>
<td>
<input type=submit name=move value="Move">
<input type=submit name=move_back value="Back">
</td>
<td class=small>
Move units from one location to another.
</td>
</tr>

<tr>

<td>
Quantity:
</td>
<td>
<input type=text class=tt name=move_qty size=45 value=<? echo hsc($move_qty); ?>>
</td>
</tr>

<tr>
<td>
Origin:
</td>
<td>
<input type=text class=tt name=move_orig size=36 value=<? echo hsc($move_orig); ?>>
</td>
</tr>

<tr>
<td>
Destination:
</td>
<td>
<input type=text class=tt name=move_dest size=36 value=<? echo hsc($move_dest); ?>>
<? echo $move_message; ?>
</td>
</tr>

</table>

</td>
</tr>

</table>

</form>
<?php
}

function print_archive() {
  global $loom_server, $look_hash, $touch_loc, $buy_usage, $content;
?>
<form method=post action="grid-tutorial.php?page=archive" autocomplete=off>

<p>
This screen is a simple user interface into the archive function <a href="https://loom.cc/?function=help&amp;topic=archive&amp;mode=advanced">documented here</a>.
It also serves as a tutorial, showing you the API url and result.

<p>
<table border=0 style='border-collapse:collapse'>
<colgroup>
<col width=150>
<col width=700>

</colgroup>

<tr>
<td>
Loom server:
</td>
<td>
<input type=text name=loom_server size=50 value=<? echo hsc($loom_server); ?>>
</td>
</tr>
<tr>
<td>
Archive Hash:
</td>
<td>
<input type=text class=tt name=look_hash size=72 value=<? echo hsc($look_hash); ?>>
<input type=submit name=look_archive value="Look">
</td>
</tr>

<tr>
<td>
Archive Location:
</td>
<td>

<input type=text class=tt name=touch_loc size=36 value=<? echo hsc($touch_loc); ?>>
<input type=submit name=touch_archive value="Touch">
<input type=submit name=buy_archive value="Buy">
<input type=submit name=sell_archive value="Sell">

</td>
</tr>

<tr>
<td>
Usage Location:
</td>
<td>
<input type=text class=tt name=buy_usage size=36 value=<? echo hsc($buy_usage); ?>>
</td>
</tr>

<tr>
<td>
Archive Content:
</td>
<td>
<a href="<? echo hsc($loom_server); ?>?function=view&hash=<? echo hsc($look_hash); ?>" title="View as web page">View</a>
<input type=submit name=write_archive value="Write">

</td>
</tr>
<tr>
<td colspan=2>
<textarea name=content rows=20 cols=120>
<? echo hsc($content); ?>
</textarea>
</td>
</tr>

</table>
</form>

<?php
}

function print_tools() {
  global $loom_server, $id, $passphrase, $idhash;
?>
<form method=post action="grid-tutorial.php?page=tools" autocomplete=off>
<h1> Tools </h1>
<p>

Here is an assortment of tools which you may occasionally find useful.
<p>
On this panel, you may generate a new random identifier or random passphrase.
You may also compute the "hash" of a passphrase, which converts a passphrase
into an identifier.  You may enter the passphrase manually if you don't want
to use a random one.

<table border=0 cellpadding=5 style='border-collapse:collapse'>
<colgroup>
<col width=100>
<col width=600>
</colgroup>

<tr>
<td>
Loom server:
</td>
<td>
  <input type=text name=loom_server size=50 value=<? echo hsc($loom_server); ?>>
</td>
</tr>

<tr>
<td>Hash:</td>
<td>
<input type=text class=tt name=idhash size=72 value="<? echo hsc($idhash); ?>">
</td>
</tr>

<tr>
<td>Identifier:</td>
<td>
<input type=text class=tt name=id size=36 value="<? echo hsc($id); ?>">
<input type=submit name=random_id value="Random">
<input type=submit name=id_hash value="Hash">
</td>
</tr>

<tr>
<td>Passphrase:</td>
<td>
<input type=text name=passphrase size=50 value="<? echo hsc($passphrase); ?>">
<input type=submit name=random_passphrase value="Random">
<input type=submit name=hash_passphrase value="Hash">
</td>
</tr>
</table>

</form>

<?php
}

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
