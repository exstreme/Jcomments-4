<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

class JCommentsVersion
{
	/** @var string Product */
	var $PRODUCT = 'JComments';
	/** @var int Main Release Level */
	var $RELEASE = '4.0';
	/** @var int Sub Release Level */
	var $DEV_LEVEL = '1';
	/** @var string Development Status */
	var $DEV_STATUS = '';
	/** @var int Build Number */
	var $BUILD = '';
	/** @var string Date */
	var $RELDATE = '30/09/2021';

	/**
	 * @return string Long format version
	 */
	function getLongVersion()
	{
		return trim($this->PRODUCT . ' ' . $this->RELEASE . '.' . $this->DEV_LEVEL . ($this->BUILD ? '.' . $this->BUILD : '') . ' ' . $this->DEV_STATUS);
	}

	/**
	 * @return string Short version format
	 */
	function getShortVersion()
	{
		return $this->RELEASE . '.' . $this->DEV_LEVEL;
	}

	/**
	 * @return string Version
	 */
	function getVersion()
	{
		return trim($this->RELEASE . '.' . $this->DEV_LEVEL . ($this->BUILD ? '.' . $this->BUILD : '') . ' ' . $this->DEV_STATUS);
	}

	/**
	 * @return string Release date
	 */
	function getReleaseDate()
	{
		return $this->RELDATE;
	}
}
