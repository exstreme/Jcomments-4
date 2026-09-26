<?php
/**
 * Simple AJAX library (based on code XAJAX library - http://www.xajaxproject.org)
 *
 * @version 1.0
 * @package JoomlaTune.Framework
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

// Check for double include
if (!defined ('JOOMLATUNE_AJAX'))
{
	define ('JOOMLATUNE_AJAX', 1);

	class JoomlaTuneAjaxResponse
	{
		var $aCommands;
		var $xml;
		var $sEncoding;

		function __construct($sEncoding='utf-8')
		{
			$this->aCommands = array();
			$this->sEncoding = $sEncoding;
		}

		function addCommand($aAttributes, $mData)
		{
			$aAttributes['d'] = $mData;
			$this->aCommands[] = $aAttributes;
		}

		/**
		 * Assign HTML (or value) to an element. Embedded <script> blocks are removed, they are never executed.
		 * Client side initialization must be done via jtajax.onAssign() callback.
		 */
		function addAssign($sTarget,$sAttribute,$sData)
		{
			$sData = preg_replace('#<script\b[^>]*>.*?</script>#is', '', (string) $sData);

			$this->addCommand(array('n'=>'as','t'=>$sTarget,'p'=>$sAttribute),$sData);

			return $this;
		}

		/**
		 * Call client side action registered via jtajax.register(name, callback).
		 *
		 * @param   string  $sAction  Action name, e.g. 'jcomments.updateList'
		 * @param   array   $aArgs    List of scalar arguments
		 */
		function addCall($sAction, array $aArgs = array())
		{
			$this->addCommand(array('n'=>'call','t'=>$sAction),array_values($aArgs));
			return $this;
		}

		/**
		 * Execute raw JavaScript on client. Does not work with Content-Security-Policy without 'unsafe-eval'.
		 *
		 * @deprecated  5.0.5  Use addCall() with an action registered via jtajax.register().
		 */
		function addScript($sJS)
		{
			$this->addCommand(array('n'=>'js'),str_replace("\r", '', $sJS));
			return $this;
		}

		function addAlert($sMsg)
		{
			$this->addCommand(array('n'=>'al'),$sMsg);
			return $this;
		}

		function getOutput()
		{
			$flags = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;

			try {
				$output = json_encode(is_array($this->aCommands) ? $this->aCommands : array(), $flags);
			} catch (JsonException $e) {
				$output = json_encode(array(array('n'=>'al','d'=>'JSON error: '.$e->getMessage())), $flags);
			}
			if (trim($this->sEncoding)) {
				@header('content-type: application/json; charset="'.$this->sEncoding.'"');
			}
			return $output;
		}

		/**
		* This function taken from JsHttpRequest library
		* JsHttpRequest: PHP backend for JavaScript DHTML loader.
		* (C) Dmitry Koterov, http://en.dklab.ru
		*
		* Convert a PHP scalar, array or hash to JS scalar/array/hash. This function is
		* an analog of json_encode(), but it can work with a non-UTF8 input and does not
		* analyze the passed data. Output format must be fully JSON compatible.
		*
		* @param mixed $a   Any structure to convert to JS.
		* @return string    JavaScript equivalent structure.
		*/
		function php2js($a=false)
		{
			if (is_null($a)) return 'null';
			if ($a === false) return 'false';
			if ($a === true) return 'true';
			if (is_scalar($a)) {
				if (is_float($a)) {
					$a = str_replace(",", ".", strval($a));
				}
				// All scalars are converted to strings to avoid indeterminism.
				// PHP's "1" and 1 are equal for all PHP operators, but
				// JS's "1" and 1 are not. So if we pass "1" or 1 from the PHP backend,
				// we should get the same result in the JS frontend (string).
				// Character replacements for JSON.
				static $jsonReplaces = array(
				array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'),
				array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"')
				);
				return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
			}
			$isList = true;
			for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
				if (key($a) !== $i) {
					$isList = false;
					break;
				}
			}
			$result = array();
			if ($isList) {
				foreach ($a as $v) {
					$result[] = JoomlaTuneAjaxResponse::php2js($v);
				}
				return '[ ' . join(', ', $result) . ' ]';
			} else {
				foreach ($a as $k => $v) {
					$k = JoomlaTuneAjaxResponse::php2js($k);
					$v = JoomlaTuneAjaxResponse::php2js($v);
					$result[] = $k . ': ' . $v;
				}
				return '{ ' . join(', ', $result) . ' }';
			}
		}
	}

	class JoomlaTuneAjax
	{
		var $aFunctions;
		var $aObjects;
		var $aFunctionRequestTypes;
		var $aFunctionIncludeFiles;
		var $sRequestURI;
		var $sEncoding;

		function __construct($sRequestURI="",$sEncoding='utf-8')
		{
			$this->aFunctions = array();
			$this->aFunctionRequestTypes = array();
			$this->aObjects = array();
			$this->aFunctionIncludeFiles = array();
			$this->sRequestURI = $sRequestURI;
			if ($this->sRequestURI == "") {
				$this->sRequestURI = $this->_detectURI();
			}
			$this->setCharEncoding($sEncoding);
		}

		function setCharEncoding($sEncoding)
		{
			$this->sEncoding = $sEncoding;
		}

		function registerFunction($mFunction,$sRequestType=1)
		{
			if (is_array($mFunction)) {
				$this->aFunctions[$mFunction[0]] = 1;
				$this->aFunctionRequestTypes[$mFunction[0]] = $sRequestType;
				$this->aObjects[$mFunction[0]] = array_slice($mFunction, 1);
			} else {
				$this->aFunctions[$mFunction] = 1;
				$this->aFunctionRequestTypes[$mFunction] = $sRequestType;
			}
		}

		function processRequest()
		{
			return $this->processRequests();
		}

		function _isObjectCallback($sFunction)
		{
			if (array_key_exists($sFunction, $this->aObjects)) {
				return true;
			}
			return false;
		}
		function _callFunction($sFunction, $aArgs)
		{
			if ($this->_isObjectCallback($sFunction)) {
				$mReturn = call_user_func_array($this->aObjects[$sFunction], $aArgs);
			} else if (array_key_exists($sFunction, $this->aFunctions)) {
				$mReturn = call_user_func_array($sFunction, $aArgs);
			}
			return $mReturn;
		}

		function processRequests()
		{
			$sFunctionName = $_REQUEST["jtxf"];
			$aArgs = isset($_REQUEST["jtxa"]) ? $_REQUEST["jtxa"] : array();

			if (!array_key_exists($sFunctionName, $this->aFunctions)) {
				$oResponse = new JoomlaTuneAjaxResponse();
				$oResponse->addAlert("Unknown Function $sFunctionName.");
			} else {
				$oResponse = $this->_callFunction($sFunctionName, $aArgs);
			}
			@header('content-type: application/json; charset="'.$this->sEncoding.'"');
			print $oResponse->getOutput();
			exit();
		}

		function _detectURI() {
			$aURL = array();

			// Try to get the request URL
			if (!empty($_SERVER['REQUEST_URI'])) {
				$_SERVER['REQUEST_URI'] = str_replace(array('"',"'",'<','>'), array('%22','%27','%3C','%3E'), $_SERVER['REQUEST_URI']);
				$aURL = parse_url($_SERVER['REQUEST_URI']);
			}

			// Fill in the empty values
			if (empty($aURL['scheme'])) {
				if (!empty($_SERVER['HTTP_SCHEME'])) {
					$aURL['scheme'] = $_SERVER['HTTP_SCHEME'];
				} else {
					$aURL['scheme'] = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') ? 'https' : 'http';
				}
			}

			if (empty($aURL['host'])) {
				if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
					if (strpos($_SERVER['HTTP_X_FORWARDED_HOST'], ':') > 0) {
						list($aURL['host'], $aURL['port']) = explode(':', $_SERVER['HTTP_X_FORWARDED_HOST']);
					} else {
						$aURL['host'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
					}
				} else if (!empty($_SERVER['HTTP_HOST'])) {
					if (strpos($_SERVER['HTTP_HOST'], ':') > 0) {
						list($aURL['host'], $aURL['port']) = explode(':', $_SERVER['HTTP_HOST']);
					} else {
						$aURL['host'] = $_SERVER['HTTP_HOST'];
					}
				} else if (!empty($_SERVER['SERVER_NAME'])) {
					$aURL['host'] = $_SERVER['SERVER_NAME'];
				} else {
					print "Error: ajax failed to automatically identify your Request URI.";
					print "Please set the Request URI explicitly when you instantiate the jtajax object.";
					exit();
				}
			}

			if (empty($aURL['port']) && !empty($_SERVER['SERVER_PORT'])) {
				$aURL['port'] = $_SERVER['SERVER_PORT'];
			}

			if (empty($aURL['path'])) {
				if (!empty($_SERVER['PATH_INFO'])) {
					$sPath = parse_url($_SERVER['PATH_INFO']);
				} else {
					$sPath = parse_url($_SERVER['PHP_SELF']);
				}
				$aURL['path'] = str_replace(array('"',"'",'<','>'), array('%22','%27','%3C','%3E'), $sPath['path']);
				unset($sPath);
			}

			if (!empty($aURL['query'])) {
				$aURL['query'] = '?'.$aURL['query'];
			}

			// Build the URL: Start with scheme, user and pass
			$sURL = $aURL['scheme'].'://';
			if (!empty($aURL['user'])) {
				$sURL.= $aURL['user'];
				if (!empty($aURL['pass'])) {
					$sURL.= ':'.$aURL['pass'];
				}
				$sURL.= '@';
			}

			// Add the host
			$sURL.= $aURL['host'];

			// Add the port if needed
			if (!empty($aURL['port']) && (($aURL['scheme'] == 'http' && $aURL['port'] != 80) || ($aURL['scheme'] == 'https' && $aURL['port'] != 443))) {
				$sURL.= ':'.$aURL['port'];
			}

			// Add the path and the query string
			$sURL.= $aURL['path'].@$aURL['query'];

			// Clean up
			unset($aURL);
			return $sURL;
		}
	}
} // end of double include check
