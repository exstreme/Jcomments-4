<?php
/**
 * JComments - Joomla Comment System
 *
 * @version 3.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;

class plgQuickiconJComments extends CMSPlugin
{
	public function onGetIcons($context)
	{
		$app = Factory::getApplication();

		if ($context == $this->params->get('context', 'mod_quickicon')
			&& $app->getIdentity()->authorise('core.manage', 'com_jcomments'))
		{

			$app->getLanguage()->load('com_jcomments.sys', JPATH_ADMINISTRATOR, 'en-GB', true);
			$this->loadLanguage('com_jcomments.sys', JPATH_ADMINISTRATOR);

			$text = $this->params->get('displayedtext');

			if (empty($text))
			{
				$text = Text::_('COM_JCOMMENTS');
			}

			return array(
				array(
					'link'   => 'index.php?option=com_jcomments',
					'image'  => 'comments',
					'text'   => $text,
					'access' => array('core.manage', 'com_jcomments'),
					'id'     => 'plg_quickicon_jcomments'
				)
			);
		}
		else
		{
			return array();
		}
	}
}
