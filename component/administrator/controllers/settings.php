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
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;

/**
 * Settings controller.
 *
 * @since  1.5
 */
class JCommentsControllerSettings extends BaseController
{
	/**
	 * Method to save a record.
	 *
	 * @return  void|boolean  Void on success. Boolean false on fail.
	 *
	 * @since   3.0
	 * @throws  Exception
	 */
	public function save()
	{
		$this->checkToken();

		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		// Check if the user is authorized to do this.
		if (!$user->authorise('core.admin', 'com_jcomments') && !$user->authorise('core.manage', 'com_jcomments'))
		{
			$this->setRedirect(Route::_('index.php', false), Text::_('JERROR_ALERTNOAUTHOR'), 'error');
		}

		$returnUri = $this->input->post->get('return', null, 'base64');
		$redirect  = !empty($returnUri) ? '&return=' . urlencode($returnUri) : '';
		$data      = $this->input->post->get('jform', array(), 'array');
		$context   = 'com_jcomments.edit.settings';

		/** @var JCommentsModelSettings $model */
		$model = $this->getModel('Settings', 'JCommentsModel', array('ignore_request' => true));
		$model->setState('component.option', 'com_jcomments');
		$form  = $model->getForm();

		// Validate the posted data.
		$return = $model->validate($form, $data);

		// Check for validation errors.
		if ($return === false)
		{
			// Save the data in the session.
			$this->app->setUserState($context . '.data', $data);

			// Redirect back to the edit screen.
			$this->setRedirect(
				Route::_('index.php?option=com_jcomments&view=settings' . $redirect, false),
				$model->getError(),
				'error'
			);

			return false;
		}

		if ($data['captcha_engine'] == 'kcaptcha' && (!extension_loaded('gd') || !function_exists('imagecreatefrompng')))
		{
			$this->app->enqueueMessage(Text::_('A_WARNINGS_PHP_GD'), 'warning');
		}

		$result = $model->save($data);

		if (!$result)
		{
			// Save failed, go back to the screen and display a notice.
			$this->setRedirect(Route::_('index.php?option=com_jcomments&view=settings' . $redirect, false));

			return false;
		}

		// Clear session data.
		$this->app->setUserState($context . '.data', null);

		$this->app->enqueueMessage(Text::_('A_SETTINGS_SAVED'), 'message');
		$this->setRedirect(Route::_('index.php?option=com_jcomments&view=settings', false));

		return true;
	}

	/**
	 * Method to cancel an edit.
	 *
	 * @param   string  $key  The name of the primary key of the URL variable.
	 *
	 * @return  boolean  True if access level checks pass, false otherwise.
	 *
	 * @since   1.6
	 */
	public function cancel($key = null)
	{
		$this->setRedirect(Route::_('index.php?option=com_jcomments', false));

		return true;
	}

	/**
	 * Method to restore component configuration from json file.
	 *
	 * @return  boolean
	 *
	 * @since  3.0
	 */
	public function restore()
	{
		$this->checkToken();

		$app = Factory::getApplication();

		// Check if the user is authorized to do this.
		if (!$app->getIdentity()->authorise('core.admin', 'com_jcomments'))
		{
			$app->redirect('index.php', Text::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		/** @var JCommentsModelSettings $model */
		$model = $this->getModel('settings');
		$file = $this->input->files->get('form_upload_config', '', 'array');
		$file['name'] = File::makeSafe($file['name']);
		$url = 'index.php?option=com_jcomments&view=settings';

		if ($this->detectMime($file['tmp_name']) != 'application/json' || File::getExt($file['name']) != 'json')
		{
			$this->setRedirect($url, Text::_('A_SETTINGS_RESTORE_INVALID_REQUEST'), 'error');

			return false;
		}

		if (isset($file['name']))
		{
			$fc     = file_get_contents($file['tmp_name']);
			$data   = json_decode($fc);
			$errors = json_last_error();

			if ($errors === JSON_ERROR_NONE)
			{
				if ($model->restoreConfig($data))
				{
					$this->setRedirect($url, Text::_('A_SETTINGS_BUTTON_RESTORECONFIG_SUCCESS'));
				}
				else
				{
					$this->setRedirect($url, Text::_('A_SETTINGS_BUTTON_RESTORECONFIG_ERROR'), 'error');
				}

				return false;
			}
			else
			{
				$this->setRedirect($url, Text::_('A_SETTINGS_RESTORE_INVALID_FILE'), 'error');
			}
		}
		else
		{
			$this->setRedirect($url, Text::_('A_SETTINGS_RESTORE_INVALID_REQUEST'), 'error');
		}

		return true;
	}

	/**
	 * Get MIME-type of the file.
	 *
	 * @param   string  $path  Path to a file.
	 *
	 * @return  string
	 *
	 * @since   3.1
	 */
	public function detectMime($path)
	{
		if (!empty($path) && is_file($path))
		{
			// We should suppress all errors here to avoid broken data due to bug in PHP >7 with mime database.
			if (function_exists('finfo_open'))
			{
				$finfo = new finfo(FILEINFO_MIME_TYPE);
				$mime = @$finfo->file($path);
			}
			elseif (function_exists('mime_content_type'))
			{
				$mime = @mime_content_type($path);
			}
			else
			{
				$mime = 'text/plain';
			}
		}
		else
		{
			throw new RuntimeException('File not found at ' . $path);
		}

		return $mime;
	}
}
