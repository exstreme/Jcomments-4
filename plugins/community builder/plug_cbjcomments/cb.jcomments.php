<?php
/**
 * CB JComments - CommunityBuilder plugin displays a tab with the user comments
 *
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

if (!(defined('_VALID_CB') || defined('_JEXEC')))
{
	die;
}

use CBLib\Registry\GetterInterface;
use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;
use Joomla\String\StringHelper;

/**
 * Tab class for handling the CB tab.
 * Build and display user comments tab header and content.
 *
 * @since 1.0
 */
class JCommentsMyComments extends cbTabHandler
{
	/**
	 * @var    Joomla\Database\DatabaseDriver $db
	 *
	 * @since  2.6
	 */
	private $db;

	/**
	 * @var    Joomla\CMS\Application\CMSApplicationInterface $app
	 *
	 * @since  2.6
	 */
	private $app;

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->app = Factory::getApplication();
		$this->db  = Factory::getContainer()->get('DatabaseDriver');

		// CB not have translated strings for plug_cbjcomments
		$this->app->getLanguage()->load('plug_cbjcomments', JPATH_SITE . '/components/com_comprofiler/plugin/user/plug_cbjcomments');
	}

	/**
	 * Labeller for title:
	 * Returns a profile view tab title
	 *
	 * @param   CB\Database\Table\TabTable   $tab       the tab database entry
	 * @param   CB\Database\Table\UserTable  $user      the user being displayed
	 * @param   integer                      $ui        1 for front-end, 2 for back-end
	 * @param   array                        $postdata  _POST data for saving edited tab content as generated with getEditTab
	 * @param   string                       $reason    'profile' for user profile view, 'edit' for profile edit,
	 *                                                  'register' for registration, 'search' for searches
	 *
	 * @return  string|boolean  Either string HTML for tab content, or false if ErrorMSG generated
	 *
	 * @throws  Exception
	 * @since   2.6
	 */
	public function getTabTitle($tab, $user, $ui, $postdata, $reason = null)
	{
		$title = parent::getTabTitle($tab, $user, $ui, $postdata, $reason);
		$total = 0;

		if (($reason != 'profile') || (!$this->params->get('tab_count', 1, GetterInterface::INT)))
		{
			return $title;
		}

		$model = JPATH_SITE . '/components/com_jcomments/models/jcomments.php';

		if (is_file($model))
		{
			require_once $model;

			$user  = $this->app->getIdentity();
			$total = JCommentsModel::getCommentsCount(
				array(
					'userid'    => $user->get('id'),
					'published' => 1,
					'filter'    => $this->db->qn('object_group') . ' != ' . $this->db->quote('com_comprofiler')
				)
			);
		}

		// We not use tab title from backend.
		return Text::_('COMMENTS_LIST_HEADER') . ' <span class="badge badge-pill badge-light border text-muted">' . $total . '</span>';
	}

	/**
	 * Generates the HTML to display the user profile tab
	 *
	 * @param   CB\Database\Table\TabTable   $tab   the tab database entry
	 * @param   CB\Database\Table\UserTable  $user  the user being displayed
	 * @param   integer                      $ui    1 for front-end, 2 for back-end
	 *
	 * @return  string  Either string HTML for tab content, or false if ErrorMSG generated
	 *
	 * @throws  Exception
	 * @since   1.0
	 */
	public function getDisplayTab($tab, $user, $ui): ?string
	{
		$content  = '';
		$comments = JPATH_SITE . '/components/com_jcomments/jcomments.php';

		if (is_file($comments))
		{
			require_once $comments;

			$user = $this->app->getIdentity();

			$this->params->def('count', 5);
			$this->params->def('limit_comment_text', 100);
			$this->params->def('orderby_object_title', 1);

			/** @var Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$source = trim(str_replace(' ', '', $this->params->get('source', 'com_content')));
			$source = explode(',', $source);
			$count  = (int) $this->params->get('count');

			$access   = array_unique(Access::getAuthorisedViewLevels($user->get('id')));
			$access[] = 0;

			$query = $this->db->getQuery(true)
				->select(
					$this->db->qn(
						array(
							'c.id', 'c.userid', 'c.comment', 'c.title', 'c.name', 'c.username', 'c.email', 'c.date',
							'c.object_id', 'c.object_group'
						)
					)
				)
				->select("'' as avatar")
				->select($this->db->qn('o.title', 'object_title'))
				->select($this->db->qn('o.link', 'object_link'))
				->select($this->db->qn('o.access', 'object_access'))
				->select($this->db->qn('o.userid', 'object_owner'))
				->from($this->db->qn('#__jcomments', 'c'))
				->innerJoin(
					$this->db->qn('#__jcomments_objects', 'o'),
					'c.object_id = o.object_id AND c.object_group = o.object_group AND c.lang = o.lang'
				)
				->where($this->db->qn('c.published') . ' = 1')
				->where($this->db->qn('c.deleted') . ' = 0')
				->where($this->db->qn('c.userid') . ' = :uid')
				->where($this->db->qn('o.link') . " <> ''")
				->bind(':uid', $user->id, ParameterType::INTEGER);

			if (is_array($access))
			{
				$query->whereIn($this->db->qn('o.access'), $access);
			}
			else
			{
				$query->where($this->db->qn('o.access') . ' <= :access')
					->bind(':access', $access, ParameterType::INTEGER);
			}

			if (count($source))
			{
				if (count($source) == 1 && $source[0] == 'com_content')
				{
					$query->innerJoin(
						$this->db->qn('#__content', 'cc'),
						$this->db->qn('cc.id') . ' = ' . $this->db->qn('o.object_id')
					)->leftJoin(
						$this->db->qn('#__categories', 'ct'),
						$this->db->qn('ct.id') . ' = ' . $this->db->qn('cc.catid')
					)->where($this->db->qn('c.object_group') . " = 'com_content'");

					/*$now  = Factory::getDate()->toSql();
					$query->where('(' . $this->db->qn('cc.publish_up') . " = '0000-00-00 00:00:00'"
						. ' OR ' . $this->db->qn('cc.publish_up') . ' <= :now_up)'
					)->where('(' . $this->db->qn('cc.publish_down') . " = '0000-00-00 00:00:00'"
						. ' OR ' . $this->db->qn('cc.publish_down') . ' >= :now_down)'
					)
						->bind(':now_up', $now)
						->bind(':now_down', $now);*/
				}
				else
				{
					$query->whereIn($this->db->qn('c.object_group'), $source);
				}
			}

			$query->order($this->db->qn('c.date') . ' DESC');

			$db->setQuery($query, 0, $count);
			$list = $db->loadObjectList();

			if (count($list))
			{
				$document = $this->app->getDocument();
				$document->addStylesheet('components/com_comprofiler/plugin/user/plug_cbjcomments/css/style.css');

				$config = \Joomla\CMS\Component\ComponentHelper::getParams('com_jcomments');
				$bbcode = JCommentsFactory::getBBCode();
				$smiles = JCommentsFactory::getSmilies();

				$showCommentTitle = $this->params->get('show_comment_title', 0);
				$showReadmore     = $this->params->get('show_readmore', 0);
				$readmoreText     = $this->params->get('readmore', '');
				$showSmiles       = $this->params->get('show_smiles', 0);
				$limitCommentText = $this->params->get('limit_comment_text', 100);

				if ($showReadmore && empty($readmoreText))
				{
					$this->app->getLanguage()->load('com_content', JPATH_SITE);

					$readmoreText = Text::_('COM_CONTENT_FEED_READMORE') . '...';
				}

				// Prepare comments list
				foreach ($list as $item)
				{
					$item->displayObjectTitle  = $item->object_title;
					$item->displayCommentDate  = $item->date;
					$item->displayCommentTitle = JCommentsText::censor($item->title);
					$item->displayCommentLink  = $item->object_link . '#comment-' . $item->id;

					$text = JCommentsText::censor($item->comment);
					$text = $bbcode->filter($text, true);
					$text = JCommentsText::cleanText($text);

					if ($limitCommentText && StringHelper::strlen($text) > $limitCommentText)
					{
						$text = HTMLHelper::_('string.truncate', $text, $config->get('limit_comment_text'));
					}

					switch ($showSmiles)
					{
						case 1:
							$text = $smiles->replace($text);
							break;
						case 2:
							$text = $smiles->strip($text);
							break;
					}

					$item->displayCommentText = $text;
					$item->readmoreText       = $readmoreText;
				}

				// Group comments by objects
				if ($this->params->get('orderby_object_title') == 0)
				{
					$list = $this->groupBy($list, 'object_title', null);
				}
				else
				{
					$list = $this->groupBy($list, 'object_title');
				}

				$content = '<ul class="cb-jcomments-latest">';

				// Display comments list
				foreach ($list as $groupName => $group)
				{
					$content .= '<li>';

					if ($group[0]->object_link != '')
					{
						$content .= '<h4><a href="' . $group[0]->object_link . '">' . $groupName . '</a></h4>';
					}
					else
					{
						$content .= $groupName;
					}

					$content .= '<ul>';

					foreach ($group as $_item)
					{
						$content .= '<li>';
						$content .= '<div class="border rounded py-1 pb-1 px-2 comment">';
						$content .= '<span class="text-muted date">
							<span class="icon-calendar icon-fw" aria-hidden="true"></span>
							<time datetime="' . HTMLHelper::_('date', $_item->displayCommentDate, 'c') . '" itemprop="datePublished">'
								. HTMLHelper::_('date', $_item->displayCommentDate, 'DATE_FORMAT_LC5') . '
							</time>
						</span>';

						if ($showCommentTitle && $_item->displayCommentTitle)
						{
							$content .= '<a class="title" href="' . $_item->displayCommentLink . '">';
							$content .= $_item->displayCommentTitle;
							$content .= '</a>';
						}

						$content .= '<div class="mt-2 pb-1 text-break">' . $_item->displayCommentText . '</div>';

						if ($showReadmore)
						{
							$content .= '<div class="cb-jcomments-readmore">';
							$content .= '<a href="' . $_item->displayCommentLink . '">' . $_item->readmoreText . '</a>';
							$content .= '</div>';
						}

						$content .= '</div>';
						$content .= '</li>';
					}

					$content .= '</ul>';
					$content .= '</li>';
				}

				$content .= '</ul>';
			}
		}

		return $content;
	}

	protected function groupBy($list, $fieldName, $groupingDirection = 'ksort')
	{
		$grouped = array();

		if (!is_array($list))
		{
			if ($list == '')
			{
				return $grouped;
			}

			$list = array($list);
		}

		foreach ($list as $key => $item)
		{
			if (!isset($grouped[$item->$fieldName]))
			{
				$grouped[$item->$fieldName] = array();
			}

			$grouped[$item->$fieldName][] = $item;
			unset($list[$key]);
		}

		if ($groupingDirection !== null)
		{
			$groupingDirection($grouped);
		}

		return $grouped;
	}
}

/**
 * Tab class for handling the CB tab.
 * Build and display user profile comments tab header and content(list with profile comments and the form).
 *
 * @since 1.0
 */
class JCommentsProfileComments extends cbTabHandler
{
	/**
	 * @var    Joomla\CMS\Application\CMSApplicationInterface $app
	 *
	 * @since  2.6
	 */
	private $app;

	/**
	 * Constructor
	 *
	 * @since 1.0
	 */
	public function __construct()
	{
		parent::__construct();

		$this->app = Factory::getApplication();

		// CB not have translated strings for plug_cbjcomments
		$this->app->getLanguage()->load('plug_cbjcomments', JPATH_SITE . '/components/com_comprofiler/plugin/user/plug_cbjcomments');
	}

	/**
	 * Labeller for title:
	 * Returns a profile view tab title
	 *
	 * @param   CB\Database\Table\TabTable   $tab       the tab database entry
	 * @param   CB\Database\Table\UserTable  $user      the user being displayed
	 * @param   integer                      $ui        1 for front-end, 2 for back-end
	 * @param   array                        $postdata  _POST data for saving edited tab content as generated with getEditTab
	 * @param   string                       $reason    'profile' for user profile view, 'edit' for profile edit,
	 *                                                  'register' for registration, 'search' for searches
	 *
	 * @return  string|boolean  Either string HTML for tab content, or false if ErrorMSG generated
	 *
	 * @throws  Exception
	 * @since   2.6
	 */
	public function getTabTitle($tab, $user, $ui, $postdata, $reason = null)
	{
		$title = parent::getTabTitle($tab, $user, $ui, $postdata, $reason);
		$total = 0;

		if (($reason != 'profile') || (!$this->params->get('tab_count', 1, GetterInterface::INT)))
		{
			return $title;
		}

		$model = JPATH_SITE . '/components/com_jcomments/models/jcomments.php';

		if (is_file($model))
		{
			require_once $model;

			/** @var Joomla\Database\DatabaseDriver $db */
			$db = Factory::getContainer()->get('DatabaseDriver');

			$user  = $this->app->getIdentity();
			$total = JCommentsModel::getCommentsCount(
				array(
					'userid'    => $user->get('id'),
					'published' => 1,
					'filter'    => $db->qn('object_group') . ' = ' . $db->quote('com_comprofiler')
				)
			);
		}

		// We not use tab title from backend.
		return Text::_('PROFILE_COMMENTS_TAB') . ' <span class="badge badge-pill badge-light border text-muted">' . $total . '</span>';
	}

	/**
	 * Generates the HTML to display the user profile tab
	 *
	 * @param   CB\Database\Table\TabTable   $tab   the tab database entry
	 * @param   CB\Database\Table\UserTable  $user  the user being displayed
	 * @param   integer                      $ui    1 for front-end, 2 for back-end
	 *
	 * @return  string  Either string HTML for tab content, or false if ErrorMSG generated
	 *
	 * @throws  Exception
	 * @since   1.0
	 */
	public function getDisplayTab($tab, $user, $ui): ?string
	{
		$content = '';

		$comments = JPATH_SITE . '/components/com_jcomments/jcomments.php';

		if (is_file($comments))
		{
			require_once $comments;

			$content = JComments::show($user->id, 'com_comprofiler');
		}

		return $content;
	}
}
