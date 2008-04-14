<?php
/* $Id: socket.php,v 1.3 2002/09/08 19:25:02 shaggy Exp $ */

/*
Copyright (c) 2001, 2002 by Martin Tsachev. All rights reserved.
http://www.mtweb.org

Redistribution and use in source and binary forms,
with or without modification, are permitted provided
that the conditions available at
http://www.opensource.org/licenses/bsd-license.html
are met.
*/

// From http://www.mtdev.com/download/2002/09/http-keep-alive-connections-in-php/socket.phps

Class Socket {
	var $fp = null;
	var $host;
        var $ssl;
	var $timeout = 30;
	var $maxlen = 4096;

	var $accept = 'text/plain'; // MIME types to accept
	var $lang = 'en'; // Language to accept

	var $headers; // response headers
	var $body; // response body

	var $status; // HTTP status
	var $connection; // Connection type: close/keep-alive
	var $te; // Transfer encoding
	var $type; // returned MIME type


	function Socket($host, $ssl=false) {
		$this->host = $host;
                $this->ssl = $ssl;
	}


	function setAccept($types) {
		$this->accept = $types;
	}


	function connect() {
          $port = 80;
          $host = $this->host;
          if ($this->ssl) {
            $host = "ssl://$host";
            $port = 443;
          }
		$this->fp = fsockopen($host, $port, $errno, $errstr, $this->timeout);

		if (!$this->fp) {
			return "Network error: $errstr ($errno)";
		}

		return 0;
	}


	function disconnect() {
		fclose($this->fp);
	}


	function get($uri) {
		$request =
			"GET $uri HTTP/1.1\r\n" .
			"Host: $this->host\r\n" .
			"Connection: Keep-Alive\r\n" .
                  //"Accept: $this->accept\r\n" .
                  //"Accept-Language: $this->lang\r\n" .
                  //"Accept-Encoding: chunked\r\n" .
                  //"User-Agent: PHP/4.2.1\r\n" .
			"\r\n";

		fputs($this->fp, $request);

		$this->headers = fgets($this->fp, $this->maxlen);
		if (!$this->headers) { // if disconnected meanwhile
			$this->connect();
			fputs($this->fp, $request);
			$this->headers = fgets($this->fp, $this->maxlen);
		}

		preg_match('|^HTTP.+? (\d+?) |', $this->headers, $matches);
		$this->status = $matches[1];

		$this->type = '';
		$this->connection = '';
		$this->te = '';

		while ($line = fgets($this->fp, $this->maxlen)) {
			if ($line == "\r\n") { break; }

			if (preg_match('/^Content-Length: (.+)/', $line, $matches)) {
				$length = (int) trim($matches[1]);
			}

			if (preg_match('/^Content-Type: (.+)/', $line, $matches)) {
				$this->type = strtolower(trim($matches[1]));
			}

			if (preg_match('/^Connection: (.+)/', $line, $matches)) {
				$this->connection = strtolower(trim($matches[1]));
			}

			if (preg_match('/^Transfer-Encoding: (.+)/', $line, $matches)) {
				$this->te = strtolower(trim($matches[1]));
			}

			$this->headers .= $line;
		}

		$this->body = '';
		if ($this->connection == 'close') {
			while (!feof($this->fp)) {
				$this->body .= fread($this->fp, $this->maxlen);
			}
			return ;
		}

		if (isset($length) and strpos($this->te, 'chunked') === false) {
                  $this->body = '';
                  while ($length > 0) {
                    $str = fread($this->fp, $length);
                    $this->body .= $str;
                    $length -= strlen($str);
                  }
                  return ;
		}

		// chunked encoding
		$length = rtrim(fgets($this->fp, $this->maxlen));
		$length = hexdec($length);

		while (true) {
			if ($length < 1) { break; }
			$this->body .= fread($this->fp, $length);

			fgets($this->fp, $this->maxlen);
			$length = rtrim(fgets($this->fp, $this->maxlen));
			$length = hexdec($length);
		}

		fgets($this->fp, $this->maxlen);
	}

} // class Socket
?>
