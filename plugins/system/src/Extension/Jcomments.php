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

namespace Joomla\Plugin\System\Jcomments\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Jcomments\Site\Helper\ComponentHelper as JcommentsComponentHelper;
use Joomla\Event\DispatcherInterface;
use Joomla\Http\Exception\InvalidResponseCodeException;
use Joomla\Registry\Registry;

/**
 * System plugin for Jcomments
 *
 * @since 1.5
 */
final class Jcomments extends CMSPlugin
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

		// Use this plugin only in site application.
		if (!$this->app->isClient('site'))
		{
			return;
		}

		if (!isset($this->params))
		{
			$this->params = new Registry('');
		}
	}

	/**
	 * After Render Event.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @noinspection PhpUnused
	 */
	public function onAfterRender()
	{
		// Use this plugin only in site application.
		if (!$this->app->isClient('site'))
		{
			return;
		}

		$buffer = $this->app->getBody();

		// Cleanup RSS from {jcomments}
		if ($this->params->get('clear_rss', 0) == 1
			&& $this->app->input->get('option') == 'com_content'
			&& $this->app->getDocument()->getType() === 'feed')
		{
			$buffer = preg_replace('#{jcomments\s+(off|on|lock)}#i', '', $buffer);
			$buffer = \Joomla\String\StringHelper::str_ireplace('{jcomments}', '', $buffer);
			$this->app->setBody($buffer);
		}
	}

	/**
	 * This event is triggered immediately before the framework has rendered the application.
	 *
	 * @return  void
	 *
	 * @since   4.1
	 * @noinspection PhpUnused
	 */
	public function onBeforeRender()
	{
		$document = $this->app->getDocument();

		if ($document->getType() != 'html')
		{
			return;
		}

		$option = $this->app->input->get('option');

		if ($this->app->isClient('site') && ($option == 'com_content' || $option == 'com_multicategories'))
		{
			JcommentsComponentHelper::loadComponentAssets();
		}
	}

	/**
	 * @param   integer  $objectId     Object ID
	 * @param   string   $objectGroup  Object group. E.g. com_content
	 * @param   string   $objectTitle  Object title. Used in RSS and Atom feed.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	public function onJcommentsShow(int $objectId, string $objectGroup, string $objectTitle)
	{
		// Only one copy of JComments per page is allowed
		if (defined('JCOMMENTS_SHOW'))
		{
			return;
		}

		JcommentsComponentHelper::loadComponentAssets();

		$basePath = JPATH_ROOT . '/components/com_jcomments';
		$view = JcommentsComponentHelper::getView(
			'Comments',
			'Site',
			'Html',
			// View config
			array('base_path' => $basePath, 'template_path' => $basePath . '/tmpl/comments/'),
			true,
			// Model config. NOTE! Do not set up `ignore_request` in this because component params will be empty in view when calling getState()
			array(
				'name'      => 'Comments',
				'prefix'    => 'Site',
				'base_path' => $basePath,
				'options'   => array(
					'object_id'    => $objectId,
					'object_group' => $objectGroup,
					'object_title' => $objectTitle
				)
			)
		);

		ob_start();

		$view->display();
		$output = ob_get_contents();

		ob_end_clean();

		define('JCOMMENTS_SHOW', 1);

		echo $output;
	}

	/**
	 * Get total comments for object
	 *
	 * @param   integer      $objectId     Object ID
	 * @param   string       $objectGroup  Object group. E.g. com_content
	 * @param   string|null  $lang         Language tag
	 *
	 * @return  void
	 *
	 * @since   1.5
	 */
	public function onJcommentsCount(int $objectId, string $objectGroup, ?string $lang = null)
	{
		/** @var Joomla\Component\Jcomments\Site\Model\CommentsModel $model */
		$model = $this->app->bootComponent('com_jcomments')->getMVCFactory()
			->createModel('Comments', 'Site', array('ignore_request' => true));

		$model->setState('object_id', $objectId);
		$model->setState('object_group', $objectGroup);
		$model->setState('list.options.lang', $lang);

		echo $model->getTotal();
	}

	/**
	 * Do spam checks before comment add. Available only on frontend.
	 *
	 * @param   string  $ip  IP from comment
	 *
	 * @return  boolean  False if IP in spam database, true otherwise.
	 *
	 * @see     https://www.stopforumspam.com/usage
	 * @since   4.0.23
	 * @noinspection PhpUnused
	 */
	public function onJcommentsCommentBeforeAdd(string $ip)
	{
		$params = ComponentHelper::getParams('com_jcomments');

		if ($params->get('stopforumspam', 0) == 1)
		{
			try
			{
				$httpResponse = HttpFactory::getHttp()->get(
					'https://api.stopforumspam.org/api?ip=' . $ip . '&json',
					array(),
					$params->get('antispam_request_timeout', 3)
				);
				$responseBody = (string) $httpResponse->getBody();
				$responseBody = json_decode($responseBody);

				if ($responseBody->success)
				{
					// 1 - spam
					if ($responseBody->ip->appears == 1)
					{
						Factory::getApplication()->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 'error');
						Log::add('Spammer(by StopForumSpam) from IP ' . $ip . ' is trying to send comment.', Log::ERROR, 'com_jcomments');

						return false;
					}
				}
			}
			catch (InvalidResponseCodeException $e)
			{
				Log::add('Invalid or undefined HTTP response code while fetching StopForumSpam API.', Log::ERROR, 'com_jcomments');
			}
		}

		return true;
	}
}
