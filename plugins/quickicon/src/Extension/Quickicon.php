<?php
/**
 * JComments quick icon plugin
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Plugin\Quickicon\Jcomments\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Displays an icon for quick access to comments in dashboard
 *
 * @since  4.1
 */
final class Quickicon extends CMSPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  4.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 * @since  3.8.0
	 */
	protected $app;

	/**
	 * Show jcomments icon.
	 *
	 * @param   string  $context  The calling context
	 *
	 * @return  array   A list of icon definition associative arrays
	 *
	 * @since   3.9.0
	 */
	public function onGetIcons(string $context): array
	{
		$app = $this->getApplication() ?: $this->app;

		if ($context !== $this->params->get('context', 'mod_quickicon')
			|| !$app->getIdentity()->authorise('core.manage', 'com_jcomments'))
		{
			return array();
		}

		$text = $this->params->get('displayedtext');

		if (empty($text))
		{
			$text = Text::_('COM_JCOMMENTS_COMMENTS');
		}

		return array(
			array(
				'link'   => 'index.php?option=com_jcomments&view=comments',
				'image'  => 'icon-comment-dots',
				'text'   => $text,
				'access' => array('core.manage', 'com_jcomments'),
				'id'     => 'plg_quickicon_jcomments'
			)
		);
	}
}
