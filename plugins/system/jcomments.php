<?php
/**
 * JComments system plugin - System plugin for attaching JComments.
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

include_once JPATH_ROOT . '/components/com_jcomments/helpers/system.php';

/**
 * System plugin for attaching JComments CSS & JavaScript to HEAD tag
 *
 * @since 1.5
 */
class PlgSystemJComments extends CMSPlugin
{
	/**
	 * Application object.
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 * @since  3.8.0
	 */
	protected $app;

	/**
	 * Constructor
	 *
	 * @param   DispatcherInterface  $subject  The object to observe
	 * @param   array                $config   An optional associative array of configuration settings.
	 *                                         Recognized key values include 'name', 'group', 'params', 'language'
	 *                                         (this list is not meant to be comprehensive).
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		if (!isset($this->params))
		{
			$this->params = new Registry('');
		}

		// Small hack to allow CAPTCHA display even if any notice or warning occurred
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
				// Turn off all error reporting for AJAX call
				@error_reporting(E_NONE);
			}
		}
	}

	/**
	 * After Render Event.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function onAfterRender()
	{
		// Use this plugin only in site application.
		if (!$this->app->isClient('site'))
		{
			return;
		}

		$buffer = $this->app->getBody();

		if ($this->params->get('clear_rss', 0) == 1
			&& $this->app->input->get('option') == 'com_content'
			&& $this->app->getDocument()->getType() === 'feed')
		{
			$buffer = preg_replace('#{jcomments\s+(off|on|lock)}#is', '', $buffer);
			$this->app->setBody($buffer);
		}

		if ((defined('JCOMMENTS_CSS') || defined('JCOMMENTS_JS')) && !defined('JCOMMENTS_SHOW'))
		{
			if ($this->app->getName() == 'site')
			{
				$regexpJS  = '#(\<script(\stype=\"text\/javascript\")? src="[^\"]*\/com_jcomments\/[^\>]*\>\<\/script\>[\s\r\n]*?)#ismU';
				$regexpCSS = '#(\<link rel="stylesheet" href="[^\"]*\/com_jcomments\/[^>]*>[\s\r\n]*?)#ismU';

				$jcommentsTestJS  = '#(JCommentsEditor|new JComments)#ismU';
				$jcommentsTestCSS = '#(comment-link|jcomments-links)#ismU';

				$jsFound  = preg_match($jcommentsTestJS, $buffer);
				$cssFound = preg_match($jcommentsTestCSS, $buffer);

				if (!$jsFound)
				{
					// Remove JavaScript if JComments isn't loaded
					$buffer = preg_replace($regexpJS, '', $buffer);
				}

				if (!$cssFound && !$jsFound)
				{
					// Remove CSS if JComments isn't loaded
					$buffer = preg_replace($regexpCSS, '', $buffer);
				}

				if ($buffer != '')
				{
					$this->app->setBody($buffer);
				}
			}
		}
	}

	public function onAfterRoute()
	{
		// Do not change to $app->getDocument() because it will cause an error.
		$document = Factory::getDocument();

		if ($document->getType() != 'html')
		{
			return;
		}

		$option = $this->app->input->get('option');

		if ($this->app->isClient('site') && ($option == 'com_content' || $option == 'com_multicategories'))
		{
			// Try to find CSS in ROOT/templates folder
			$template = ComponentHelper::getParams('com_jcomments')->get('template');
			$cssName = $this->app->getLanguage()->isRtl() ? 'style_rtl.css' : 'style.css';
			$cssUrl  = Uri::root(true) . '/templates/' . $this->app->getTemplate() . '/html/com_jcomments/' . $template . '/' . $cssName;

			// Try to find CSS in ROOT/media/component folder
			if (!is_file(JPATH_SITE . '/templates/' . $this->app->getTemplate() . '/html/com_jcomments/' . $template . '/' . $cssName))
			{
				$cssUrl  = Uri::root(true) . '/components/com_jcomments/tpl/' . $template . '/' . $cssName;
			}

			$document->addStyleSheet($cssUrl);

			if (!defined('JCOMMENTS_CSS'))
			{
				define('JCOMMENTS_CSS', 1);
			}

			// Include JComments JavaScript library
			$document->addScript(Uri::root(true) . '/media/com_jcomments/js/jcomments-v2.3.js');

			if (!defined('JOOMLATUNE_AJAX_JS'))
			{
				$document->addScript(Uri::root(true) . '/components/com_jcomments/libraries/joomlatune/ajax.js?v=4');
				define('JOOMLATUNE_AJAX_JS', 1);
			}

			if (!defined('JCOMMENTS_JS'))
			{
				define('JCOMMENTS_JS', 1);
			}
		}
	}

	public function onJCommentsShow($objectId, $objectGroup, $objectTitle)
	{
		$coreFile = JPATH_ROOT . '/components/com_jcomments/jcomments.php';

		if (is_file($coreFile))
		{
			include_once $coreFile;
			echo JComments::show($objectId, $objectGroup, $objectTitle);
		}
	}

	public function onJCommentsCount($objectId, $objectGroup)
	{
		$coreFile = JPATH_ROOT . '/components/com_jcomments/jcomments.php';

		if (is_file($coreFile))
		{
			include_once $coreFile;
			echo JComments::getCommentsCount($objectId, $objectGroup);
		}
	}
}
