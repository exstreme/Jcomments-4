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
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Utilities\ArrayHelper;

include_once(JPATH_ROOT . '/components/com_jcomments/helpers/system.php');

/**
 * System plugin for attaching JComments CSS & JavaScript to HEAD tag
 *
 * @since 1.5
 */
class plgSystemJComments extends CMSPlugin
{
	/**
	 * Constructor
	 *
	 * @param   object  $subject  The object to observe
	 * @param   array   $config   An array that holds the plugin configuration
	 *
	 * @throws Exception
	 * @since 1.5
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		if (!isset($this->params))
		{
			$this->params = new JRegistry('');
		}

		// small hack to allow CAPTCHA display even if any notice or warning occurred
		$app    = Factory::getApplication();
		$option = $app->input->get('option');
		$task   = $app->input->get('task');

		if ($option == 'com_jcomments' && $task == 'captcha')
		{
			@ob_start();
		}

		if (isset($_REQUEST['jtxf']))
		{
			if ($this->params->get('disable_error_reporting', 0) == 1)
			{
				// turn off all error reporting for AJAX call
				@error_reporting(E_NONE);
			}
		}
	}

	public function onAfterRender()
	{
		$app    = Factory::getApplication();
		$buffer = $app->getBody();

		if ($this->params->get('clear_rss', 0) == 1)
		{
			$option = $app->input->get('option');

			if ($option == 'com_content')
			{
				$document = Factory::getDocument();

				if ($document->getType() == 'feed')
				{
					$buffer = preg_replace('#{jcomments\s+(off|on|lock)}#is', '', $buffer);
					$app->setBody($buffer);
				}
			}
		}

		if ((defined('JCOMMENTS_CSS') || defined('JCOMMENTS_JS')) && !defined('JCOMMENTS_SHOW'))
		{
			if ($app->getName() == 'site')
			{
				$regexpJS  = '#(\<script(\stype=\"text\/javascript\")? src="[^\"]*\/com_jcomments\/[^\>]*\>\<\/script\>[\s\r\n]*?)#ismU';
				$regexpCSS = '#(\<link rel="stylesheet" href="[^\"]*\/com_jcomments\/[^>]*>[\s\r\n]*?)#ismU';

				$jcommentsTestJS  = '#(JCommentsEditor|new JComments)#ismU';
				$jcommentsTestCSS = '#(comment-link|jcomments-links)#ismU';

				$jsFound  = preg_match($jcommentsTestJS, $buffer);
				$cssFound = preg_match($jcommentsTestCSS, $buffer);

				if (!$jsFound)
				{
					// remove JavaScript if JComments isn't loaded
					$buffer = preg_replace($regexpJS, '', $buffer);
				}

				if (!$cssFound && !$jsFound)
				{
					// remove CSS if JComments isn't loaded
					$buffer = preg_replace($regexpCSS, '', $buffer);
				}

				if ($buffer != '')
				{
					$app->setBody($buffer);
				}
			}
		}

		return true;
	}

	public function onAfterRoute()
	{
		$legacyFile = JPATH_ROOT . '/components/com_jcomments/jcomments.legacy.php';

		if (!is_file($legacyFile))
		{
			return;
		}

		include_once($legacyFile);

		$app = Factory::getApplication();
		$app->getRouter();
		$document = Factory::getDocument();

		if ($document->getType() == 'html')
		{
			if ($app->isClient('administrator'))
			{
				$document->addStyleSheet(JURI::root(true) . '/administrator/components/com_jcomments/assets/css/icon.css?v=2');
				$app->getLanguage()->load('com_jcomments.sys', JPATH_ROOT . '/administrator', 'en-GB', true);

				$option = $app->findOption();
				$task = $app->input->get('task');

				// TODO Do find a better solution in joomla 4.0
				$type = 'content';

				// remove comments if content item deleted from trash
				if ($option == 'com_trash' && $task == 'delete' && $type == 'content')
				{
					$cid = $app->input->post->get('cid', array(), 'array');
					ArrayHelper::toInteger($cid, array(0));
					include_once(JPATH_ROOT . '/components/com_jcomments/jcomments.php');
					JCommentsModel::deleteComments($cid, 'com_content');
				}
			}
			else
			{
				$option = $app->input->get('option');

				if ($option == 'com_content' || $option == 'com_alphacontent' || $option == 'com_multicategories')
				{
					include_once(JPATH_ROOT . '/components/com_jcomments/jcomments.class.php');
					include_once(JPATH_ROOT . '/components/com_jcomments/helpers/system.php');

					// include JComments CSS
					if ($this->params->get('disable_template_css', 0) == 0)
					{
						$document->addStyleSheet(JCommentsSystem::getCSS());
						$language = $app->getLanguage();

						if ($language->isRTL())
						{
							$rtlCSS = JCommentsSystem::getCSS(true);

							if ($rtlCSS != '')
							{
								$document->addStyleSheet($rtlCSS);
							}
						}
					}

					if (!defined('JCOMMENTS_CSS'))
					{
						define('JCOMMENTS_CSS', 1);
					}

					// include JComments JavaScript library
					$document->addScript(JCommentsSystem::getCoreJS());

					if (!defined('JOOMLATUNE_AJAX_JS'))
					{
						$document->addScript(JCommentsSystem::getAjaxJS());
						define('JOOMLATUNE_AJAX_JS', 1);
					}

					if (!defined('JCOMMENTS_JS'))
					{
						define('JCOMMENTS_JS', 1);
					}
				}
			}
		}
	}


	public function onJCommentsShow($object_id, $object_group, $object_title)
	{
		$coreFile = JPATH_ROOT . '/components/com_jcomments/jcomments.php';

		if (is_file($coreFile))
		{
			include_once($coreFile);
			echo JComments::show($object_id, $object_group, $object_title);
		}
	}

	public function onJCommentsCount($object_id, $object_group)
	{
		$coreFile = JPATH_ROOT . '/components/com_jcomments/jcomments.php';

		if (is_file($coreFile))
		{
			include_once($coreFile);
			echo JComments::getCommentsCount($object_id, $object_group);
		}
	}
}
