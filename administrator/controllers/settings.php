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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

class JCommentsControllerSettings extends BaseController
{
	function display($cachable = false, $urlparams = array())
	{
		Factory::getApplication()->input->set('view', 'default');

		parent::display($cachable, $urlparams);
	}

	public function save()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app    = Factory::getApplication();
		$base64 = $app->input->get('base64', '');

		if (!empty($base64))
		{
			$base64 = base64_decode(urldecode($base64));
			parse_str($base64, $data);

			foreach ($data as $k => $v)
			{
				$app->input->post->set($k, $v);
			}
		}

		$model = $this->getModel('Settings', 'JCommentsModel', array('ignore_request' => true));
		$data  = $app->input->post->get('jform', array(), 'array');

		$language = $app->input->post->get('language', '', 'string');
		$model->setState($model->getName() . '.language', $language);

		if ($model->save($data) === false)
		{
			$this->setRedirect(
				Route::_('index.php?option=com_jcomments&view=settings', false),
				Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()),
				'error'
			);

			return false;
		}
		else
		{
			$this->getModel('Smiley')->saveLegacy();
		}

		$captchaEngine = JCommentsFactory::getConfig()->get('captcha_engine', 'kcaptcha');

		if ($captchaEngine == 'kcaptcha')
		{
			if (!extension_loaded('gd') || !function_exists('imagecreatefrompng'))
			{
				Factory::getApplication()->enqueueMessage(Text::_('A_WARNINGS_PHP_GD'), 'warning');
			}
		}

		$cache = Factory::getCache('com_jcomments');
		$cache->clean();

		$this->setRedirect(Route::_('index.php?option=com_jcomments&view=settings', false), Text::_('A_SETTINGS_SAVED'));

		return true;
	}

	public function cancel()
	{
		$this->setRedirect(Route::_('index.php?option=com_jcomments', false));
	}

	public function reset()
	{
		Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));

		$app      = Factory::getApplication();
		$language = $app->input->post->get('language', '', 'string');

		$model = $this->getModel('Settings', 'JCommentsModel', array('ignore_request' => true));
		$model->setState($model->getName() . '.language', $language);

		if ($model->reset() === false)
		{
			$this->setRedirect(
				Route::_('index.php?option=com_jcomments&view=settings', false),
				Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()),
				'error'
			);

			return false;
		}

		$cache = Factory::getCache('com_jcomments');
		$cache->clean();

		$this->setRedirect(Route::_('index.php?option=com_jcomments&view=settings', false), Text::_('A_SETTINGS_RESTORED'));

		return true;
	}
}
