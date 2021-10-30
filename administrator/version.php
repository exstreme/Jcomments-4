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
	public $PRODUCT = 'JComments';

	/** @var int Main Release Level */
	public $RELEASE = '4.0';

	/** @var int Sub Release Level */
	public $DEV_LEVEL = '3';

	/** @var string Development Status */
	public $DEV_STATUS = '';

	/** @var int Build Number */
	public $BUILD = '';

	/** @var string Date */
	public $RELDATE = '30/09/2021';

	/**
	 * @return string Long format version
	 */
	public function getLongVersion()
	{
		return trim($this->PRODUCT . ' ' . $this->RELEASE . '.' . $this->DEV_LEVEL . ($this->BUILD ? '.' . $this->BUILD : '') . ' ' . $this->DEV_STATUS);
	}

	/**
	 * @return string Short version format
	 */
	public function getShortVersion()
	{
		return $this->RELEASE . '.' . $this->DEV_LEVEL;
	}

	/**
	 * @return string Version
	 */
	public function getVersion()
	{
		return trim($this->RELEASE . '.' . $this->DEV_LEVEL . ($this->BUILD ? '.' . $this->BUILD : '') . ' ' . $this->DEV_STATUS);
	}

	/**
	 * @return string Release date
	 */
	public function getReleaseDate()
	{
		return $this->RELDATE;
	}
}
