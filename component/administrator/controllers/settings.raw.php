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

use Joomla\CMS\Access\Access;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Settings controller.
 *
 * @since  1.6
 */
class JCommentsControllerSettings extends BaseController
{
	/**
	 * Method to save component configuration into json file.
	 *
	 * @return  void
	 *
	 * @since   3.0
	 */
	public function saveConfig()
	{
		/** @var CMSApplication $app */
		$app = Factory::getApplication();

		// Check if the user is authorized to do this.
		if (!$app->getIdentity()->authorise('core.admin', 'com_jcomments'))
		{
			$this->setRedirect('index.php?option=com_jcomments&view=settings', Text::_('JLIB_RULES_NOT_ALLOWED'), 'error');
		}

		$params   = ComponentHelper::getParams('com_jcomments');
		$document = $app->getDocument();
		$document->setMimeEncoding('application/octet-stream');

		$app->setHeader('Pragma', 'no-cache', true);
		$app->setHeader('Expires', '-1');
		$app->setHeader('Cache-Control', 'public, no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true);
		$app->setHeader('Content-Transfer-Encoding', 'Binary');
		$app->setHeader(
			'Content-disposition',
			'attachment; filename="com_jcomments-settings-' . HTMLHelper::_('date', time(), 'Y-m-d_H-i-s') . '.json"'
		);
		$app->sendHeaders();

		$_access = new Access;
		$_access->preload('com_jcomments');
		$access = $_access->getAssetRules('com_jcomments');

		$accessArray['access'] = json_decode((string) $access, true);
		$paramsArray['params'] = $params->toArray();

		echo json_encode(array_merge($accessArray, $paramsArray));
	}
}
