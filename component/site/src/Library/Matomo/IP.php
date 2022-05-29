<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik;

defined('_JEXEC') or die;

use Joomla\Filter\InputFilter;
use Matomo\Network\IPUtils;

/**
 * Contains IP address helper functions (for both IPv4 and IPv6).
 *
 * As of Piwik 2.9, most methods in this class are deprecated. You are
 * encouraged to use classes from the Piwik "Network" component:
 *
 * @see  \Matomo\Network\IP
 * @see  \Matomo\Network\IPUtils
 * @link https://github.com/matomo-org/component-network
 *
 * As of Piwik 1.3, IP addresses are stored in the DB has VARBINARY(16),
 * and passed around in network address format which has the advantage of
 * being in big-endian byte order. This allows for binary-safe string
 * comparison of addresses (of the same length), even on Intel x86.
 *
 * As a matter of naming convention, we use `$ip` for the network address format
 * and `$ipString` for the presentation format (i.e., human-readable form).
 *
 * We're not using the network address format (in_addr) for socket functions,
 * so we don't have to worry about incompatibility with Windows UNICODE
 * and inetPtonW().
 *
 * @api
 */
class IP
{
	/**
	 * Returns the most accurate IP address available for the current user, in
	 * IPv4 format. This could be the proxy client's IP address.
	 *
	 * @param   array  $options  Options. 'proxy_client_headers' and 'proxy_ips' as arrays.
	 *
	 * @return  string  IP address in presentation format.
	 *
	 * @since   4.0
	 * @see     https://matomo.org/faq/how-to-install/faq_98/
	 */
	public static function getIpFromHeader($options)
	{
		$clientHeaders = @$options['proxy_client_headers'];

		if (!is_array($clientHeaders))
		{
			$clientHeaders = array();
		}

		$default = '0.0.0.0';

		if (isset($_SERVER['REMOTE_ADDR']))
		{
			$default = $_SERVER['REMOTE_ADDR'];
		}

		$ipString = self::getNonProxyIpFromHeader($default, $clientHeaders, $options);

		return IPUtils::sanitizeIp($ipString);
	}

	/**
	 * Returns a non-proxy IP address from header.
	 *
	 * @param   string  $default       Default value to return if there no matching proxy header.
	 * @param   array   $proxyHeaders  List of proxy headers.
	 * @param   array   $options       Options. 'proxy_client_headers' and 'proxy_ips' as arrays.
	 *
	 * @return  string
	 *
	 * @see     https://matomo.org/faq/how-to-install/faq_98/
	 * @since   4.0
	 */
	public static function getNonProxyIpFromHeader($default, $proxyHeaders, $options)
	{
		$proxyIps = array();

		if (isset($options['proxy_ips']))
		{
			$proxyIps = $options['proxy_ips'];
		}

		if (!is_array($proxyIps))
		{
			$proxyIps = array();
		}

		if (!$options['proxy_ip_read_last_in_list'])
		{
			$proxyIps[] = $default;
		}

		// Examine proxy headers
		foreach ($proxyHeaders as $proxyHeader)
		{
			if (!empty($_SERVER[$proxyHeader]))
			{
				/*
				 * This may be buggy if someone has proxy IPs and proxy host headers configured as
				 * `$_SERVER[$proxyHeader]` could be eg $_SERVER['HTTP_X_FORWARDED_HOST'] and
				 * include an actual host name, not an IP
				 */
				if ($options['proxy_ip_read_last_in_list'])
				{
					$proxyIp = self::getLastIpFromList($_SERVER[$proxyHeader], $proxyIps);
				}
				else
				{
					$proxyIp = self::getFirstIpFromList($_SERVER[$proxyHeader], $proxyIps);
				}

				if (strlen($proxyIp) && stripos($proxyIp, 'unknown') === false)
				{
					return $proxyIp;
				}
			}
		}

		return $default;
	}

	/**
	 * Returns the last IP address in a comma separated list, subject to an optional exclusion list.
	 *
	 * @param   string  $csv          Comma separated list of elements.
	 * @param   array   $excludedIps  Optional list of excluded IP addresses (or IP address ranges).
	 *
	 * @return  string  Last (non-excluded) IP address in the list or an empty string if all given IPs are excluded.
	 *
	 * @since   4.0
	 */
	public static function getFirstIpFromList($csv, $excludedIps = null)
	{
		$filter = new InputFilter;
		$p = strrpos($csv, ',');

		if ($p !== false)
		{
			$elements = self::getIpsFromList($csv, $excludedIps);

			return reset($elements) ?: '';
		}

		return trim($filter->clean($csv));
	}

	public static function getLastIpFromList($csv, $excludedIps = null)
	{
		$filter = new InputFilter;
		$p = strrpos($csv, ',');

		if ($p !== false)
		{
			$elements = self::getIpsFromList($csv, $excludedIps);

			return end($elements) ?: '';
		}

		return trim($filter->clean($csv));
	}

	private static function getIpsFromList(string $csv, ?array $excludedIps)
	{
		$filter = new InputFilter;
		$result = [];
		$elements = explode(',', $csv);

		foreach ($elements as $ipString)
		{
			$element = trim($filter->clean($ipString));

			if (empty($element))
			{
				continue;
			}

			$ip = \Matomo\Network\IP::fromStringIP(IPUtils::sanitizeIp($element));

			if (empty($excludedIps) || (!in_array($element, $excludedIps) && !$ip->isInRanges($excludedIps)))
			{
				$result[] = $element;
			}
		}

		return $result;
	}
}
