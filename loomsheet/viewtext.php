<?php

// simple file viewer

function mq($x) {
  if (get_magic_quotes_gpc()) return stripslashes($x);
  else return $x;
}

$file = mq($_GET['file']);
$title = mq($_GET['title']);

$file = trim($file);

if ($title == '') $title = $file;

if (substr($file, 0, 1) == '/' || strpos(' ' . $file, '..') > 0) {
  echo "Thought you could access some random file, didn't you. Not!";
  return;
 }

$text = htmlspecialchars(file_get_contents($file));

// Add paragraph breaks
$text = str_replace("\n\n", "\n<p>\n", $text);

// Add line breaks
$text = str_replace("\n", "<br>\n", $text);

// Get rid of the breaks just before paragraphs
$text = str_replace("<br>\n<p>", "\n<p>", $text);

// Make spaces non-breaking
$text = str_replace(" ", "&nbsp;", $text);

// And change the non-duplicated ones back to spaces
$text = preg_replace("/([^;])&nbsp;([^&])/", "$1 $2", $text);

// Once more to get the remaining &nbsp; after single chars
$text = preg_replace("/([^;])&nbsp;([^&])/", "$1 $2", $text);

?>
<html>
<head>
<title><? echo htmlspecialchars($title); ?></title>
</head>
<body>
<div style="font-family: courier">
<? echo $text; ?>
</div>
</body>
</html>

<?

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