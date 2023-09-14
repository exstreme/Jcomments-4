<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Component\Jcomments\Site\Event;

defined('_JEXEC') or die;

use BadMethodCallException;
use Joomla\CMS\Event\AbstractImmutableEvent;
use Joomla\CMS\Event\Result\ResultAware;
use Joomla\CMS\Event\Result\ResultAwareInterface;
use Joomla\CMS\Event\Result\ResultTypeArrayAware;

/**
 * Event class for basic form events
 *
 * @since  4.1
 */
class FormEvent extends AbstractImmutableEvent implements ResultAwareInterface
{
	use ResultAware;
	use ResultTypeArrayAware;

	/**
	 * Constructor.
	 *
	 * @param   string  $name       The event name.
	 * @param   array   $arguments  The event arguments.
	 *
	 * @throws  BadMethodCallException
	 *
	 * @since   4.1
	 */
	public function __construct($name, array $arguments = [])
	{
		parent::__construct($name, $arguments);
	}

	/**
	 * Set abort parameter to true
	 *
	 * @param   string  $reason  The abort reason text
	 *
	 * @return  void
	 *
	 * @since   4.1
	 */
	public function setAbort(string $reason)
	{
		$this->arguments['abort'] = true;
		$this->arguments['abortReason'] = $reason;
	}
}
