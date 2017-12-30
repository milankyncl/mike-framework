<?php

/**
 * Mike Framework | https://milankyncl.cz/mike
 * Copyright (c) 2017 Milan Kyncl | https://milankyncl.cz
 */

namespace Mike\Http;

use Mike\Exception;
use Mike\DependencyContainer\Injectable;
use Mike\Http\Response\Headers;
use Mike\Http\Response\Cookies;


class Response extends Injectable {

	protected $_sent = false;

	protected $_content;

	protected $_headers;

	protected $_cookies;

	protected $_file;

	/**
	 * Response constructor.
	 *
	 * @param string $content
	 * @param int $code
	 * @param string $status
	 */

	public function __construct($content = null, $code = null, $status = null) {

		$this->_headers = new Headers();

		if(!is_null($content))
			$this->_content = $content;

		if(!is_null($code))
			$this->setStatusCode($code, $status);

	}

	/**
	 * Sets the HTTP response code
	 *
	 * @param int $code
	 * @param string $message
	 *
	 * @return Response
	 */

	public function setStatusCode($code, $message = null) {

		$headers = $this->getHeaders();
		$currentHeadersRaw = $headers->toArray();

		if(is_array($currentHeadersRaw)) {

			foreach($currentHeadersRaw as $headerKey => $headerValue) {

				if(is_string($headerKey) && strstr($headerKey, 'HTTP/'))
					$headers->remove($headerKey);

			}

		}

		if(is_null($message)) {

			if(!isset($statusCodes[$code]))
				throw new Exception("Non-standard statuscode given without a message");

			$defaultMessage = self::STATUS_MESSAGES[$code];
			$message = $defaultMessage;
		}

		$headers->setRaw('HTTP/1.1 ' . $code . ' ' . $message);

		$headers->set('Status', $code . ' ' . $message);

		return $this;
	}

	/**
	 * Returns the status code
	 *
	 * @return int|null
	 */

	public function getStatusCode() {

		$statusCode = substr($this->getHeaders()->get('Status'), 0, 3);

		return $statusCode ? (int) $statusCode : null;
	}

	/**
	 * Sets a headers bag for the response externally
	 *
	 * @param Headers $headers
	 *
	 * @return Response
	 */

	public function setHeaders(Headers $headers) {

		$this->_headers = $headers;

		return $this;
	}

	/**
	 * Returns headers set by the user
	 *
	 * @return Headers
	 */

	public function getHeaders() {

		return $this->_headers;
	}

	/**
	 * Sets a cookies bag for the response externally
	 *
	 * @return Response
	 */

	public function setCookies(Cookies $cookies) {

		$this->_cookies = $cookies;

		return $this;
	}

	/**
	 * Returns cookies set by the user
	 *
	 * @return Cookies
	 */

	public function getCookies() {

		return $this->_cookies;
	}

	/**
	 * Overwrites a header in the response
	 *
	 * @param string $name
	 * @param string $value
	 *
	 * @return Response
	 */

	public function setHeader($name, $value) {

		$headers = $this->getHeaders();

		$headers->set($name, $value);

		return $this;
	}

	/**
	 * Send a raw header to the response
	 *
	 * @param string $header
	 *
	 * @return Response
	 */

	public function setRawHeader($header) {

		$headers = $this->getHeaders();
		$headers->setRaw($header);

		return $this;
	}

	/**
	 * Resets all the established headers
	 *
	 * @return Response
	 */

	public function resetHeaders() {

		$headers = $this->getHeaders();
		$headers->reset();

		return $this;
	}

	/**
	 * Sets an Expires header in the response that allows to use the HTTP cache
	 *
	 * @param \DateTime $datetime
	 *
	 * @return Response
	 */

	public function setExpires(\DateTime $datetime) {


		$date = clone $datetime;

		$date->setTimezone(new \DateTimeZone('UTC'));
		$this->setHeader('Expires', $date->format('D, d M Y H:i:s') . ' GMT');

		return $this;
	}

	/**
	 * Sets Last-Modified header
	 *
	 * @param \DateTime $datetime
	 *
	 * @return Response
	 */

	public function setLastModified(\DateTime $datetime) {

		$date = clone $datetime;

		$date->setTimezone(new \DateTimeZone('UTC'));
		$this->setHeader('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');

		return $this;
	}

	/**
	 * Sets Cache headers to use HTTP cache
	 *
	 * @param int $minutes
	 *
	 * @return Response
	 */

	public function setCache($minutes) {

		$date = new \DateTime();

		$date->modify('+' . $minutes . ' minutes');

		$this->setExpires($date);
		$this->setHeader('Cache-Control', 'max-age=' . ($minutes * 60));

		return $this;
	}

	/**
	 * Sends a Not-Modified response
	 *
	 * @return Response
	 */

	public function setNotModified() {

		$this->setStatusCode(304, 'Not modified');

		return $this;
	}

	/**
	 * Sets the response content-type mime, optionally the charset
	 *
	 * @param string $contentType
	 * @param string $charset
	 *
	 * @return Response
	 */

	public function setContentType($contentType, $charset = null) {

		if(is_null($charset))
			$this->setHeader('Content-Type', $contentType);

		else
			$this->setHeader('Content-Type', $contentType . '; charset=' . $charset);

		return $this;
	}

	/**
	 * Sets the response content-length
	 *
	 * @param int $contentLength
	 *
	 * @return Response
	 */

	public function setContentLength($contentLength) {

		$this->setHeader('Content-Length', $contentLength);

		return $this;
	}

	/**
	 * Set a custom ETag
	 *
	 * @param string $etag
	 *
	 * return Response
	 */

	public function setEtag($etag) {

		$this->setHeader('Etag', $etag);

		return $this;
	}

	/**
	 * Redirect by HTTP to another action or URL
	 *
	 * @param string $location
	 * @param bool $externalRedirect
	 * @param int $statusCode
	 *
	 * @return Response
	 *
	 * @throws Exception
	 */

	public function redirect($location = null, $externalRedirect = false, $statusCode = 302) {

		$header = null;

		if($externalRedirect) {

			$header = $location;

		} else {

			if(is_string($location) && strstr($location, '://')) {

				$matched = preg_match('/^[^:\\/?#]++:/', $location);

				if($matched)
					$header = $location;

			}
		}

		if(!$header) {

			/** @var \Mike\Mvc\Url $url */

			$url = $this->_dependencyContainer->get('url');
			$header = $url->get($location);

		}

		if($this->_dependencyContainer->has('view')) {

			$view = $this->_dependencyContainer->get('view');
			$view->disable();

		}

		if($statusCode < 300 || $statusCode > 308)
			throw new Exception('Status code is not valid for redirect.');

		$this->setStatusCode($statusCode);

		$this->setHeader('Location', $header);

		return $this;
	}

	/**
	 * Sets HTTP response body
	 *
	 * @param string $content
	 *
	 * @return Response
	 */

	public function setContent($content) {

		$this->_content = $content;

		return $this;
	}

	/**
	 * Sets HTTP response body. The parameter is automatically converted to JSON
	 * and also sets default header: Content-Type: "application/json; charset=UTF-8"
	 *
	 * @param mixed $content
	 * @param int $jsonOptions
	 * @param int $depth
	 *
	 * @return Response
	 */

	public function setJsonContent($content, $jsonOptions = 0, $depth = 512) {

		$this->setContentType('application/json', 'UTF-8');
		$this->setContent(json_encode($content, $jsonOptions, $depth));

		return $this;
	}

	/**
	 * Appends a string to the HTTP response body
	 *
	 * @param string $content
	 *
	 * @return Response
	 */

	public function appendContent($content) {

		$this->_content = $this->getContent() . $content;

		return $this;
	}

	/**
	 * Gets the HTTP response body
	 *
	 * @return string
	 */

	public function getContent() {

		return $this->_content;
	}

	/**
	 * Check if the response is already sent
	 *
	 * @return bool
	 */

	public function isSent() {

		return $this->_sent;
	}

	/**
	 * Sends headers to the client
	 *
	 * @return Response
	 */

	public function sendHeaders() {

		$this->_headers->send();

		return $this;
	}

	/**
	 * Sends cookies to the client
	 *
	 * @return Response
	 */

	public function sendCookies() {

		/** @var Cookies $cookies */

		$cookies = $this->_cookies;

		if(is_object($cookies))
			$cookies->send();

		return $this;
	}

	/**
	 * Prints out HTTP response to the client
	 *
	 * @return Response
	 */

	public function send() {


		if($this->_sent)
			throw new Exception('Response was already sent.');

		$this->sendHeaders();

		$this->sendCookies();

		$content = $this->_content;

		if(!is_null($content)) {

			echo $content;

		} else {

			$file = $this->_file;

			if(is_string($file) && strlen($file))
				readfile($file);
		}

		$this->_sent = true;

		return $this;
	}

	/**
	 * Sets an attached file to be sent at the end of the request
	 *
	 * @param string $filePath
	 * @param string $attachmentName
	 * @param bool $attachment
	 *
	 * @return Response
	 */

	public function setFileToSend($filePath, $attachmentName = null, $attachment = true) {


		if(is_string($attachmentName))
			$basePath = basename($filePath);
		else
			$basePath = $attachmentName;

		if($attachment) {
			$this->setRawHeader('Content-Description: File Transfer');
			$this->setRawHeader('Content-Type: application/octet-stream');
			$this->setRawHeader('Content-Disposition: attachment; filename=' . $basePath);
			$this->setRawHeader('Content-Transfer-Encoding: binary');
		}

		$this->_file = $filePath;

		return $this;
	}

	/**
	 * Remove a header in the response
	 *
	 * @param string $name
	 *
	 * @return Response
	 */

	public function removeHeader($name) {

		$headers = $this->getHeaders();
		$headers->remove($name);

		return $this;
	}

	/**
	 * Status messages
	 */

	const STATUS_MESSAGES = [
		// INFORMATIONAL CODES
		100 => "Continue",                        // RFC 7231, 6.2.1
		101 => "Switching Protocols",             // RFC 7231, 6.2.2
		102 => "Processing",                      // RFC 2518, 10.1
		103 => "Early Hints",
		// SUCCESS CODES
		200 => "OK",                              // RFC 7231, 6.3.1
		201 => "Created",                         // RFC 7231, 6.3.2
		202 => "Accepted",                        // RFC 7231, 6.3.3
		203 => "Non-Authoritative Information",   // RFC 7231, 6.3.4
		204 => "No Content",                      // RFC 7231, 6.3.5
		205 => "Reset Content",                   // RFC 7231, 6.3.6
		206 => "Partial Content",                 // RFC 7233, 4.1
		207 => "Multi-status",                    // RFC 4918, 11.1
		208 => "Already Reported",                // RFC 5842, 7.1
		226 => "IM Used",                         // RFC 3229, 10.4.1
		// REDIRECTION CODES
		300 => "Multiple Choices",                // RFC 7231, 6.4.1
		301 => "Moved Permanently",               // RFC 7231, 6.4.2
		302 => "Found",                           // RFC 7231, 6.4.3
		303 => "See Other",                       // RFC 7231, 6.4.4
		304 => "Not Modified",                    // RFC 7232, 4.1
		305 => "Use Proxy",                       // RFC 7231, 6.4.5
		306 => "Switch Proxy",                    // RFC 7231, 6.4.6 (Deprecated)
		307 => "Temporary Redirect",              // RFC 7231, 6.4.7
		308 => "Permanent Redirect",              // RFC 7538, 3
		// CLIENT ERROR
		400 => "Bad Request",                     // RFC 7231, 6.5.1
		401 => "Unauthorized",                    // RFC 7235, 3.1
		402 => "Payment Required",                // RFC 7231, 6.5.2
		403 => "Forbidden",                       // RFC 7231, 6.5.3
		404 => "Not Found",                       // RFC 7231, 6.5.4
		405 => "Method Not Allowed",              // RFC 7231, 6.5.5
		406 => "Not Acceptable",                  // RFC 7231, 6.5.6
		407 => "Proxy Authentication Required",   // RFC 7235, 3.2
		408 => "Request Time-out",                // RFC 7231, 6.5.7
		409 => "Conflict",                        // RFC 7231, 6.5.8
		410 => "Gone",                            // RFC 7231, 6.5.9
		411 => "Length Required",                 // RFC 7231, 6.5.10
		412 => "Precondition Failed",             // RFC 7232, 4.2
		413 => "Request Entity Too Large",        // RFC 7231, 6.5.11
		414 => "Request-URI Too Large",           // RFC 7231, 6.5.12
		415 => "Unsupported Media Type",          // RFC 7231, 6.5.13
		416 => "Requested range not satisfiable", // RFC 7233, 4.4
		417 => "Expectation Failed",              // RFC 7231, 6.5.14
		418 => "I'm a teapot",                    // RFC 7168, 2.3.3
		421 => "Misdirected Request",
		422 => "Unprocessable Entity",            // RFC 4918, 11.2
		423 => "Locked",                          // RFC 4918, 11.3
		424 => "Failed Dependency",               // RFC 4918, 11.4
		425 => "Unordered Collection",
		426 => "Upgrade Required",                // RFC 7231, 6.5.15
		428 => "Precondition Required",           // RFC 6585, 3
		429 => "Too Many Requests",               // RFC 6585, 4
		431 => "Request Header Fields Too Large", // RFC 6585, 5
		451 => "Unavailable For Legal Reasons",   // RFC 7725, 3
		499 => "Client Closed Request",
		// SERVER ERROR
		500 => "Internal Server Error",           // RFC 7231, 6.6.1
		501 => "Not Implemented",                 // RFC 7231, 6.6.2
		502 => "Bad Gateway",                     // RFC 7231, 6.6.3
		503 => "Service Unavailable",             // RFC 7231, 6.6.4
		504 => "Gateway Time-out",                // RFC 7231, 6.6.5
		505 => "HTTP Version not supported",      // RFC 7231, 6.6.6
		506 => "Variant Also Negotiates",         // RFC 2295, 8.1
		507 => "Insufficient Storage",            // RFC 4918, 11.5
		508 => "Loop Detected",                   // RFC 5842, 7.2
		510 => "Not Extended",                    // RFC 2774, 7
		511 => "Network Authentication Required"  // RFC 6585, 6
	];

}