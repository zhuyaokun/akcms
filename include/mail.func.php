<?php
if(!defined('CORE_ROOT')) exit;
class smtp {
	var $host;
	var $user;
	var $pass;
	var $port;
	var $sock;
	var $from;
	function smtp($host = '', $user = '', $pass = '', $from = '', $port = 25) {
		if(empty($host)) {
			global $smtphosts;
			if(empty($smtphosts)) return false;
			$r = array_rand($smtphosts);
			list($host, $user, $pass, $from, $port) = $smtphosts[$r];
		}
		$this->host = $host;
		$this->user = $user;
		$this->pass = $pass;
		$this->from = $from;
		$this->port = $port;
		$this->sock = fsockopen($host, $port, $errno, $errstr, 30);
		if(!($this->sock && $this->smtp_ok())) return false;
		if(!$this->smtp_putcmd('HELO '.$this->host)) {
			return $this->debug('sending HELO command');
		}
		if(!$this->smtp_putcmd('AUTH LOGIN '.base64_encode($this->user))) {
			return $this->debug('sending HELO command');
		}
		if(!$this->smtp_putcmd(base64_encode($this->pass))) {
			return $this->debug('sending HELO command');
		}
	}
	function sendmail($to, $subject = '', $html = '') {
		global $sysedition, $header_charset;
		eventlog('Send mail to:'.$to);
		if(@include_once('Mail/mime.php')) {
			$mime = new Mail_Mime();
			$mime->setHTMLBody($html);
			$headers = array();
			$body = $mime->get(array('html_charset' => $header_charset));
			$headers = $mime->headers($headers);
			$headers['X-Mailer'] = 'AKCMS '.$sysedition;
			$headers['Date'] = date('r');
			$headers['From'] = $this->from;
			$headers['Subject'] = $subject;
			$tos = explode(",", $to);
			foreach($tos as $to) {
				$headers['To'] = $to;
				list($msec, $sec) = explode(" ", microtime());
				$headers['Message-ID']= "<".date("YmdHis", $sec).".".($msec*1000000).".".$this->from.">\r\n";
				$header = '';
				foreach($headers as $k => $v) {
					$header .= "{$k}: ".$mime->encodeHeader($k, $v, $header_charset, 'base64')."\r\n";
				}
				$r = $this->smtp_send($to, $header, $body);
				if($r === false) return false;
			}
		} else {
			$html = str_replace("\r\n.\r\n", "\1.\3", $html);
			$_h = "MIME-Version:1.0\r\n";
			$_h .= "Content-Type:text/html\r\n";
			$_h .= "From: ".$this->from."<".$this->from.">\r\n";
			$_h .= 'Subject: '.$subject."\r\n";
			$_h .= 'Date: '.date('r')."\r\n";
			$_h .= "X-Mailer:By AKCMS ({$sysedition})\r\n";
			list($msec, $sec) = explode(" ", microtime());
			$_h .= "Message-ID: <".date("YmdHis", $sec).".".($msec*1000000).".".$this->from.">\r\n";
			$tos = explode(",", $to);
			foreach($tos as $to) {
				$header = $_h.'To: '.$to."\r\n";
				$r = $this->smtp_send($to, $header, $html);
				if($r === false) return false;
			}
		}
	}
	function close() {
		$this->smtp_putcmd('QUIT');
		return fclose($this->sock);
	}
	function smtp_send($to, $header, $body = '') {
		if(!$this->smtp_putcmd('MAIL FROM:<'.$this->from.'>')) {
			return $this->debug('ERROR:sending MAIL FROM command');
		}
		if(!$this->smtp_putcmd('RCPT TO:<'.$to.'>')) {
			return $this->debug('ERROR:sending RCPT TO command');
		}
		if(!$this->smtp_putcmd('DATA')) {
			return $this->debug('ERROR:sending DATA command');
		}
		if(!fputs($this->sock, $header."\r\n".$body)) {
			return $this->debug('ERROR:sending message');
		}
		if(!$this->smtp_eom()) {
			return $this->debug('ERROR:sending <CR><LF>.<CR><LF> [EOM]');
		}
		return TRUE;
	}
	function smtp_eom() {
		fputs($this->sock, "\r\n.\r\n");
		return $this->smtp_ok();
	}
	function smtp_ok() {
		$response = fgets($this->sock, 512);
		$response = str_replace("\r\n", '', $response);
		$this->debug('<'.$response);
		if(!preg_match("/^[23]/is", $response)) {
			fputs($this->sock, "QUIT\r\n");
			fgets($this->sock, 512);
			return false;
		}
		return true;
	}
	function smtp_putcmd($cmd) {
		$this->debug('>'.$cmd);
		fputs($this->sock, $cmd."\r\n");
		return $this->smtp_ok();
	}
	function debug($string) {
		eventlog($string);
		return false;
	}
}
?>