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

namespace Joomla\Component\Jcomments\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Settings controller.
 *
 * @since  1.6
 */
class SettingsController extends BaseController
{
	/**
	 * Method to save component configuration into json file.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function saveConfig()
	{
		// Check if the user is authorized to do this.
		if (!$this->app->getIdentity()->authorise('core.admin', 'com_jcomments'))
		{
			echo Text::_('JLIB_RULES_NOT_ALLOWED');

			return;
		}

		$params   = ComponentHelper::getParams('com_jcomments');
		$document = $this->app->getDocument();
		$document->setMimeEncoding('application/octet-stream');

		$this->app->setHeader('Pragma', 'no-cache', true);
		$this->app->setHeader('Expires', '-1');
		$this->app->setHeader('Cache-Control', 'public, no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true);
		$this->app->setHeader('Content-Transfer-Encoding', 'Binary');
		$this->app->setHeader(
			'Content-disposition',
			'attachment; filename="com_jcomments-settings-' . HTMLHelper::_('date', time(), 'Y-m-d_H-i-s') . '.json"'
		);
		$this->app->sendHeaders();

		$_access = new Access;
		$_access->preload('com_jcomments');
		$access = $_access->getAssetRules('com_jcomments');

		$accessArray['access'] = json_decode((string) $access, true);
		$paramsArray['params'] = $params->toArray();

		echo json_encode(array_merge($accessArray, $paramsArray));
	}

	/**
	 * Method to restore component configuration from json file.
	 *
	 * @return  boolean
	 *
	 * @since  3.0
	 */
	public function restoreConfig()
	{
		$this->checkToken();

		// Check if the user is authorized to do this.
		if (!$this->app->getIdentity()->authorise('core.admin', 'com_jcomments'))
		{
			$this->app->redirect('index.php', Text::_('JERROR_ALERTNOAUTHOR'));

			return false;
		}

		/** @var \Joomla\Component\Jcomments\Administrator\Model\SettingsModel $model */
		$model        = $this->getModel();
		$file         = $this->input->files->get('form_upload_config', '', 'array');
		$file['name'] = File::makeSafe($file['name']);
		$url          = 'index.php?option=com_jcomments&view=settings';

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
				// Validate settings
				$form = $model->getForm($data, false);

				if (!$form)
				{
					$this->setRedirect($url, Text::_('JGLOBAL_VALIDATION_FORM_FAILED'), 'error');

					return false;
				}

				$validData = $model->validate($form, $data);

				if ($validData === false)
				{
					$this->setRedirect($url, $model->getError(), 'error');

					return false;
				}

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
	public function detectMime(string $path)
	{
		if (!empty($path) && is_file($path))
		{
			// We should suppress all errors here to avoid broken data due to bug in PHP >7 with mime database.
			if (function_exists('finfo_open'))
			{
				$finfo = new \finfo(FILEINFO_MIME_TYPE);
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
			throw new \RuntimeException('File not found at ' . $path);
		}

		return $mime;
	}
}
