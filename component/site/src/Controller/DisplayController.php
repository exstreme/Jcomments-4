<?php
/**
 * Frontend display controller.
 *
 * @package  JComments
 */

namespace Joomla\Component\Jcomments\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;

/**
 * Default frontend controller.
 *
 * @since  5.1.0
 */
final class DisplayController extends BaseController
{
	/**
	 * Constructor.
	 *
	 * @param   array                     $config   Controller config.
	 * @param   MVCFactoryInterface|null  $factory  MVC factory.
	 * @param   mixed                     $app      Application.
	 * @param   Input|null                $input    Input.
	 *
	 * @since   5.0.2
	 */
	public function __construct($config = [], ?MVCFactoryInterface $factory = null, $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->registerTask('notifications-cron', 'notificationsCron');
		$this->registerTask('rss_full', 'rssFull');
		$this->registerTask('rss_user', 'rssUser');
		$this->registerTask('show_all', 'showAll');
		$this->registerTask('viewAllMyComments', 'showAll');
		$this->registerTask('viewAllMyVotes', 'showAll');
	}

	/**
	 * Execute a task and process legacy AJAX calls after normal task routing.
	 *
	 * @param   string  $task  Task name.
	 *
	 * @return  mixed
	 *
	 * @since   5.0.2
	 */
	public function execute($task)
	{
		$result = parent::execute($task);

		$this->processAjaxRequest();

		return $result;
	}

	/**
	 * Display the comments page or menu-bound comments object.
	 *
	 * @param   boolean  $cachable   Cache flag.
	 * @param   array    $urlparams  Safe URL params.
	 *
	 * @return  static
	 *
	 * @since   5.0.2
	 */
	public function display($cachable = false, $urlparams = [])
	{
		$this->loadCore();

		$this->app->getDocument()->getWebAssetManager()->useScript('jquery');

		if ($this->input->get('jtxf', '') !== '')
		{
			return $this;
		}

		$jcOption = $this->input->get('option', '');

		if ($jcOption === 'com_jcomments' && !$this->app->isClient('administrator'))
		{
			$itemid = $this->input->getInt('Itemid');
			$tmpl   = $this->input->get('tmpl');

			if ($itemid !== 0 && $tmpl !== 'component')
			{
				$params      = $this->app->getParams();
				$objectGroup = \JCommentsSecurity::clearObjectGroup($params->get('object_group'));
				$objectID    = (int) $params->get('object_id', 0);

				if ($objectID !== 0 && $objectGroup !== '')
				{
					$this->prepareMenuDocument($params);

					echo \JComments::show($objectID, $objectGroup);

					return $this;
				}
			}

			$this->app->redirect(Route::_(rtrim(Uri::base(), '/')));

			return $this;
		}

		return parent::display($cachable, $urlparams);
	}

	/**
	 * Render captcha image or delegate to captcha plugins.
	 *
	 * @return  void
	 *
	 * @since   5.0.2
	 */
	public function captcha(): void
	{
		$this->loadCore();

		$config        = ComponentHelper::getParams('com_jcomments');
		$captchaEngine = $config->get('captcha_engine', 'kcaptcha');

		if ($captchaEngine === 'kcaptcha' || (int) $config->get('enable_plugins') === 0)
		{
			require_once JPATH_ROOT . '/components/com_jcomments/jcomments.captcha.php';

			\JCommentsCaptcha::image();

			return;
		}

		if ((int) $config->get('enable_plugins') === 1)
		{
			\JCommentsEvent::trigger('onJCommentsCaptchaImage');
		}
	}

	/**
	 * Execute quick moderation command.
	 *
	 * @return  void
	 *
	 * @since   5.0.2
	 */
	public function cmd(): void
	{
		$this->loadCore();

		\JComments::executeCmd();
	}

	/**
	 * Send queued notifications by cron URL.
	 *
	 * @return  void
	 *
	 * @since   5.0.2
	 */
	public function notificationsCron(): void
	{
		$this->loadCore();

		$limit  = $this->input->getInt('limit', 10);
		$secret = trim($this->input->get('secret', ''));

		if ($secret === $this->app->get('secret'))
		{
			\JCommentsNotification::send($limit);
		}
	}

	/**
	 * Refresh stored object information.
	 *
	 * @return  void
	 *
	 * @since   5.0.2
	 */
	public function refreshObjectsAjax(): void
	{
		$this->loadCore();

		require_once JPATH_ROOT . '/components/com_jcomments/jcomments.ajax.php';

		\JCommentsAJAX::refreshObjectsAjax();
	}

	/**
	 * Display object RSS feed.
	 *
	 * @return  static
	 *
	 * @since   5.0.2
	 */
	public function rss()
	{
		return $this->displayFeed();
	}

	/**
	 * Display site RSS feed.
	 *
	 * @return  static
	 *
	 * @since   5.0.2
	 */
	public function rssFull()
	{
		$this->input->set('task', 'rss_full');

		return $this->displayFeed();
	}

	/**
	 * Display user RSS feed.
	 *
	 * @return  static
	 *
	 * @since   5.0.2
	 */
	public function rssUser()
	{
		$this->input->set('task', 'rss_user');

		return $this->displayFeed();
	}

	/**
	 * Display user comments list.
	 *
	 * @return  static
	 *
	 * @since   5.0.2
	 */
	public function showAll()
	{
		$this->loadCore();
		$this->input->set('view', 'comments');

		return parent::display(true, $this->getSafeUrlParams());
	}

	/**
	 * Load frontend compatibility classes.
	 *
	 * @return  void
	 *
	 * @since   5.0.2
	 */
	private function loadCore(): void
	{
		require_once JPATH_ROOT . '/components/com_jcomments/jcomments.php';
	}

	/**
	 * Display a feed view.
	 *
	 * @return  static
	 *
	 * @since   5.0.2
	 */
	private function displayFeed()
	{
		$this->loadCore();
		$this->input->set('view', 'comments');

		return parent::display(false, $this->getSafeUrlParams());
	}

	/**
	 * Register and process legacy JoomlaTune AJAX functions.
	 *
	 * @return  void
	 *
	 * @since   5.0.2
	 */
	private function processAjaxRequest(): void
	{
		if (!isset($_REQUEST['jtxf']))
		{
			return;
		}

		$this->loadCore();

		require_once JPATH_ROOT . '/components/com_jcomments/jcomments.ajax.php';

		$jtx = new \JoomlaTuneAjax;
		$jtx->setCharEncoding('utf-8');
		$jtx->registerFunction(array('JCommentsAddComment', 'JCommentsAJAX', 'addComment'));
		$jtx->registerFunction(array('JCommentsDeleteComment', 'JCommentsAJAX', 'deleteComment'));
		$jtx->registerFunction(array('JCommentsEditComment', 'JCommentsAJAX', 'editComment'));
		$jtx->registerFunction(array('JCommentsCancelComment', 'JCommentsAJAX', 'cancelComment'));
		$jtx->registerFunction(array('JCommentsSaveComment', 'JCommentsAJAX', 'saveComment'));
		$jtx->registerFunction(array('JCommentsPublishComment', 'JCommentsAJAX', 'publishComment'));
		$jtx->registerFunction(array('JCommentsQuoteComment', 'JCommentsAJAX', 'quoteComment'));
		$jtx->registerFunction(array('JCommentsShowPage', 'JCommentsAJAX', 'showPage'));
		$jtx->registerFunction(array('JCommentsShowComment', 'JCommentsAJAX', 'showComment'));
		$jtx->registerFunction(array('JCommentsJump2email', 'JCommentsAJAX', 'jump2email'));
		$jtx->registerFunction(array('JCommentsShowForm', 'JCommentsAJAX', 'showForm'));
		$jtx->registerFunction(array('JCommentsVoteComment', 'JCommentsAJAX', 'voteComment'));
		$jtx->registerFunction(array('JCommentsShowReportForm', 'JCommentsAJAX', 'showReportForm'));
		$jtx->registerFunction(array('JCommentsReportComment', 'JCommentsAJAX', 'reportComment'));
		$jtx->registerFunction(array('JCommentsBanIP', 'JCommentsAJAX', 'BanIP'));
		$jtx->processRequests();
	}

	/**
	 * Prepare document metadata for menu-bound comments output.
	 *
	 * @param   object  $params  Menu params.
	 *
	 * @return  void
	 *
	 * @since   5.0.2
	 */
	private function prepareMenuDocument(object $params): void
	{
		$keywords    = $params->get('menu-meta_keywords');
		$description = $params->get('menu-meta_description');
		$title       = $params->get('page_title');
		$document    = $this->app->getDocument();

		if (empty($title))
		{
			$title = $this->app->get('sitename');
		}
		elseif ($this->app->get('sitename_pagetitles', 0) == 1)
		{
			$title = Text::sprintf('JPAGETITLE', $this->app->get('sitename'), $title);
		}
		elseif ($this->app->get('sitename_pagetitles', 0) == 2)
		{
			$title = Text::sprintf('JPAGETITLE', $title, $this->app->get('sitename'));
		}

		$document->setTitle($title);

		if ($keywords)
		{
			$document->setMetaData('keywords', $keywords);
		}

		if ($description)
		{
			$document->setDescription($description);
		}
	}

	/**
	 * Safe URL params used by comments views.
	 *
	 * @return  array<string, string>
	 *
	 * @since   5.0.2
	 */
	private function getSafeUrlParams(): array
	{
		return array(
			'id'               => 'INT',
			'limit'            => 'UINT',
			'limitstart'       => 'UINT',
			'return'           => 'BASE64',
			'filter'           => 'STRING',
			'filter_order'     => 'CMD',
			'filter_order_Dir' => 'CMD',
			'filter-search'    => 'STRING',
			'print'            => 'BOOLEAN',
			'lang'             => 'CMD',
			'Itemid'           => 'INT'
		);
	}
}
