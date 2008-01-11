<?php
require "LoomClient.php";

$page = htmlspecialchars($_GET['page']);
$common_type = htmlspecialchars($_POST['common_type']);
$loom_server = htmlspecialchars($_POST['loom_server']);
if ($loom_server == '') $loom_server = 'https://loom.cc/';
$buy_loc = htmlspecialchars($_POST['buy_loc']);
$buy_usage = htmlspecialchars($_POST['buy_usage']);
$issuer_orig = htmlspecialchars($_POST['issuer_orig']);
$issuer_dest = htmlspecialchars($_POST['issuer_dest']);
$touch_loc = htmlspecialchars($_POST['touch_loc']);
$look_hash = htmlspecialchars($_POST['look_hash']);
$move_qty = htmlspecialchars($_POST['move_qty']);
$move_orig = htmlspecialchars($_POST['move_orig']);
$move_dest = htmlspecialchars($_POST['move_dest']);
$content = htmlspecialchars($_POST['content']);
?>
<html>
<head>
<title>Loom Grid &amp; Archive Tutorials in PHP</title>
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
if ($page != 'archive') {
  echo '<a href="https://loom.cc/?function=grid_tutorial&mode=advanced">grid';
 } else {
  echo '<a href="https://loom.cc/?function=archive_tutorial&mode=advanced">archive';
 }
  
?>
 tutorial</a>. I recommend that you avoid entering any real locations you care about on this page, since you'll be sending those locations in plain text over the web to my hosting service. The loom.cc calls from LoomClient.php ARE encrypted, though, so if you write your own web interface, and use https for your site, the entire chain to loom.cc will be encrypted.
<p>
Download source at
<a href="../loomclient.tar.gz">loomclient.tar.gz</a>
<p>
<hr>
<a href="index.html">Loom Index</a> |
<a href="grid-tutorial.php">
<?php
if ($page != 'archive') {
  echo '<b>Grid</b>';
 } else echo 'Grid';
?>
</a> |
<a href="grid-tutorial.php?page=archive">
<?php
if ($page == 'archive') {
  echo '<b>Archive</b>';
 } else echo 'Archive';
?>
</a>
<hr>
<?php
// Process the post

$client = new LoomClient($loom_server);
$res = '';

if ($page != 'archive') {
  if ($_POST['buy'] != '') {
    $res = $client->buy($common_type, $buy_loc, $buy_usage, &$url);
  } elseif ($_POST['sell'] != '') {
    $res = $client->sell($common_type, $buy_loc, $buy_usage, &$url);
  } elseif ($_POST['issuer'] != '') {
    $res = $client->issuer($common_type, $issuer_orig, $issuer_dest, &$url);
  } elseif ($_POST['touch'] != '') {
    $res = $client->touch($common_type, $touch_loc, &$url);
  } elseif ($_POST['look'] != '') {
    $res = $client->look($common_type, $look_hash, &$url);
  } elseif ($_POST['move'] != '') {
    $res = $client->move($common_type, $move_qty, $move_orig, $move_dest, &$url);
  } elseif ($_POST['move_back'] != '') {
    $res = $client->move($common_type, $move_qty, $move_dest, $move_orig, &$url);
  }
} else {
  if ($_POST['look_archive'] != '') {
    $res = $client->look_archive($look_hash, &$url);
    if ($res != '') $content = $res['content'];
  } elseif ($_POST['touch_archive'] != '') {
    $res = $client->touch_archive($touch_loc, &$url);
    if ($res != '') {
      $content = $res['content'];
      if ($res['hash'] != '') $look_hash = $res['hash'];
    }
  } elseif ($_POST['buy_archive'] != '') {
    $res = $client->buy_archive($touch_loc, $buy_usage, &$url);
  } elseif ($_POST['sell_archive'] != '') {
    $res = $client->sell_archive($touch_loc, $buy_usage, &$url);
  } elseif ($_POST['write_archive'] != '') {
    $res = $client->write_archive($touch_loc, $buy_usage, $content, &$url);
  }
}

if ($page == 'archive') print_archive();
else print_grid();

if ($res != '') {
echo $url . '<pre>';
print_r($res);
echo '</pre>';
}
?>
</body>
</html>

<?php
function print_grid() {
  global $common_type, $loom_server, $buy_loc, $buy_usage, $issuer_orig;
  global $issuer_dest, $touch_loc, $look_hash, $move_qty, $move_orig, $move_dest;
?>
<p>
This is an interactive tutorial to help you set up and test grid operations.
Be careful using sensitive locations in this tutorial, since they do show
up in plain text on this screen. The grid API is <a href="https://loom.cc/?function=help&amp;topic=grid&amp;mode=advanced">fully documented</a>.

<p>
<form method=post action="grid-tutorial.php" autocomplete=off>
<div>
<input type=hidden name="function" value="grid_tutorial">
<input type=hidden name="mode" value="advanced">
</div>

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
<input type=text class=tt name=common_type size=36 value=<? echo($common_type); ?>>
</td>
</tr>

<tr>
<td>
Loom server:
</td>
<td>
<input type=text class=tt name=loom_server size=50 value=<? echo $loom_server; ?>>
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
<input type=text class=tt name=buy_loc size=36 value=<? echo $buy_loc; ?>>
</td>
</tr>

<tr>
<td>
Usage location:

</td>
<td>
<input type=text class=tt name=buy_usage size=36 value=<? echo $buy_usage; ?>>
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

<input type=text class=tt name=issuer_orig size=36 value=<? echo $issuer_orig;?>>
</td>
</tr>

<tr>
<td>
New Issuer:
</td>
<td>
<input type=text class=tt name=issuer_dest size=36 value=<? echo $issuer_dest; ?>>
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
<input type=text class=tt name=touch_loc size=36 value=<? echo $touch_loc; ?>>
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
<input type=text class=tt name=look_hash size=72 value=<? echo $look_hash; ?>>
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
<input type=text class=tt name=move_qty size=45 value=<? echo $move_qty; ?>>
</td>
</tr>

<tr>
<td>
Origin:
</td>
<td>
<input type=text class=tt name=move_orig size=36 value=<? echo $move_orig; ?>>
</td>
</tr>

<tr>
<td>
Destination:
</td>
<td>
<input type=text class=tt name=move_dest size=36 value=<? echo $move_dest; ?>>
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
<input type=text class=tt name=loom_server size=50 value=<? echo $loom_server ?>>
</td>
</tr>
<tr>
<td>
Archive Hash:
</td>
<td>
<input type=text class=tt name=look_hash size=72 value=<? echo $look_hash; ?>>
<input type=submit name=look_archive value="Look">
</td>
</tr>

<tr>
<td>
Archive Location:
</td>
<td>

<input type=text class=tt name=touch_loc size=36 value=<? echo $touch_loc; ?>>
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
<input type=text class=tt name=buy_usage size=36 value=<? echo $buy_usage; ?>>
</td>
</tr>

<tr>
<td>
Archive Content:
</td>
<td>
<a href="<? echo $loom_server; ?>?function=view&hash=<? echo $look_hash; ?>" title="View as web page">View</a>
<input type=submit name=write_archive value="Write">

</td>
</tr>
<tr>
<td colspan=2>
<textarea name=content rows=20 cols=120>
<? echo $content; ?>
</textarea>
</td>
</tr>

</table>
</form>

<?php
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
