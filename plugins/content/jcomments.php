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

use Joomla\CMS\Access\Access;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

/**
 * Plugin for attaching comments list and form to content item
 */
class PlgContentJcomments extends CMSPlugin
{
	public function onPrepareContent(&$article, &$params)
	{
		require_once JPATH_ROOT . '/components/com_jcomments/helpers/content.php';

		// Check whether plugin has been unpublished
		if (!PluginHelper::isEnabled('content', 'jcomments'))
		{
			JCommentsContent::clear($article);

			return '';
		}

		$app    = Factory::getApplication();
		$option = $app->input->get('option');
		$view   = $app->input->get('view');

		if (!isset($article->id) || ($option != 'com_content' && $option != 'com_alphacontent' && $option != 'com_multicategories'))
		{
			return '';
		}

		if (!isset($params) || $params == null)
		{
			$params = new Registry('');
		}
		elseif (isset($params->_raw) && strpos($params->_raw, 'moduleclass_sfx') !== false)
		{
			return '';
		}

		if ($view == 'frontpage' || $view == 'featured')
		{
			if ($this->params->get('show_frontpage', 1) == 0)
			{
				return '';
			}
		}

		require_once JPATH_ROOT . '/components/com_jcomments/jcomments.class.php';

		JCommentsContent::processForeignTags($article);

		$config           = ComponentHelper::getParams('com_jcomments');
		$categoryEnabled  = JCommentsContent::checkCategory($article->catid);
		$commentsEnabled  = JCommentsContent::isEnabled($article) || $categoryEnabled;
		$commentsDisabled = JCommentsContent::isDisabled($article) || !$commentsEnabled;
		$commentsLocked   = JCommentsContent::isLocked($article);
		$archivesState    = 2;

		if (isset($article->state) && $article->state == $archivesState && $this->params->get('enable_for_archived', 0) == 0)
		{
			$commentsLocked = true;
		}

		$config->set('comments_on', (int) $commentsEnabled);
		$config->set('comments_off', (int) $commentsDisabled);
		$config->set('comments_locked', (int) $commentsLocked);

		if ($view != 'article')
		{
			$user        = $app->getIdentity();
			$authorised  = Access::getAuthorisedViewLevels($user->get('id'));
			$checkAccess = in_array($article->access, $authorised);
			$slug        = $article->slug ?? $article->id;
			$language    = $article->language ?? 0;

			if ($checkAccess)
			{
				$readmoreLink     = Route::_(ContentHelperRoute::getArticleRoute($slug, $article->catid, $language));
				$readmoreRegister = 0;
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
				$readmoreLink     = $link;
				$readmoreRegister = 1;
			}

			// Load template for comments & readmore links
			$tmpl = JCommentsFactory::getTemplate($article->id, 'com_content', false);
			$tmpl->load('tpl_links');

			$tmpl->addVar('tpl_links', 'comments_link_style', ($readmoreRegister ? -1 : 1));
			$tmpl->addVar('tpl_links', 'content-item', $article);
			$tmpl->addVar(
				'tpl_links',
				'show_hits',
				intval($this->params->get('show_hits', 0) && $params->get('show_hits', 0))
			);

			$readmoreDisabled = false;

			if (($params->get('show_readmore') == 0) || (@$article->readmore == 0))
			{
				$readmoreDisabled = true;
			}
			elseif (@$article->readmore > 0)
			{
				$readmoreDisabled = false;
			}

			if ($this->params->get('readmore_link', 1) == 0)
			{
				$readmoreDisabled = true;
			}

			$tmpl->addVar('tpl_links', 'readmore_link_hidden', (int) $readmoreDisabled);

			// Don't fill any readmore variable if it disabled
			if (!$readmoreDisabled)
			{
				if ($readmoreRegister == 1)
				{
					$readmoreText = Text::_('COM_CONTENT_REGISTER_TO_READ_MORE');
				}
				elseif ($readmore = $params->get('readmore'))
				{
					$readmoreText = $readmore;
				}
				elseif ($alternativeReadmore = $article->alternative_readmore)
				{
					$readmoreText = trim($alternativeReadmore);

					if ($params->get('show_readmore_title', 0) != 0)
					{
						$readmoreText .= ' ' . HTMLHelper::_('string.truncate', $article->title, $params->get('readmore_limit'));
					}
				}
				else
				{
					$readmoreText = Text::_('COM_CONTENT_READ_MORE_TITLE');

					if ($params->get('show_readmore_title', 0) == 1)
					{
						$readmoreText = Text::_('COM_CONTENT_READ_MORE')
							. HTMLHelper::_('string.truncate', $article->title, $params->get('readmore_limit'));
					}
				}

				$tmpl->addVar('tpl_links', 'link-readmore', $readmoreLink);
				$tmpl->addVar('tpl_links', 'link-readmore-text', $readmoreText);
				$tmpl->addVar('tpl_links', 'link-readmore-title', $article->title);
				$tmpl->addVar('tpl_links', 'link-readmore-class', $this->params->get('readmore_css_class', 'readmore-link'));
			}

			$commentsDisabled = false;

			if ((int) $config->get('comments_off') == 1)
			{
				$commentsDisabled = true;
			}
			elseif ((int) $config->get('comments_on') == 1)
			{
				$commentsDisabled = false;
			}

			$tmpl->addVar('tpl_links', 'comments_link_hidden', (int) $commentsDisabled);

			$count = 0;

			// Do not query comments count if comments disabled and link hidden
			if (!$commentsDisabled)
			{
				require_once JPATH_ROOT . '/components/com_jcomments/models/jcomments.php';

				$anchor = "";

				if ($this->params->get('comments_count', 1) != 0)
				{
					$acl = JCommentsFactory::getACL();

					$options                 = array();
					$options['object_id']    = (int) $article->id;
					$options['object_group'] = 'com_content';
					$options['published']    = $acl->canPublish()
					|| $acl->canPublishForObject($article->id, 'com_content') ? null : 1;

					$count = JCommentsModel::getCommentsCount($options);

					$tmpl->addVar('tpl_links', 'comments-count', $count);
					$anchor = $count == 0 ? '#addcomments' : '#comments';

					if ($count == 0)
					{
						$linkText = Text::_('LINK_ADD_COMMENT');
					}
					else
					{
						$linkText = Text::plural('LINK_READ_COMMENTS', $count);
					}
				}
				else
				{
					$linkText = Text::_('LINK_ADD_COMMENT');
				}

				$tmpl->addVar('tpl_links', 'link-comment', $readmoreLink . $anchor);
				$tmpl->addVar('tpl_links', 'link-comment-text', $linkText);
				$tmpl->addVar(
					'tpl_links',
					'link-comments-class',
					$this->params->get('comments_css_class', 'comments-link')
				);
			}

			JCommentsContent::clear($article);

			// Hide comments link if comments enabled but link disabled in plugin params
			if ((($this->params->get('comments_count', 1) == 0)
				|| ($count == 0 && $this->params->get('add_comments', 1) == 0)
				|| ($count == 0 && $readmoreRegister == 1))
				&& !$commentsDisabled
			)
			{
				$tmpl->addVar('tpl_links', 'comments_link_hidden', 1);
			}

			// Links position
			if ($this->params->get('links_position', 1) == 1)
			{
				$article->text .= $tmpl->renderTemplate('tpl_links');
			}
			else
			{
				$article->text = $tmpl->renderTemplate('tpl_links') . $article->text;
			}

			$tmpl->freeTemplate('tpl_links');

			if ($this->params->get('readmore_link', 1) == 1)
			{
				$article->readmore          = 0;
				$article->readmore_link     = '';
				$article->readmore_register = false;
			}
		}
		else
		{
			if ($this->params->get('show_comments_event') == 'onPrepareContent')
			{
				$isEnabled = ((int) $config->get('comments_on', 0) == 1) && ((int) $config->get('comments_off', 0) == 0);

				if ($isEnabled && $view == 'article')
				{
					require_once JPATH_ROOT . '/components/com_jcomments/jcomments.php';

					$comments = JComments::show($article->id, 'com_content', $article->title);

					if (strpos($article->text, '{jcomments}') !== false)
					{
						$article->text = str_replace('{jcomments}', $comments, $article->text);
					}
					else
					{
						$article->text .= $comments;
					}
				}
			}

			JCommentsContent::clear($article);
		}

		return '';
	}

	public function onAfterDisplayContent(&$article, &$params)
	{
		if ($this->params->get('show_comments_event', 'onAfterDisplayContent') == 'onAfterDisplayContent')
		{
			require_once JPATH_ROOT . '/components/com_jcomments/helpers/content.php';

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
				JCommentsContent::clear($article);

				return '';
			}

			require_once JPATH_ROOT . '/components/com_jcomments/jcomments.php';

			$config    = ComponentHelper::getParams('com_jcomments');
			$isEnabled = ((int) $config->get('comments_on', 0) == 1) && ((int) $config->get('comments_off', 0) == 0);

			if ($isEnabled && $view == 'article')
			{
				JCommentsContent::clear($article);

				return JComments::show($article->id, 'com_content', $article->title);
			}
		}

		return '';
	}

	public function onContentBeforeDisplay($context, &$article, &$params)
	{
		if ($context == 'com_content.article' || $context == 'com_content.featured' || $context == 'com_content.category')
		{
			$app  = Factory::getApplication();
			$view = $app->input->get('view');

			if ($view == 'featured' || $context == 'com_content.featured')
			{
				if ($this->params->get('show_frontpage', 1) == 0)
				{
					return;
				}
			}

			// Do not display comments in modules
			$data = $params->toArray();

			if (isset($data['moduleclass_sfx']))
			{
				return;
			}

			$originalText  = $article->text ?? '';
			$article->text = '';
			$this->onPrepareContent($article, $params);

			if (isset($article->text))
			{
				if (($view == 'article') && strpos($originalText, '{jcomments}') !== false)
				{
					$originalText = str_replace('{jcomments}', $article->text, $originalText);
				}
				else
				{
					$article->introtext = str_replace('{jcomments}', '', $article->introtext);

					if ($this->params->get('links_position', 1) == 1)
					{
						$article->introtext = $article->introtext . $article->text;
					}
					else
					{
						$article->introtext = $article->text . $article->introtext;
					}
				}
			}

			$article->text = $originalText;
			JCommentsContent::clear($article);
		}
	}

	public function onContentAfterDisplay($context, &$article, &$params)
	{
		if ($context == 'com_content.article' || $context == 'com_content.featured' || $context == 'com_content.category')
		{
			// Do not display comments in modules
			$data = $params->toArray();

			if (isset($data['moduleclass_sfx']))
			{
				return '';
			}

			return $this->onAfterDisplayContent($article, $params);
		}

		return '';
	}

	public function onContentAfterDelete($context, $data)
	{
		if ($context == 'com_content.article')
		{
			require_once JPATH_ROOT . '/components/com_jcomments/models/jcomments.php';
			require_once JPATH_ROOT . '/components/com_jcomments/models/subscriptions.php';

			JCommentsModel::deleteComments($data->id);

			$model = new JcommentsModelSubscriptions;

			return $model->unsubscribe($data->id, 'com_content');
		}

		return true;
	}

	/**
	 * The save event.
	 *
	 * @param   string   $context  The context
	 * @param   object   $article  The table
	 * @param   boolean  $isNew    Is new item
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function onContentAfterSave($context, $article, $isNew)
	{
		if (($context == 'com_content.article' || $context == 'com_content.form') && !$isNew)
		{
			require_once JPATH_ROOT . '/components/com_jcomments/helpers/content.php';

			if (JCommentsContent::checkCategory($article->catid))
			{
				require_once JPATH_ROOT . '/components/com_jcomments/helpers/object.php';

				JCommentsObject::storeObjectInfo($article->id);
			}
		}
	}
}
