<?php
if (!defined('BASE_PATH')) exit('No direct script access allowed');

/**
 * Email Class
 *
 * 邮件发送类,支持使用 mail 函数，STMP， sendmail 程序发送，依赖配置 email.php 。
 * 
 * 建议使用 GBK 编码，因为某些邮箱并不支持 UTF-8 编码或者支持不完整。本类会自动将数据从
 * 站点使用编码转换为 Email 编码。这需要  iconv {@link http://cn.php.net/iconv} 库
 * 的支持
 *
 * @package		AtomCode
 * @subpackage	library
 * @category	library
 * @author		Eachcan<eachcan@gmail.com>
 * @license		http://digglink.com/doc/license.html
 * @link		http://digglink.com
 * @since		Version 1.0
 * @filesource
 */
class Email {

	private $useragent = "AtomCode v0.1.2";

	private $mailpath = "/usr/sbin/sendmail"; // Sendmail path

	
	private $driver = "mail"; // mail/sendmail/smtp

	
	private $smtp_host = ""; // SMTP Server.  Example: mail.earthlink.net

	
	private $smtp_user = ""; // SMTP Username

	
	private $smtp_pass = ""; // SMTP Password

	
	private $smtp_port = "25"; // SMTP Port

	
	private $smtp_timeout = 5; // SMTP Timeout in seconds

	
	private $wordwrap = TRUE; // TRUE/FALSE  Turns word-wrap on/off

	
	private $wrapchars = "76"; // Number of characters to wrap at.

	
	private $mailtype = "text"; // text/html  Defines email formatting

	
	private $charset = "utf-8"; // Default char set: iso-8859-1 or us-ascii

	private $sys_charset = "";
	
	private $multipart = "mixed"; // "mixed" (in the body) or "related" (separate)

	
	private $alt_message = ''; // Alternative message for HTML emails

	
	private $validate = FALSE; // TRUE/FALSE.  Enables email validation

	
	private $priority = "3"; // Default priority (1 - 5)

	
	private $newline = "\n"; // Default newline. "\r\n" or "\n" (Use "\r\n" to comply with RFC 822)

	
	private $crlf = "\n"; // The RFC 2045 compliant CRLF for quoted-printable is "\r\n".  Apparently some servers,

	
	// even on the receiving end think they need to muck with CRLFs, so using "\n", while
	// distasteful, is the only thing that seems to work for all environments.
	private $send_multipart = TRUE; // TRUE/FALSE - Yahoo does not like multipart alternative, so this is an override.  Set to FALSE for Yahoo.

	
	private $bcc_batch_mode = FALSE; // TRUE/FALSE  Turns on/off Bcc batch feature

	
	private $bcc_batch_size = 200; // If bcc_batch_mode = TRUE, sets max number of Bccs in each batch

	
	private $_safe_mode = FALSE;

	private $_subject = "";

	private $_body = "";

	private $_finalbody = "";

	private $_alt_boundary = "";

	private $_atc_boundary = "";

	private $_header_str = "";

	private $_smtp_connect = "";

	private $_encoding = "8bit";

	private $_IP = FALSE;

	private $_smtp_auth = FALSE;

	private $_replyto_flag = FALSE;

	private $_debug_msg = array();

	private $_recipients = array();

	private $_cc_array = array();

	private $_bcc_array = array();

	private $_headers = array();

	private $_attach_name = array();

	private $_attach_type = array();

	private $_attach_disp = array();

	private $_drivers = array('mail', 'sendmail', 'smtp');

	private $_base_charsets = array('us-ascii', 'iso-2022-'); // 7-bit charsets (excluding language suffix)

	
	private $_bit_depths = array('7bit', '8bit');

	private $_priorities = array('1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)');

	private static $instance;

	/**
	 * Constructor - Sets Email Preferences
	 */
	private function __construct($config = array()) {
		if (count($config) > 0) {
			$this->initialize($config);
		} else {
			$this->_smtp_auth = ($this->smtp_user == '' and $this->smtp_pass == '') ? FALSE : TRUE;
			$this->_safe_mode = ((boolean) @ini_get("safe_mode") === FALSE) ? FALSE : TRUE;
		}
		
		log_message('debug', "Email Class Initialized");
	}

	/**
	 * 取得本类实例
	 * @return Email
	 */
	public static function &instance() {
		if (!isset(self::$instance)) {
			self::$instance = new Email();
		}
		
		self::$instance->initialize(load_config('email'));
		
		return self::$instance;
	}

	/**
	 * 初始化配置信息
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */
	public function initialize($config = array()) {
		$this->clear();
		foreach ($config as $key => $val) {
			if (isset($this->$key)) {
				$method = 'set_' . $key;
				
				if (method_exists($this, $method)) {
					$this->$method($val);
				} else {
					$this->$key = $val;
				}
			}
		}
		
		$this->_smtp_auth = ($this->smtp_user == '' and $this->smtp_pass == '') ? FALSE : TRUE;
		$this->_safe_mode = ((boolean) @ini_get("safe_mode") === FALSE) ? FALSE : TRUE;
		$this->sys_charset = strtolower(get_config('charset'));
	}

	/**
	 * 清除邮件数据
	 *
	 * @access	public
	 * @return	void
	 */
	public function clear($clear_attachments = FALSE) {
		$this->_subject = "";
		$this->_body = "";
		$this->_finalbody = "";
		$this->_header_str = "";
		$this->_replyto_flag = FALSE;
		$this->_recipients = array();
		$this->_headers = array();
		$this->_debug_msg = array();
		
		$this->_setHeader('User-Agent', $this->useragent);
		$this->_setHeader('Date', $this->_setDate());
		
		if ($clear_attachments !== FALSE) {
			$this->_attach_name = array();
			$this->_attach_type = array();
			$this->_attach_disp = array();
		}
	}

	/**
	 * 设置发件人
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function from($from, $name = '') {
		if (preg_match('/\<(.*)\>/', $from, $match)) {
			$from = $match['1'];
		}
		
		if ($this->validate) {
			$this->validateEmail($this->_strToArray($from));
		}
		
		if ($name != '') {
			$name = $this->_toNativeEncoding($name);
			$name = $this->_doQEncoding($name, TRUE);
		}
		
		$this->_setHeader('From', $name . ' <' . $from . '>');
		$this->_setHeader('Return-Path', '<' . $from . '>');
	}

	/**
	 * 设置回复到
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function replyTo($replyto, $name = '') {
		if (preg_match('/\<(.*)\>/', $replyto, $match)) {
			$replyto = $match['1'];
		}
		
		if ($this->validate) {
			$this->validateEmail($this->_strToArray($replyto));
		}
		
		if ($name == '') {
			$name = $replyto;
		}
		
		if ($name != '') {
			$name = $this->_toNativeEncoding($name);
			$name = $this->_doQEncoding($name, TRUE);
		}
		
		$this->_setHeader('Reply-To', $name . ' <' . $replyto . '>');
		$this->_replyto_flag = TRUE;
	}

	/**
	 * 设置收件人
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function to($to) {
		$to = $this->_strToArray($to);
		$to = $this->cleanEmail($to);
		
		if ($this->validate) {
			$this->validateEmail($to);
		}
		
		if ($this->_getDriver() != 'mail') {
			$this->_setHeader('To', implode(", ", $to));
		}
		
		switch ($this->_getDriver()) {
			case 'smtp':
				$this->_recipients = $to;
				break;
			case 'sendmail':
			case 'mail':
				$this->_recipients = implode(", ", $to);
				break;
		}
	}

	/**
	 * 设置抄送人
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function cc($cc) {
		$cc = $this->_strToArray($cc);
		$cc = $this->cleanEmail($cc);
		
		if ($this->validate) {
			$this->validateEmail($cc);
		}
		
		$this->_setHeader('Cc', implode(", ", $cc));
		
		if ($this->_getDriver() == "smtp") {
			$this->_cc_array = $cc;
		}
	}

	/**
	 * 设置秘密抄送人
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function bcc($bcc, $limit = '') {
		if ($limit != '' && is_numeric($limit)) {
			$this->bcc_batch_mode = TRUE;
			$this->bcc_batch_size = $limit;
		}
		
		$bcc = $this->_strToArray($bcc);
		$bcc = $this->cleanEmail($bcc);
		
		if ($this->validate) {
			$this->validateEmail($bcc);
		}
		
		if (($this->_getDriver() == "smtp") or ($this->bcc_batch_mode && count($bcc) > $this->bcc_batch_size)) {
			$this->_bcc_array = $bcc;
		} else {
			$this->_setHeader('Bcc', implode(", ", $bcc));
		}
	}

	/**
	 * 设置邮件主题
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function subject($subject) {
		$subject = $this->_toNativeEncoding($subject);
		$subject = $this->_doQEncoding($subject);
		$this->_setHeader('Subject', $subject);
	}

	/**
	 * 设置邮件主题
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function body($body) {
		$this->_body = $this->_toNativeEncoding(stripslashes(rtrim(str_replace("\r", "", $body))));
	}

	/**
	 * 添加一个附件
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	function attach($filename, $disposition = 'attachment') {
		$this->_attach_name[] = $filename;
		$this->_attach_type[] = $this->_mimeTypes(next(explode('.', basename($filename))));
		$this->_attach_disp[] = $disposition; // Can also be 'inline'  Not sure if it matters
	}

	/**
	 * 增加一个邮件头
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	private function _setHeader($header, $value) {
		$this->_headers[$header] = $value;
	}

	/**
	 * 字符串转换为数组
	 *
	 * @access	private
	 * @param	string
	 * @return	array
	 */
	private function _strToArray($email) {
		if (!is_array($email)) {
			if (strpos($email, ',') !== FALSE || strpos($email, ' ') !== FALSE) {
				$email = preg_split('/[\s,]/', $email, -1, PREG_SPLIT_NO_EMPTY);
			} else {
				$email = trim($email);
				settype($email, "array");
			}
		}
		return $email;
	}

	/**
	 * 设置文本模式的信息
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setAltMessage($str = '') {
		$this->alt_message = ($str == '') ? '' : $str;
	}

	/**
	 * 设置邮件类型
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setMailtype($type = 'html') {
		$this->mailtype = ($type == 'text') ? 'text' : 'html';
	}

	/**
	 * 设置文本换行，便于导出为 .eml 后查看
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setWordwrap($wordwrap = TRUE) {
		$this->wordwrap = ($wordwrap === FALSE) ? FALSE : TRUE;
	}

	/**
	 * 设置发信方式
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setDriver($driver = 'mail') {
		$this->driver = (!in_array($driver, $this->_drivers, TRUE)) ? 'mail' : strtolower($driver);
	}

	/**
	 * 设置优先级
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */
	public function setPriority($n = 3) {
		if (!is_numeric($n)) {
			$this->priority = 3;
			return;
		}
		
		if ($n < 1 or $n > 5) {
			$this->priority = 3;
			return;
		}
		
		$this->priority = $n;
	}

	/**
	 * 设置新行符
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setNewline($newline = "\n") {
		if ($newline != "\n" and $newline != "\r\n" and $newline != "\r") {
			$this->newline = "\n";
			return;
		}
		
		$this->newline = $newline;
	}

	/**
	 * 设置行结束符
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setCrlf($crlf = "\n") {
		if ($crlf != "\n" and $crlf != "\r\n" and $crlf != "\r") {
			$this->crlf = "\n";
			return;
		}
		
		$this->crlf = $crlf;
	}

	/**
	 * 多块邮件边界字符
	 *
	 * @access	private
	 * @return	void
	 */
	private function _setBoundaries() {
		$this->_alt_boundary = "B_ALT_" . uniqid(''); // multipart/alternative
		$this->_atc_boundary = "B_ATC_" . uniqid(''); // attachment boundary
	}

	/**
	 * 取得一个 MessageID，用于防止重复发送同一封邮件
	 *
	 * @access	private
	 * @return	string
	 */
	private function _getMessageId() {
		$from = $this->_headers['Return-Path'];
		$from = str_replace(">", "", $from);
		$from = str_replace("<", "", $from);
		
		return "<" . uniqid('') . strstr($from, '@') . ">";
	}

	/**
	 * 取得邮件发送方法
	 *
	 * @access	private
	 * @param	bool
	 * @return	string
	 */
	private function _getDriver($return = TRUE) {
		$this->driver = strtolower($this->driver);
		$this->driver = (!in_array($this->driver, $this->_drivers, TRUE)) ? 'mail' : $this->driver;
		
		if ($return == TRUE) {
			return $this->driver;
		}
	}

	/**
	 * 取得邮件编码
	 *
	 * @access	private
	 * @param	bool
	 * @return	string
	 */
	private function _getEncoding($return = TRUE) {
		$this->_encoding = (!in_array($this->_encoding, $this->_bit_depths)) ? '8bit' : $this->_encoding;
		
		foreach ($this->_base_charsets as $charset) {
			if (strncmp($charset, $this->charset, strlen($charset)) == 0) {
				$this->_encoding = '7bit';
			}
		}
		
		if ($return == TRUE) {
			return $this->_encoding;
		}
	}

	/**
	 * Get content type (text/html/attachment)
	 *
	 * @access	private
	 * @return	string
	 */
	private function _getContentType() {
		if ($this->mailtype == 'html' && count($this->_attach_name) == 0) {
			return 'html';
		} elseif ($this->mailtype == 'html' && count($this->_attach_name) > 0) {
			return 'html-attach';
		} elseif ($this->mailtype == 'text' && count($this->_attach_name) > 0) {
			return 'plain-attach';
		} else {
			return 'plain';
		}
	}

	/**
	 * Set RFC 822 Date
	 *
	 * @access	private
	 * @return	string
	 */
	private function _setDate() {
		$timezone = date("Z");
		$operator = (strncmp($timezone, '-', 1) == 0) ? '-' : '+';
		$timezone = abs($timezone);
		$timezone = floor($timezone / 3600) * 100 + ($timezone % 3600) / 60;
		
		return sprintf("%s %s%04d", date("D, j M Y H:i:s"), $operator, $timezone);
	}

	/**
	 * Mime message
	 *
	 * @access	private
	 * @return	string
	 */
	private function _getMimeMessage() {
		return "This is a multi-part message in MIME format." . $this->newline . "Your email application may not support this format.";
	}

	/**
	 * 验证邮件地址组是否正确
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function validateEmail($email) {
		if (!is_array($email)) {
			$this->_setErrorMessage('email_must_be_array');
			return FALSE;
		}
		
		foreach ($email as $val) {
			if (!$this->validEmail($val)) {
				$this->_setErrorMessage('email_invalid_address', $val);
				return FALSE;
			}
		}
	}

	/**
	 * 验证邮件地址
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function validEmail($address) {
		return (!preg_match('/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix', $address)) ? FALSE : TRUE;
	}

	/**
	 * 取得邮箱的邮箱地址部分，去除其他字符
	 * 
	 * 例如： E <eachcan@gmail.com> 取到 eachcan@gmail.com
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function cleanEmail($email) {
		if (!is_array($email)) {
			if (preg_match('/\<(.*)\>/', $email, $match)) {
				return $match[1];
			} else {
				return $email;
			}
		}
		
		$clean_email = array();
		
		foreach ($email as $addy) {
			if (preg_match('/\<(.*)\>/', $addy, $match)) {
				$clean_email[] = $match['1'];
			} else {
				$clean_email[] = $addy;
			}
		}
		
		return $clean_email;
	}

	/**
	 * Build alternative plain text message
	 *
	 * This function provides the raw message for use
	 * in plain-text headers of HTML-formatted emails.
	 * If the user hasn't specified his own alternative message
	 * it creates one by stripping the HTML
	 *
	 * @access	private
	 * @return	string
	 */
	private function _getAltMessage() {
		if ($this->alt_message != "") {
			return $this->wordWrap($this->alt_message, '76');
		}
		
		if (preg_match('/\<body.*?\>(.*)\<\/body\>/si', $this->_body, $match)) {
			$body = $match['1'];
		} else {
			$body = $this->_body;
		}
		
		$body = trim(strip_tags($body));
		$body = preg_replace('#<!--(.*)--\>#', "", $body);
		$body = str_replace("\t", "", $body);
		
		for($i = 20; $i >= 3; $i--) {
			$n = "";
			
			for($x = 1; $x <= $i; $x++) {
				$n .= "\n";
			}
			
			$body = str_replace($n, "\n\n", $body);
		}
		
		return $this->wordWrap($body, '76');
	}

	/**
	 * 为邮件换行
	 *
	 * @access	public
	 * @param	string
	 * @param	integer
	 * @return	string
	 */
	public function wordWrap($str, $charlim = '') {
		// Se the character limit
		if ($charlim == '') {
			$charlim = ($this->wrapchars == "") ? "76" : $this->wrapchars;
		}
		
		// Reduce multiple spaces
		$str = preg_replace("| +|", " ", $str);
		
		// Standardize newlines
		if (strpos($str, "\r") !== FALSE) {
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}
		
		// If the current word is surrounded by {unwrap} tags we'll 
		// strip the entire chunk and replace it with a marker.
		$unwrap = array();
		if (preg_match_all('|(\{unwrap\}.+?\{/unwrap\})|s', $str, $matches)) {
			for($i = 0; $i < count($matches[0]); $i++) {
				$unwrap[] = $matches[1][$i];
				$str = str_replace($matches[1][$i], "{{unwrapped" . $i . "}}", $str);
			}
		}
		
		// Use PHP's native function to do the initial wordwrap.  
		// We set the cut flag to FALSE so that any individual words that are 
		// too long get left alone.  In the next step we'll deal with them.
		$str = wordwrap($str, $charlim, "\n", FALSE);
		
		// Split the string into individual lines of text and cycle through them
		$output = "";
		foreach (explode("\n", $str) as $line) {
			// Is the line within the allowed character count?
			// If so we'll join it to the output and continue
			if (strlen($line) <= $charlim) {
				$output .= $line . $this->newline;
				continue;
			}
			
			$temp = '';
			while ((strlen($line)) > $charlim) {
				// If the over-length word is a URL we won't wrap it
				if (preg_match('!\[url.+\]|://|wwww.!', $line)) {
					break;
				}
				
				// Trim the word down
				$temp .= substr($line, 0, $charlim - 1);
				$line = substr($line, $charlim - 1);
			}
			
			// If $temp contains data it means we had to split up an over-length 
			// word into smaller chunks so we'll add it back to our current line
			if ($temp != '') {
				$output .= $temp . $this->newline . $line;
			} else {
				$output .= $line;
			}
			
			$output .= $this->newline;
		}
		
		// Put our markers back
		if (count($unwrap) > 0) {
			foreach ($unwrap as $key => $val) {
				$output = str_replace("{{unwrapped" . $key . "}}", $val, $output);
			}
		}
		
		return $output;
	}

	/**
	 * Build final headers
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _buildHeaders() {
		$this->_setHeader('X-Sender', $this->cleanEmail($this->_headers['From']));
		$this->_setHeader('X-Mailer', $this->useragent);
		$this->_setHeader('X-Priority', $this->_priorities[$this->priority - 1]);
		$this->_setHeader('Message-ID', $this->_getMessageId());
		$this->_setHeader('Mime-Version', '1.0');
	}

	/**
	 * Write Headers as a string
	 *
	 * @access	private
	 * @return	void
	 */
	private function _writeHeaders() {
		if ($this->driver == 'mail') {
			$this->_subject = $this->_headers['Subject'];
			unset($this->_headers['Subject']);
		}
		
		reset($this->_headers);
		$this->_header_str = "";
		
		foreach ($this->_headers as $key => $val) {
			$val = trim($val);
			
			if ($val != "") {
				$this->_header_str .= $key . ": " . $val . $this->newline;
			}
		}
		
		if ($this->_getDriver() == 'mail') {
			$this->_header_str = substr($this->_header_str, 0, -1);
		}
	}

	/**
	 * Build Final Body and attachments
	 *
	 * @access	private
	 * @return	void
	 */
	private function _buildMessage() {
		if ($this->wordwrap === TRUE and $this->mailtype != 'html') {
			$this->_body = $this->wordWrap($this->_body);
		}
		
		$this->_setBoundaries();
		$this->_writeHeaders();
		
		$hdr = ($this->_getDriver() == 'mail') ? $this->newline : '';
		
		switch ($this->_getContentType()) {
			case 'plain' :
				
				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: " . $this->_getEncoding();
				
				if ($this->_getDriver() == 'mail') {
					$this->_header_str .= $hdr;
					$this->_finalbody = $this->_body;
					
					return;
				}
				
				$hdr .= $this->newline . $this->newline . $this->_body;
				
				$this->_finalbody = $hdr;
				return;
				
				break;
			case 'html' :
				
				if ($this->send_multipart === FALSE) {
					$hdr .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
					$hdr .= "Content-Transfer-Encoding: quoted-printable";
				} else {
					$hdr .= "Content-Type: multipart/alternative; boundary=\"" . $this->_alt_boundary . "\"" . $this->newline;
					$hdr .= $this->_getMimeMessage() . $this->newline . $this->newline;
					$hdr .= "--" . $this->_alt_boundary . $this->newline;
					
					$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
//					$hdr .= "Content-Transfer-Encoding: " . $this->_getEncoding() . $this->newline . $this->newline;
//					$hdr .= $this->_getAltMessage() . $this->newline . $this->newline . "--" . $this->_alt_boundary . $this->newline;
					$hdr .= "Content-Transfer-Encoding: base64" . $this->newline . $this->newline;
					$hdr .= base64_encode($this->_getAltMessage()) . $this->newline . $this->newline . "--" . $this->_alt_boundary . $this->newline;
					
					$hdr .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
					$hdr .= "Content-Transfer-Encoding: base64";
				}
				
				//$this->_body = $this->_prepQuotedPrintable($this->_body);
				$this->_body = base64_encode($this->_body);

				if ($this->_getDriver() == 'mail') {
					$this->_header_str .= $hdr;
					$this->_finalbody = $this->_body . $this->newline . $this->newline;
					
					if ($this->send_multipart !== FALSE) {
						$this->_finalbody .= "--" . $this->_alt_boundary . "--";
					}
					
					return;
				}
				
				$hdr .= $this->newline . $this->newline;
				$hdr .= $this->_body . $this->newline . $this->newline;
				
				if ($this->send_multipart !== FALSE) {
					$hdr .= "--" . $this->_alt_boundary . "--";
				}
				
				$this->_finalbody = $hdr;
				return;
				
				break;
			case 'plain-attach' :
				
				$hdr .= "Content-Type: multipart/" . $this->multipart . "; boundary=\"" . $this->_atc_boundary . "\"" . $this->newline;
				$hdr .= $this->_getMimeMessage() . $this->newline . $this->newline;
				$hdr .= "--" . $this->_atc_boundary . $this->newline;
				
				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: " . $this->_getEncoding();
				
				if ($this->_getDriver() == 'mail') {
					$this->_header_str .= $hdr;
					
					$body = $this->_body . $this->newline . $this->newline;
				}
				
				$hdr .= $this->newline . $this->newline;
				$hdr .= $this->_body . $this->newline . $this->newline;
				
				break;
			case 'html-attach' :
				
				$hdr .= "Content-Type: multipart/" . $this->multipart . "; boundary=\"" . $this->_atc_boundary . "\"" . $this->newline;
				$hdr .= $this->_getMimeMessage() . $this->newline . $this->newline;
				$hdr .= "--" . $this->_atc_boundary . $this->newline;
				
				$hdr .= "Content-Type: multipart/alternative; boundary=\"" . $this->_alt_boundary . "\"" . $this->newline . $this->newline;
				$hdr .= "--" . $this->_alt_boundary . $this->newline;
				
				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: " . $this->_getEncoding() . $this->newline . $this->newline;
				$hdr .= $this->_getAltMessage() . $this->newline . $this->newline . "--" . $this->_alt_boundary . $this->newline;
				
				$hdr .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: quoted-printable";
				
				$this->_body = $this->_prepQuotedPrintable($this->_body);
				
				if ($this->_getDriver() == 'mail') {
					$this->_header_str .= $hdr;
					
					$body = $this->_body . $this->newline . $this->newline;
					$body .= "--" . $this->_alt_boundary . "--" . $this->newline . $this->newline;
				}
				
				$hdr .= $this->newline . $this->newline;
				$hdr .= $this->_body . $this->newline . $this->newline;
				$hdr .= "--" . $this->_alt_boundary . "--" . $this->newline . $this->newline;
				
				break;
		}
		
		$attachment = array();
		
		$z = 0;
		
		for($i = 0; $i < count($this->_attach_name); $i++) {
			$filename = $this->_attach_name[$i];
			$basename = basename($filename);
			$ctype = $this->_attach_type[$i];
			
			if (!file_exists($filename)) {
				$this->_setErrorMessage('email_attachment_missing', $filename);
				return FALSE;
			}
			
			$h = "--" . $this->_atc_boundary . $this->newline;
			$h .= "Content-type: " . $ctype . "; ";
			$h .= "name=\"" . $basename . "\"" . $this->newline;
			$h .= "Content-Disposition: " . $this->_attach_disp[$i] . ";" . $this->newline;
			$h .= "Content-Transfer-Encoding: base64" . $this->newline;
			
			$attachment[$z++] = $h;
			$file = filesize($filename) + 1;
			
			if (!$fp = fopen($filename, 'rb')) {
				$this->_setErrorMessage('email_attachment_unreadable', $filename);
				return FALSE;
			}
			
			$attachment[$z++] = chunk_split(base64_encode(fread($fp, $file)));
			fclose($fp);
		}
		
		if ($this->_getDriver() == 'mail') {
			$this->_finalbody = $body . implode($this->newline, $attachment) . $this->newline . "--" . $this->_atc_boundary . "--";
			
			return;
		}
		
		$this->_finalbody = $hdr . implode($this->newline, $attachment) . $this->newline . "--" . $this->_atc_boundary . "--";
		
		return;
	}

	/**
	 * Prep Quoted Printable
	 *
	 * Prepares string for Quoted-Printable Content-Transfer-Encoding
	 * Refer to RFC 2045 http://www.ietf.org/rfc/rfc2045.txt
	 *
	 * @access	private
	 * @param	string
	 * @param	integer
	 * @return	string
	 */
	private function _prepQuotedPrintable($str, $charlim = '') {
		// Set the character limit
		// Don't allow over 76, as that will make servers and MUAs barf
		// all over quoted-printable data
		if ($charlim == '' or $charlim > '76') {
			$charlim = '76';
		}
		
		// Reduce multiple spaces
		$str = preg_replace("| +|", " ", $str);
		
		// kill nulls
		$str = preg_replace('/\x00+/', '', $str);
		
		// Standardize newlines
		if (strpos($str, "\r") !== FALSE) {
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}
		
		// We are intentionally wrapping so mail servers will encode characters
		// properly and MUAs will behave, so {unwrap} must go!
		$str = str_replace(array('{unwrap}', '{/unwrap}'), '', $str);
		
		// Break into an array of lines
		$lines = explode("\n", $str);
		
		$escape = '=';
		$output = '';
		
		foreach ($lines as $line) {
			$length = strlen($line);
			$temp = '';
			
			// Loop through each character in the line to add soft-wrap
			// characters at the end of a line " =\r\n" and add the newly
			// processed line(s) to the output (see comment on $crlf class property)
			for($i = 0; $i < $length; $i++) {
				// Grab the next character
				$char = substr($line, $i, 1);
				$ascii = ord($char);
				
				// Convert spaces and tabs but only if it's the end of the line
				if ($i == ($length - 1)) {
					$char = ($ascii === 32 or $ascii === 9) ? $escape . sprintf('%02s', dechex($ascii)) : $char;
				}
				
				// encode = signs
				if ($ascii === 61) {
					$char = $escape . strtoupper(sprintf('%02s', dechex($ascii))); // =3D
				}
				
				// If we're at the character limit, add the line to the output,
				// reset our temp variable, and keep on chuggin'
				if ((strlen($temp) + strlen($char)) >= $charlim) {
					$output .= $temp . $escape . $this->crlf;
					$temp = '';
				}
				
				// Add the character to our temporary line
				$temp .= $char;
			}
			
			// Add our completed line to the output
			$output .= $temp . $this->crlf;
		}
		
		// get rid of extra CRLF tacked onto the end
		$output = substr($output, 0, strlen($this->crlf) * -1);
		
		return $output;
	}

	/**
	 * Prep Q Encoding
	 *
	 * Performs "Q Encoding" on a string for use in email headers.  It's related
	 * but not identical to quoted-printable, so it has its own method
	 *
	 * @access	public
	 * @param	str
	 * @param	bool	// set to TRUE for processing From: headers
	 * @return	str
	 */
	private function _prepQEncoding($str, $from = FALSE) {
		$str = str_replace(array("\r", "\n"), array('', ''), $str);
		
		// Line length must not exceed 76 characters, so we adjust for
		// a space, 7 extra characters =??Q??=, and the charset that we will add to each line
		$limit = 75 - 7 - strlen($this->charset);
		
		// these special characters must be converted too
		$convert = array('_', '=', '?');
		
		if ($from === TRUE) {
			$convert[] = ',';
			$convert[] = ';';
		}
		
		$output = '';
		$temp = '';
		
		for($i = 0, $length = strlen($str); $i < $length; $i++) {
			// Grab the next character
			$char = substr($str, $i, 1);
			$ascii = ord($char);
			
			// convert ALL non-printable ASCII characters and our specials
			if ($ascii < 32 or $ascii > 126 or in_array($char, $convert)) {
				$char = '=' . dechex($ascii);
			}
			
			// handle regular spaces a bit more compactly than =20
			if ($ascii == 32) {
				$char = '_';
			}
			
			// If we're at the character limit, add the line to the output,
			// reset our temp variable, and keep on chuggin'
			if ((strlen($temp) + strlen($char)) >= $limit) {
				$output .= $temp . $this->crlf;
				$temp = '';
			}
			
			// Add the character to our temporary line
			$temp .= $char;
		}
		
		$str = $output . $temp;
		
		// wrap each line with the shebang, charset, and transfer encoding
		// the preceding space on successive lines is required for header "folding"
		$str = trim(preg_replace('/^(.*)$/m', ' =?' . $this->charset . '?Q?$1?=', $str));
		
		return $str;
	}

	private function _doQEncoding($str, $from = FALSE) {
		if ($str != '') {
			if (!preg_match('/[\200-\377]/', $str)) {
				if ($from) {
					// add slashes for non-printing characters, slashes, and double quotes, and surround it in double quotes
					$str = '"' . addcslashes($str, "\0..\37\177'\"\\") . '"';
				}
			} else {
				$str = $this->_prepQEncoding($str, $from);
			}
		}
		
		return $str;
	}
	
	private function _toNativeEncoding($str) {
		if ($this->charset != $this->sys_charset) {
			$str = iconv($this->sys_charset, $this->charset, $str);
		}
		
		return $str;
	}
	
	/**
	 * 发送邮件
	 *
	 * @access	public
	 * @return	bool
	 */
	public function send() {
		if ($this->_replyto_flag == FALSE) {
			$this->replyTo($this->_headers['From']);
		}
		
		if ((!isset($this->_recipients) and !isset($this->_headers['To'])) and (!isset($this->_bcc_array) and !isset($this->_headers['Bcc'])) and (!isset($this->_headers['Cc']))) {
			$this->_setErrorMessage('email_no_recipients');
			return FALSE;
		}
		
		$this->_buildHeaders();
		
		if ($this->bcc_batch_mode and count($this->_bcc_array) > 0) {
			if (count($this->_bcc_array) > $this->bcc_batch_size) return $this->batchBccSend();
		}
		
		$this->_buildMessage();
		
		if (!$this->_spoolEmail()) {
			if (TEST_MODEL) {
				echo $this->printDebugger();
			}
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Batch Bcc Send.  Sends groups of BCCs in batches
	 *
	 * @access	private
	 * @return	bool
	 */
	private function batchBccSend() {
		$float = $this->bcc_batch_size - 1;
		
		$set = "";
		
		$chunk = array();
		
		$bccs = count($this->_bcc_array);
		for($i = 0; $i < $bccs; $i++) {
			if (isset($this->_bcc_array[$i])) {
				$set .= ", " . $this->_bcc_array[$i];
			}
			
			if ($i == $float) {
				$chunk[] = substr($set, 1);
				$float = $float + $this->bcc_batch_size;
				$set = "";
			}
			
			if ($i == count($this->_bcc_array) - 1) {
				$chunk[] = substr($set, 1);
			}
		}
		
		$bccs = count($chunk);
		for($i = 0; $i < $bccs; $i++) {
			unset($this->_headers['Bcc']);
			
			$bcc = $this->_strToArray($chunk[$i]);
			$bcc = $this->cleanEmail($bcc);
			
			if ($this->driver != 'smtp') {
				$this->_setHeader('Bcc', implode(", ", $bcc));
			} else {
				$this->_bcc_array = $bcc;
			}
			
			$this->_buildMessage();
			$this->_spoolEmail();
		}
	}

	/**
	 * Unwrap special elements
	 *
	 * @access	private
	 * @return	void
	 */
	private function _unwrapSpecials() {
		$this->_finalbody = preg_replace_callback('/\{unwrap\}(.*?)\{\/unwrap\}/si', array($this, '_removeNewlineCallback'), $this->_finalbody);
	}

	/**
	 * Strip line-breaks via callback
	 *
	 * @access	private
	 * @return	string
	 */
	private function _removeNewlineCallback($matches) {
		if (strpos($matches[1], "\r") !== FALSE or strpos($matches[1], "\n") !== FALSE) {
			$matches[1] = str_replace(array(

			"\r\n", "\r", "\n"), '', $matches[1]);
		}
		
		return $matches[1];
	}

	/**
	 * Spool mail to the mail server
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _spoolEmail() {
		$this->_unwrapSpecials();
		
		switch ($this->_getDriver()) {
			case 'mail' :
				
				if (!$this->_sendWithMail()) {
					$this->_setErrorMessage('email_send_failure_phpmail');
					return FALSE;
				}
				break;
			case 'sendmail' :
				
				if (!$this->_sendWithSendmail()) {
					$this->_setErrorMessage('email_send_failure_sendmail');
					return FALSE;
				}
				break;
			case 'smtp' :
				
				if (!$this->_sendWithSmtp()) {
					$this->_setErrorMessage('email_send_failure_smtp');
					return FALSE;
				}
				break;
		
		}
		
		$this->_setErrorMessage('email_sent', $this->_getDriver());
		return TRUE;
	}

	/**
	 * Send using mail()
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _sendWithMail() {
		if ($this->_safe_mode == TRUE) {
			if (!mail($this->_recipients, $this->_subject, $this->_finalbody, $this->_header_str)) {
				return FALSE;
			} else {
				return TRUE;
			}
		} else {
			// most documentation of sendmail using the "-f" flag lacks a space after it, however
			// we've encountered servers that seem to require it to be in place.
			if (!mail($this->_recipients, $this->_subject, $this->_finalbody, $this->_header_str, "-f " . $this->cleanEmail($this->_headers['From']))) {
				return FALSE;
			} else {
				return TRUE;
			}
		}
	}

	/**
	 * Send using Sendmail
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _sendWithSendmail() {
		$fp = @popen($this->mailpath . " -oi -f " . $this->cleanEmail($this->_headers['From']) . " -t", 'w');
		
		if (!is_resource($fp)) {
			$this->_setErrorMessage('email_no_socket');
			return FALSE;
		}
		
		fputs($fp, $this->_header_str);
		fputs($fp, $this->_finalbody);
		pclose($fp) >> 8 & 0xFF;
		
		return TRUE;
	}

	/**
	 * Send using SMTP
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _sendWithSmtp() {
		if ($this->smtp_host == '') {
			$this->_setErrorMessage('email_no_hostname');
			return FALSE;
		}
		
		$this->_smtpConnect();
		$this->_smtpAuthenticate();
		
		$this->_sendCommand('from', $this->cleanEmail($this->_headers['From']));
		
		foreach ($this->_recipients as $val) {
			$this->_sendCommand('to', $val);
		}
		
		if (count($this->_cc_array) > 0) {
			foreach ($this->_cc_array as $val) {
				if ($val != "") {
					$this->_sendCommand('to', $val);
				}
			}
		}
		
		if (count($this->_bcc_array) > 0) {
			foreach ($this->_bcc_array as $val) {
				if ($val != "") {
					$this->_sendCommand('to', $val);
				}
			}
		}
		
		$this->_sendCommand('data');
		
		// perform dot transformation on any lines that begin with a dot
		$this->_sendData($this->_header_str . preg_replace('/^\./m', '..$1', $this->_finalbody));
		
		$this->_sendData('.');
		
		$reply = $this->_getSmtpData();
		
		$this->_setErrorMessage($reply);
		
		if (strncmp($reply, '250', 3) != 0) {
			$this->_setErrorMessage('email_smtp_error', $reply);
			return FALSE;
		}
		
		$this->_sendCommand('quit');
		return TRUE;
	}

	/**
	 * SMTP Connect
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _smtpConnect() {
		$this->_smtp_connect = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, $this->smtp_timeout);
		
		if (!is_resource($this->_smtp_connect)) {
			$this->_setErrorMessage('email_smtp_error', $errno . " " . $errstr);
			return FALSE;
		}
		
		$this->_setErrorMessage($this->_getSmtpData());
		return $this->_sendCommand('hello');
	}

	/**
	 * Send SMTP command
	 *
	 * @access	private
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	private function _sendCommand($cmd, $data = '') {
		switch ($cmd) {
			case 'hello' :
				
				if ($this->_smtp_auth or $this->_getEncoding() == '8bit')
					$this->_sendData('EHLO ' . $this->_getHostname());
				else
					$this->_sendData('HELO ' . $this->_getHostname());
				
				$resp = 250;
				break;
			case 'from' :
				
				$this->_sendData('MAIL FROM:<' . $data . '>');
				
				$resp = 250;
				break;
			case 'to' :
				
				$this->_sendData('RCPT TO:<' . $data . '>');
				
				$resp = 250;
				break;
			case 'data' :
				
				$this->_sendData('DATA');
				
				$resp = 354;
				break;
			case 'quit' :
				
				$this->_sendData('QUIT');
				
				$resp = 221;
				break;
		}
		
		$reply = $this->_getSmtpData();
		
		$this->_debug_msg[] = "<pre>" . $cmd . ": " . $reply . "</pre>";
		
		if (substr($reply, 0, 3) != $resp) {
			$this->_setErrorMessage('email_smtp_error', $reply);
			return FALSE;
		}
		
		if ($cmd == 'quit') {
			fclose($this->_smtp_connect);
		}
		
		return TRUE;
	}

	/**
	 * SMTP Authenticate
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _smtpAuthenticate() {
		if (!$this->_smtp_auth) {
			return TRUE;
		}
		
		if ($this->smtp_user == "" and $this->smtp_pass == "") {
			$this->_setErrorMessage('email_no_smtp_unpw');
			return FALSE;
		}
		
		$this->_sendData('AUTH LOGIN');
		
		$reply = $this->_getSmtpData();
		
		if (strncmp($reply, '334', 3) != 0) {
			$this->_setErrorMessage('email_failed_smtp_login', $reply);
			return FALSE;
		}
		
		$this->_sendData(base64_encode($this->smtp_user));
		
		$reply = $this->_getSmtpData();
		
		if (strncmp($reply, '334', 3) != 0) {
			$this->_setErrorMessage('email_smtp_auth_un', $reply);
			return FALSE;
		}
		
		$this->_sendData(base64_encode($this->smtp_pass));
		
		$reply = $this->_getSmtpData();
		
		if (strncmp($reply, '235', 3) != 0) {
			$this->_setErrorMessage('email_smtp_auth_pw', $reply);
			return FALSE;
		}
		
		return TRUE;
	}

	/**
	 * Send SMTP data
	 *
	 * @access	private
	 * @return	bool
	 */
	private function _sendData($data) {
		if (!fwrite($this->_smtp_connect, $data . $this->newline)) {
			$this->_setErrorMessage('email_smtp_data_failure', $data);
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Get SMTP data
	 *
	 * @access	private
	 * @return	string
	 */
	private function _getSmtpData() {
		$data = "";
		
		while (($str = fgets($this->_smtp_connect, 512)) !== NULL) {
			$data .= $str;
			
			if (substr($str, 3, 1) == " ") {
				break;
			}
		}
		
		return $data;
	}

	/**
	 * Get Hostname
	 *
	 * @access	private
	 * @return	string
	 */
	private function _getHostname() {
		return (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'localhost.localdomain';
	}

	/**
	 * 取得调试信息
	 *
	 * @access	public
	 * @return	string
	 */
	public function printDebugger() {
		$msg = '';
		
		if (count($this->_debug_msg) > 0) {
			foreach ($this->_debug_msg as $val) {
				$msg .= $val;
			}
		}
		
		$msg .= "<pre>" . $this->_header_str . "\n" . htmlspecialchars($this->_subject) . "\n" . htmlspecialchars($this->_finalbody) . '</pre>';
		return $msg;
	}

	/**
	 * Set Message
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _setErrorMessage($msg, $val = '') {
		$this->_debug_msg[] = str_replace('%s', $val, $msg) . "<br />";
	}

	/**
	 * Mime Types
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _mimeTypes($ext = "") {
		$mimes = array(

		'hqx' => 'application/mac-binhex40', 'cpt' => 'application/mac-compactpro', 'doc' => 'application/msword', 'bin' => 'application/macbinary', 'dms' => 'application/octet-stream', 'lha' => 'application/octet-stream', 'lzh' => 'application/octet-stream', 'exe' => 'application/octet-stream', 'class' => 'application/octet-stream', 'psd' => 'application/octet-stream', 'so' => 'application/octet-stream', 'sea' => 'application/octet-stream', 'dll' => 'application/octet-stream', 'oda' => 'application/oda', 'pdf' => 'application/pdf', 'ai' => 'application/postscript', 'eps' => 'application/postscript', 'ps' => 'application/postscript', 'smi' => 'application/smil', 'smil' => 'application/smil', 'mif' => 'application/vnd.mif', 'xls' => 'application/vnd.ms-excel', 'ppt' => 'application/vnd.ms-powerpoint', 'wbxml' => 'application/vnd.wap.wbxml', 'wmlc' => 'application/vnd.wap.wmlc', 'dcr' => 'application/x-director', 'dir' => 'application/x-director', 'dxr' => 'application/x-director', 'dvi' => 'application/x-dvi', 'gtar' => 'application/x-gtar', 'php' => 'application/x-httpd-php', 'php4' => 'application/x-httpd-php', 'php3' => 'application/x-httpd-php', 'phtml' => 'application/x-httpd-php', 'phps' => 'application/x-httpd-php-source', 'js' => 'application/x-javascript', 'swf' => 'application/x-shockwave-flash', 'sit' => 'application/x-stuffit', 'tar' => 'application/x-tar', 'tgz' => 'application/x-tar', 'xhtml' => 'application/xhtml+xml', 'xht' => 'application/xhtml+xml', 'zip' => 'application/zip', 'mid' => 'audio/midi', 'midi' => 'audio/midi', 'mpga' => 'audio/mpeg', 'mp2' => 'audio/mpeg', 'mp3' => 'audio/mpeg', 'aif' => 'audio/x-aiff', 'aiff' => 'audio/x-aiff', 'aifc' => 'audio/x-aiff', 'ram' => 'audio/x-pn-realaudio', 'rm' => 'audio/x-pn-realaudio', 'rpm' => 'audio/x-pn-realaudio-plugin', 'ra' => 'audio/x-realaudio', 'rv' => 'video/vnd.rn-realvideo', 'wav' => 'audio/x-wav', 'bmp' => 'image/bmp', 'gif' => 'image/gif', 'jpeg' => 'image/jpeg', 'jpg' => 'image/jpeg', 'jpe' => 'image/jpeg', 'png' => 'image/png', 'tiff' => 'image/tiff', 'tif' => 'image/tiff', 'css' => 'text/css', 'html' => 'text/html', 'htm' => 'text/html', 'shtml' => 'text/html', 'txt' => 'text/plain', 'text' => 'text/plain', 'log' => 'text/plain', 'rtx' => 'text/richtext', 'rtf' => 'text/rtf', 'xml' => 'text/xml', 'xsl' => 'text/xml', 'mpeg' => 'video/mpeg', 'mpg' => 'video/mpeg', 'mpe' => 'video/mpeg', 'qt' => 'video/quicktime', 'mov' => 'video/quicktime', 'avi' => 'video/x-msvideo', 'movie' => 'video/x-sgi-movie', 'doc' => 'application/msword', 'word' => 'application/msword', 'xl' => 'application/excel', 'eml' => 'message/rfc822');
		
		return (!isset($mimes[strtolower($ext)])) ? "application/x-unknown-content-type" : $mimes[strtolower($ext)];
	}

}
// END Email class

/* End of file Email.php */
/* Location: ./system/library/Email.php */