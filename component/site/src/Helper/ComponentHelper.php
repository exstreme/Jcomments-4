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

namespace Joomla\Component\Jcomments\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Filesystem\File;
use Joomla\Registry\Registry;

/**
 * Component helper class
 *
 * @alias  JcommentsComponentHelper
 *
 * @since  4.1
 */
class ComponentHelper extends \Joomla\CMS\Component\ComponentHelper
{
	/**
	 * Include javascript and css.
	 *
	 * @return  void
	 *
	 * @since   4.1
	 */
	public static function loadComponentAssets()
	{
		$document = Factory::getApplication()->getDocument();

		if ($document->getType() != 'html')
		{
			return;
		}

		$params = self::getParams('com_jcomments');
		$pluginParams = new Registry(PluginHelper::getPlugin('system', 'jcomments')->params);

		/** @var \Joomla\CMS\WebAsset\WebAssetManager $wa */
		$wa = $document->getWebAssetManager();

		/** @var \Joomla\CMS\WebAsset\WebAssetRegistry $wr */
		$wr = $wa->getRegistry();
		$wr->addRegistryFile('media/com_jcomments/joomla.asset.json');

		if ($pluginParams->get('disable_template_css') == 0)
		{
			$style = File::makeSafe($params->get('custom_css'));

			if ($style != 'frontend-style')
			{
				$cssPath = 'media/com_jcomments/css/' . $style . '.css';

				if (is_file($cssPath))
				{
					$wa->registerAndUseStyle('jcomments.custom_style', $cssPath);
				}
				else
				{
					$wa->useStyle('jcomments.style');
				}
			}
			else
			{
				$wa->useStyle('jcomments.style');
			}
		}

		$wa->useScript('jcomments.core')->useScript('jcomments.frontend');
	}

	/**
	 * Method to load and return a view object.
	 *
	 * @param   string   $name         The name of the view.
	 * @param   string   $prefix       Optional view prefix.
	 * @param   string   $type         Optional type of view.
	 * @param   array    $config       Optional configuration array for the view.
	 * @param   boolean  $setModel     Load and set model for view.
	 * @param   array    $modelConfig  Model condifguration. Will be available in model __construct()
	 *
	 * @return  \Joomla\CMS\MVC\View\ViewInterface  The view object
	 *
	 * @throws  \Exception
	 * @since   4.1
	 */
	public static function getView(string $name, string $prefix = '', string $type = 'Html', array $config = [],
		bool $setModel = false, array $modelConfig = []
	)
	{
		$app = Factory::getApplication();

		if (!isset($config['layout']))
		{
			$config['layout'] = $app->input->get('layout', 'default', 'string');
		}

		/** @var \Joomla\CMS\MVC\Factory\MVCFactory $factory */
		$factory = $app->bootComponent('com_jcomments')->getMVCFactory();

		/** @var \Joomla\Component\Jcomments\Site\View\Comments\HtmlView $view */
		$view = $factory->createView($name, $prefix, $type, $config);

		if ($setModel)
		{
			if (!isset($modelConfig['name']))
			{
				$modelConfig['name'] = $name;
			}

			if (!isset($modelConfig['prefix']))
			{
				$modelConfig['prefix'] = '';
			}

			if (!isset($modelConfig['options']))
			{
				$modelConfig['options'] = array();
			}

			Form::addFormPath($modelConfig['base_path'] . '/forms');

			$model = $factory->createModel($modelConfig['name'], $modelConfig['prefix'], $modelConfig['options']);
			$view->setModel($model, true);
		}

		return $view;
	}

	/**
	 * Make new seed to use with srand()
	 *
	 * @return  integer
	 *
	 * @since   4.0
	 */
	public static function makeSeed(): int
	{
		list($usec, $sec) = explode(' ', microtime());

		return (int) $sec + $usec * 10000000;
	}
}
