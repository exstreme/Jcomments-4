<?php
/**
 * JComments content plugin - Plugin for attaching comments list and form to content item.
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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Event\DispatcherInterface;
use Joomla\Registry\Registry;

/**
 * Class for attaching comments list and form to content item.
 *
 * @since  1.0
 */
class PlgContentJcomments extends CMSPlugin
{
	/**
	 * @var    \Joomla\CMS\Application\SiteApplication
	 *
	 * @since  3.9.0
	 */
	protected $app;

	/**
	 * Load the plugin language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  3.9.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Constructor
	 *
	 * @param   DispatcherInterface  $subject  The object to observe
	 * @param   array                $config   An optional associative array of configuration settings.
	 *                                         Recognized key values include 'name', 'group', 'params', 'language'
	 *                                         (this list is not meant to be comprehensive).
	 *
	 * @since   1.5
	 */
	public function __construct(&$subject, $config = array())
	{
		parent::__construct($subject, $config);

		// Load component language file
		$this->app->getLanguage()->load('com_jcomments');
	}

	/**
	 * Prepare 'readmore' data before display layout
	 *
	 * @param   object  $article  The article object
	 * @param   object  $params   The article params
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   1.5
	 */
	private function prepareContent(&$article, &$params): void
	{
		// Check whether plugin has been unpublished
		if (!PluginHelper::isEnabled('content', 'jcomments'))
		{
			JcommentsContentHelper::clear($article);

			return;
		}

		$app    = Factory::getApplication();
		$option = $app->input->get('option');
		$view   = $app->input->get('view');

		if (!isset($article->id) || ($option != 'com_content' && $option != 'com_multicategories'))
		{
			return;
		}

		if (!isset($params) || $params == null)
		{
			$params = new Registry('');
		}
		elseif (isset($params->_raw) && strpos($params->_raw, 'moduleclass_sfx') !== false)
		{
			return;
		}

		JcommentsContentHelper::processForeignTags($article);

		$config           = ComponentHelper::getParams('com_jcomments');
		$categoryEnabled  = JcommentsContentHelper::checkCategory($article->catid);
		$commentsEnabled  = JcommentsContentHelper::isEnabled($article) || $categoryEnabled;
		$commentsDisabled = JcommentsContentHelper::isDisabled($article) || !$commentsEnabled;
		$commentsLocked   = JcommentsContentHelper::isLocked($article);
		$archivesState    = 2;

		if (isset($article->state) && $article->state == $archivesState && $this->params->get('enable_for_archived') == 0)
		{
			$commentsLocked = true;
			JcommentsFactory::getAcl()->setCommentsLocked($commentsLocked);
		}

		$config->set('comments_on', $commentsEnabled);
		$config->set('comments_off', $commentsDisabled);
		$config->set('comments_locked', $commentsLocked);

		// Render 'Readmore' layout
		if ($view != 'article')
		{
			$layoutData = array();
			$layoutData['params'] = &$this->params;

			$slug     = $article->slug ?? $article->id;
			$language = $article->language ?? '*';

			if ($params->get('access-view'))
			{
				$readmoreLink = Route::_(ContentHelperRoute::getArticleRoute($slug, $article->catid, $language));
			}
			else
			{
				$returnURL = Route::_(ContentHelperRoute::getArticleRoute($slug, $article->catid, $language));

				$menu   = $app->getMenu();
				$active = $menu->getActive();
				$itemId = $active->id;
				$link1  = Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
				$link   = new Uri($link1);
				$link->setVar('return', base64_encode($returnURL));
				$readmoreLink = $link;
			}

			$commentsDisabled = false;

			if ($config->get('comments_off'))
			{
				$commentsDisabled = true;
			}
			elseif ($config->get('comments_on'))
			{
				$commentsDisabled = false;
			}

			$layoutData['commentsLinkHidden'] = $commentsDisabled;
			$count                            = 0;
			$layoutData['commentsCount']      = $count;

			// Do not query comments count if comments disabled and link hidden
			if (!$commentsDisabled)
			{
				if ($this->params->get('link_read_comments') != 0)
				{
					$acl = JcommentsFactory::getACL();

					/** @var Joomla\Component\Jcomments\Site\Model\CommentsModel $model */
					$model = $this->app->bootComponent('com_jcomments')->getMVCFactory()->createModel('Comments', 'Site');
					$count = $model->getCommentsCount(
						array(
							'object_id'    => (int) $article->id,
							'object_group' => 'com_content',
							'published'    => $acl->canPublish() || $acl->canPublishForObject($article->id, 'com_content')
								? null : 1, // TODO Set to 1 when object::save() will be implemented in object model.
							'lang' => $language
						)
					);

					$layoutData['commentsCount'] = $count;
				}
			}

			JcommentsContentHelper::clear($article);
			$layoutData['item'] = $article;
			$layoutData['link'] = $readmoreLink;

			// Hide comments link if comments enabled but link disabled in plugin params
			if ((($this->params->get('link_read_comments') == 0)
				|| ($count == 0 && $this->params->get('link_add_comment') == 0))
				&& !$commentsDisabled
			)
			{
				$layoutData['commentsLinkHidden'] = true;
			}

			$layoutData['showReadmore'] = false;

			if ($params->get('show_readmore') && $article->readmore)
			{
				// Disable joomla 'readmore' and show 'readmore' layout from this plugin
				$params->set('show_readmore', false);
				$layoutData['showReadmore'] = true;
			}

			// Links position
			if ($this->params->get('links_position') == 'after')
			{
				$article->introtext = $article->text . $this->getRenderer('links')->render($layoutData);
			}
			else
			{
				$article->introtext = $this->getRenderer('links')->render($layoutData) . $article->text;
			}
		}
		// Display comments
		else
		{
			if ($this->params->get('show_comments_event') == 'onContentBeforeDisplay')
			{
				$isEnabled = ($config->get('comments_on', false)) && ($config->get('comments_off', false) == false);

				if ($isEnabled)
				{
					$basePath = JPATH_ROOT . '/components/com_jcomments';
					$view = $this->getView(
						'Comments',
						'Site',
						'Html',
						// View config
						array('base_path' => $basePath, 'template_path' => $basePath . '/tmpl/comments/'),
						true,
						// Model config
						array('name' => 'Comments', 'prefix' => 'Site', 'base_path' => $basePath)
					);

					ob_start();

					//$view->display(null, array('id' => $article->id, 'object' => 'com_content', 'title' => $article->title));
					$view->display();
					$output = ob_get_contents();

					ob_end_clean();

					if (strpos($article->text, '{jcomments}') !== false)
					{
						$article->text = str_replace('{jcomments}', '', $article->text) . $output;
					}
					else
					{
						$article->text .= $output;
					}
				}
			}

			JcommentsContentHelper::clear($article);
		}
	}

	/**
	 * Show 'Readmore' layout before or after article introtext in featured view, display comment form otherwise.
	 *
	 * @param   string  $context  The context of the content being passed to the plugin
	 * @param   object  $article  The article object
	 * @param   object  $params   The article params
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	public function onContentBeforeDisplay(string $context, &$article, &$params)
	{
		if ($context == 'com_content.article' || $context == 'com_content.featured' || $context == 'com_content.category')
		{
			$app  = Factory::getApplication();
			$view = $app->input->get('view');

			// Do not display comments in modules
			if ($params->get('moduleclass_sfx', null) !== null)
			{
				return;
			}

			$this->prepareContent($article, $params);

			if (isset($article->text))
			{
				$article->text = str_replace('{jcomments}', '', $article->text);
				$article->introtext = str_replace('{jcomments}', '', $article->introtext);

				if (($view === 'article') && strpos($article->text, '{jcomments}') !== false)
				{
					$article->text = str_replace('{jcomments}', $article->text, $article->text);
				}
			}

			JcommentsContentHelper::clear($article);
		}
	}

	/**
	 * Display comment form
	 *
	 * @param   string  $context  The context of the content being passed to the plugin
	 * @param   object  $article  The article object
	 * @param   object  $params   The article params
	 *
	 * @return  string
	 *
	 * @throws  \Exception
	 * @since   1.6
	 */
	public function onContentAfterDisplay(string $context, &$article, &$params): string
	{
		if ($context == 'com_content.article' || $context == 'com_content.featured' || $context == 'com_content.category')
		{
			// Do not display comments in modules
			if ($params->get('moduleclass_sfx', null) !== null)
			{
				return '';
			}

			$app  = Factory::getApplication();
			$view = $app->input->get('view');

			// Check whether plugin has been unpublished
			if (!PluginHelper::isEnabled('content', 'jcomments')
				|| ($view != 'article')
				|| $params->get('intro_only')
				|| $params->get('popup')
				|| $app->input->getBool('fullview')
				|| $app->input->get('print')
			)
			{
				JcommentsContentHelper::clear($article);

				return '';
			}

			$config    = ComponentHelper::getParams('com_jcomments');
			$isEnabled = ($config->get('comments_on', false)) && ($config->get('comments_off', false) == false);

			if ($isEnabled && $view === 'article' && $this->params->get('show_comments_event') == 'onContentAfterDisplay')
			{
				JcommentsContentHelper::clear($article);

				$basePath = JPATH_ROOT . '/components/com_jcomments';
				$view = $this->getView(
					'Comments',
					'Site',
					'Html',
					// View config
					array('base_path' => $basePath, 'template_path' => $basePath . '/tmpl/comments/'),
					true,
					// Model config
					array('name' => 'Comments', 'prefix' => 'Site', 'base_path' => $basePath)
				);

				ob_start();

				//$view->display(null, array('id' => $article->id, 'object' => 'com_content', 'title' => $article->title));
				$view->display();
				$output = ob_get_contents();

				ob_end_clean();

				return $output;
			}
		}

		return '';
	}

	/**
	 * After delete content method
	 * Method is called right after the content is deleted
	 *
	 * @param   string  $context  The context of the content passed to the plugin
	 * @param   object  $article  A JTableContent object
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onContentAfterDelete(string $context, $article): void
	{
		if ($context == 'com_content.article')
		{
			$factory = $this->app->bootComponent('com_jcomments')->getMVCFactory();

			/*require_once JPATH_ROOT . '/components/com_jcomments/_models/jcomments.php';
			require_once JPATH_ROOT . '/components/com_jcomments/_models/subscriptions.php';

			JCommentsModel::deleteComments($article->id);

			$model = new JcommentsModelSubscriptions;
			$model->unsubscribe($article->id, 'com_content');*/

			/** @var Joomla\Component\Jcomments\Site\Model\CommentModel $model */
			/*$comment = $factory->createModel('Comment', 'Site', array('ignore_request' => true));
			$comment->delete($article->id);*/

			/** @var Joomla\Component\Jcomments\Site\Model\SubscriptionModel $model */
			/*$subscription = $factory->createModel('Subscription', 'Site', array('ignore_request' => true));
			$subscription->unsubscribe($article->id, 'com_content');*/
		}
	}

	/**
	 * After save content method
	 * Article is passed by reference, but after the save, so no changes will be saved.
	 * Method is called right after the content is saved
	 *
	 * @param   string   $context  The context of the content passed to the plugin (added in 1.6)
	 * @param   object   $article  A JTableContent object
	 * @param   boolean  $isNew    If the content is just about to be created
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public function onContentAfterSave(string $context, $article, bool $isNew): void
	{
		if (($context == 'com_content.article' || $context == 'com_content.form') && !$isNew)
		{
			if (JcommentsContentHelper::checkCategory($article->catid))
			{
				//require_once JPATH_ROOT . '/components/com_jcomments/helpers/object.php';

				//JcommentsObject::storeObjectInfo($article->id);

				/** @var Joomla\Component\Jcomments\Site\Model\ObjectModel $model */
				/*$model = $this->app->bootComponent('com_jcomments')->getMVCFactory()
					->createModel('Object', 'Site', array('ignore_request' => true));

				$model->save($article->id);*/
			}
		}
	}

	/**
	 * Get the layout paths
	 *
	 * @return  array
	 *
	 * @throws  \Exception
	 * @since   3.5
	 */
	protected function getLayoutPaths(): array
	{
		$template = Factory::getApplication()->getTemplate();

		return [
			JPATH_ROOT . '/templates/' . $template . '/html/layouts/plugins/' . $this->_type . '/' . $this->_name,
			__DIR__ . '/layouts',
		];
	}

	/**
	 * Get the plugin renderer
	 *
	 * @param   string  $layoutId  Layout identifier
	 *
	 * @return  \Joomla\CMS\Layout\LayoutInterface
	 *
	 * @throws  \Exception
	 * @since   3.5
	 */
	protected function getRenderer(string $layoutId = 'default')
	{
		$renderer = new FileLayout($layoutId);

		$renderer->setIncludePaths($this->getLayoutPaths());

		return $renderer;
	}

	/**
	 * Render a layout of this plugin
	 *
	 * @param   string  $layoutId  Layout identifier
	 * @param   array   $data      Data for the layout
	 *
	 * @return  string
	 *
	 * @throws  \Exception
	 * @since   3.5
	 */
	public function render(string $layoutId, array $data = []): string
	{
		return $this->getRenderer($layoutId)->render($data);
	}

	/**
	 * Debug a layout of this plugin
	 *
	 * @param   string  $layoutId  Layout identifier
	 * @param   array   $data      Optional data for the layout
	 *
	 * @return  string
	 *
	 * @throws  \Exception
	 * @since   3.5
	 */
	public function debug(string $layoutId, array $data = []): string
	{
		return $this->getRenderer($layoutId)->debug($data);
	}

	/**
	 * Method to load and return a view object.
	 *
	 * @param   string   $name         The name of the view.
	 * @param   string   $prefix       Optional view prefix.
	 * @param   string   $type         Optional type of view.
	 * @param   array    $config       Optional configuration array for the view.
	 * @param   boolean  $setModel     Load and set model for view.
	 * @param   array    $modelConfig  Model condifguration.
	 *
	 * @return  \Joomla\CMS\MVC\View\ViewInterface  The view object
	 *
	 * @throws  \Exception
	 * @since   3.10.0
	 */
	private function getView(string $name, string $prefix = '', string $type = 'Html', array $config = [],
		bool $setModel = false, array $modelConfig = []
	)
	{
		if (!isset($config['layout']))
		{
			$config['layout'] = $this->app->input->get('layout', 'default', 'string');
		}

		/** @var Joomla\CMS\MVC\Factory\MVCFactory $factory */
		$factory = $this->app->bootComponent('com_jcomments')->getMVCFactory();

		/** @var Joomla\Component\Jcomments\Site\View\Comments\HtmlView $view */
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
}
