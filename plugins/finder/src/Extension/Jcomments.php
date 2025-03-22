<?php
/**
 * JComments finder plugin.
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Plugin\Finder\Jcomments\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Finder as FinderEvent;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseQuery;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

/**
 * Smart Search adapter for com_jcomments.
 *
 * @since  2.5
 */
final class Jcomments extends Adapter implements SubscriberInterface
{
	use DatabaseAwareTrait;

	/**
	 * The plugin identifier.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $context = 'Jcomments';

	/**
	 * The extension name.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $extension = 'com_jcomments';

	/**
	 * The sublayout to use when rendering the results.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $layout = 'comment';

	/**
	 * The type of content that the adapter indexes.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $type_title = 'Comment';

	/**
	 * The table name.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $table = '#__jcomments';

	/**
	 * The field the published state is stored in.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $state_field = 'published';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   5.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return array_merge(
			[
				'onFinderChangeState' => 'onFinderChangeState',
				'onFinderAfterDelete' => 'onFinderAfterDelete',
				'onFinderBeforeSave'  => 'onFinderBeforeSave',
				'onFinderAfterSave'   => 'onFinderAfterSave',
			],
			parent::getSubscribedEvents()
		);
	}

	/**
	 * Method to setup the indexer to be run.
	 *
	 * @return  bool  True on success.
	 *
	 * @since   2.5
	 */
	protected function setup()
	{
		return true;
	}

	/**
	 * Method to remove the link information for items that have been deleted.
	 *
	 * @param   FinderEvent\AfterDeleteEvent   $event  The event instance.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  \Exception on database error.
	 */
	public function onFinderAfterDelete(FinderEvent\AfterDeleteEvent $event): void
	{
		$context = $event->getContext();
		$table = $event->getItem();

		if ($context === 'com_jcomments.comment')
		{
			$id = $table->id;
		}
		elseif ($context === 'com_finder.index')
		{
			$id = $table->link_id;
		}
		else
		{
			return;
		}

		// Remove item from the index.
		$this->remove($id);
	}

	/**
	 * Smart Search after save content method.
	 * Reindexes the link information for an article that has been saved.
	 * It also makes adjustments if the access level of an item or the
	 * category to which it belongs has changed.
	 *
	 * @param   FinderEvent\AfterSaveEvent   $event  The event instance.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  \Exception on database error.
	 */
	public function onFinderAfterSave(FinderEvent\AfterSaveEvent $event): void
	{
		$context = $event->getContext();
		$row     = $event->getItem();
		$isNew   = $event->getIsNew();

		// We only want to handle articles here.
		if ($context === 'com_content.article' || $context === 'com_content.form') {
			// Check if the access levels are different.
			if (!$isNew && $this->old_access != $row->access) {
				// Process the change.
				$this->itemAccessChange($row);
			}

			// Reindex the item.
			$this->reindex($row->id);
		}

		// Check for access changes in the category.
		if ($context === 'com_categories.category') {
			// Check if the access levels are different.
			if (!$isNew && $this->old_cataccess != $row->access) {
				$this->categoryAccessChange($row);
			}
		}
	}

	/**
	 * Smart Search before content save method.
	 * This event is fired before the data is actually saved.
	 *
	 * @param   FinderEvent\BeforeSaveEvent   $event  The event instance.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  \Exception on database error.
	 */
	public function onFinderBeforeSave(FinderEvent\BeforeSaveEvent $event)
	{
		$context = $event->getContext();
		$row     = $event->getItem();
		$isNew   = $event->getIsNew();

		// We only want to handle articles here.
		if ($context === 'com_content.article' || $context === 'com_content.form') {
			// Query the database for the old access level if the item isn't new.
			if (!$isNew) {
				$this->checkItemAccess($row);
			}
		}

		// Check for access levels from the category.
		if ($context === 'com_categories.category') {
			// Query the database for the old access level if the item isn't new.
			if (!$isNew) {
				$this->checkCategoryAccess($row);
			}
		}
	}

	/**
	 * Method to update the link information for items that have been changed
	 * from outside the edit screen. This is fired when the item is published,
	 * unpublished, archived, or unarchived from the list view.
	 *
	 * @param   FinderEvent\AfterChangeStateEvent   $event  The event instance.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function onFinderChangeState(FinderEvent\AfterChangeStateEvent $event)
	{
		$context = $event->getContext();
		$pks     = $event->getPks();
		$value   = $event->getValue();

		// We only want to handle articles here.
		if ($context === 'com_jcomments.comment' || $context === 'com_jcomments.form')
		{
			$this->itemStateChange($pks, $value);
		}

		// Handle when the plugin is disabled.
		if ($context === 'com_plugins.plugin' && $value === 0)
		{
			$this->pluginDisable($pks);
		}
	}

	/**
	 * Method to index an item. The item must be a Result object.
	 *
	 * @param   Result  $item  The item to index as a Result object.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  \Exception on database error.
	 */
	protected function index(Result $item)
	{
		$item->setLanguage();

		// Check if the extension is enabled.
		if (ComponentHelper::isEnabled($this->extension) === false)
		{
			return;
		}

		$item->context = 'com_jcomments.comment';

		// Remove all bbcode tags. NOTE! In HTML mode tags will strip by finder.
		if (ComponentHelper::getParams('com_jcomments')->get('editor_format') == 'bbcode')
		{
			$bbcode = JcommentsFactory::getBbcode();
			$item->summary = $bbcode->filter($item->summary, true);
			$item->summary = $bbcode->filterCustom($item->summary, true);
		}

		$componentXML = Installer::parseXMLInstallFile(
			Path::clean(JPATH_ROOT . '/administrator/components/com_jcomments/jcomments.xml')
		);

		// Create a URL as identifier to recognise items again.
		if (!version_compare($componentXML['version'], '4.1.0', 'ge'))
		{
			// Old link variant for
			$item->url = $item->object_link . '#comment-' . $item->id;
		}
		else
		{
			$item->url = 'index.php?option=com_jcomments&task=comments.goto&object_id=' . $item->object_id
				. '&object_group=' . $item->object_group . '&comment_id=' . $item->id
				. '&lang=' . StringHelper::substr($item->language, 0, 2) . '#comment-item-' . $item->id;
		}

		// Build the necessary route and path information.
		$item->route = Route::link('Site', $item->url);

		// Adjust the title if necessary.
		$item->title = HTMLHelper::_(
			'string.truncate',
			empty($item->title) ? $item->summary : $item->title,
			100, true, false
		);

		// Add the meta author.
		$item->metaauthor = ContentHelper::getCommentAuthorName($item);

		// Add the metadata processing instructions.
		$item->addInstruction(Indexer::META_CONTEXT, 'author');

		// Translate the state.
		$item->state = (int) $item->published == 1 && $item->deleted == 0;

		// Get taxonomies to display
		$taxonomies = $this->params->get('taxonomies', ['type', 'author', 'language']);

		// Add the type taxonomy data.
		if (in_array('type', $taxonomies)) {
			$item->addTaxonomy('Type', 'Comment');
		}

		// Add the author taxonomy data.
		$author = ContentHelper::getCommentAuthorName($item);

		if (in_array('author', $taxonomies) && !empty($author)) {
			$item->addTaxonomy('Author', $author, $item->state);
		}

		// Add the language taxonomy data.
		if (in_array('language', $taxonomies))
		{
			$item->addTaxonomy('Language', $item->language);
		}

		// Get content extras.
		Helper::getContentExtras($item);
		Helper::addCustomFields($item, 'com_jcomments.comment');

		// Index the item.
		$this->indexer->index($item);
	}

	/**
	 * Method to get the SQL query used to retrieve the list of comments.
	 *
	 * @param   mixed  $query  A DatabaseQuery object or null.
	 *
	 * @return  DatabaseQuery  A database object.
	 *
	 * @since   2.5
	 */
	protected function getListQuery($query = null)
	{
		$db = $this->getDatabase();

		// Check if we can use the supplied SQL query. Alias should be an 'a.', otherwise it will throw an error.
		$query = $query instanceof DatabaseQuery ? $query : $db->getQuery(true)
			->select(
				$db->quoteName(
					array(
						'a.id', 'a.object_id', 'a.object_group', 'a.lang', 'a.userid', 'a.name', 'a.username', 'a.title',
						'a.published', 'a.deleted'
					)
				)
			)
			->select($db->quoteName('a.comment', 'summary'))
			->select($db->quoteName('a.lang', 'language'))
			->select($db->quoteName('o.access', 'access'))
			->select($db->quoteName('o.link', 'object_link'))
			->from($db->quoteName('#__jcomments', 'a'))
			->leftJoin(
				$db->quoteName('#__jcomments_objects', 'o'),
				'o.object_id = a.object_id AND o.object_group = a.object_group'
			);

		return $query;
	}
}
