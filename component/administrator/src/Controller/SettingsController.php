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
use Joomla\CMS\Response\JsonResponse;

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
	public function backup()
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
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function restore()
	{
		if ($this->checkToken('post', false) === false)
		{
			echo new JsonResponse(null, Text::_('JINVALID_TOKEN_NOTICE'), true, true);

			$this->app->close();
		}

		// Check if the user is authorized to do this.
		if (!$this->app->getIdentity()->authorise('core.admin', 'com_jcomments'))
		{
			echo new JsonResponse(null, Text::_('JERROR_ALERTNOAUTHOR'), true, true);

			$this->app->close();
		}

		/** @var \Joomla\Component\Jcomments\Administrator\Model\SettingsModel $model */
		$model        = $this->getModel();
		$file         = $this->input->files->get('upload_config', '', 'array');
		$file['name'] = File::makeSafe($file['name']);

		if ($this->detectMime($file['tmp_name']) != 'application/json' || File::getExt($file['name']) != 'json')
		{
			echo new JsonResponse(null, Text::_('A_SETTINGS_RESTORE_INVALID_REQUEST'), true, true);

			$this->app->close();
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
					echo new JsonResponse(null, Text::_('JGLOBAL_VALIDATION_FORM_FAILED'), true, true);

					$this->app->close();
				}

				$validData = $model->validate($form, $data);

				if ($validData === false)
				{
					echo new JsonResponse(null, $model->getError(), true, true);

					$this->app->close();
				}

				if ($model->restoreConfig($data))
				{
					echo new JsonResponse(null, Text::_('A_SETTINGS_BUTTON_RESTORECONFIG_SUCCESS'));
				}
				else
				{
					echo new JsonResponse(null, Text::_('A_SETTINGS_BUTTON_RESTORECONFIG_ERROR'), true, true);
				}

				$this->app->close();
			}
			else
			{
				echo new JsonResponse(null, Text::_('A_SETTINGS_RESTORE_INVALID_FILE'), true, true);
			}
		}
		else
		{
			echo new JsonResponse(null, Text::_('A_SETTINGS_RESTORE_INVALID_REQUEST'), true, true);
		}

		$this->app->close();
	}

	/**
	 * Get MIME-type of the file.
	 *
	 * @param   string  $path  Path to a file.
	 *
	 * @return  string
	 *
	 * @since   4.0
	 */
	public function detectMime(string $path): string
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
