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
use Joomla\CMS\Table\Table;

class JCommentsImportJAComment extends JCommentsImportAdapter
{
	public function __construct()
	{
		$this->code       = 'jacomment';
		$this->extension  = 'com_jacomment';
		$this->name       = 'JA Comment';
		$this->author     = 'JoomlArt';
		$this->license    = 'Commercial';
		$this->licenseUrl = '';
		$this->siteUrl    = 'http://www.joomlart.com/joomla/extensions/ja-comment-component';
		$this->tableName  = '#__jacomment_items';
	}

	public function execute($language, $start = 0, $limit = 100)
	{
		$db     = Factory::getContainer()->get('DatabaseDriver');
		$source = $this->getCode();

		$query = $db->getQuery(true);

		$query->select('c.*');
		$query->from($db->quoteName($this->tableName) . ' AS c');
		$query->select('u.username as user_username, u.name as user_name, u.email as user_email');
		$query->join('LEFT', $db->quoteName('#__users') . ' AS u ON c.userid = u.id');
		$query->order($db->escape('c.date'));

		$db->setQuery($query, $start, $limit);
		$rows = $db->loadObjectList();

		foreach ($rows as $row)
		{
			$table               = Table::getInstance('Comment', 'JCommentsTable');
			$table->object_id    = $row->contentid;
			$table->object_group = $row->option;
			$table->parent       = $row->parentid;
			$table->userid       = $row->userid;
			$table->name         = $row->name;
			$table->username     = $row->username;
			$table->comment      = $row->comment;
			$table->ip           = $row->ip;
			$table->email        = $row->email;
			$table->homepage     = $row->website;
			$table->published    = $row->published;
			$table->date         = $row->date;
			$table->isgood       = $row->voted > 0 ? $row->voted : 0;
			$table->ispoor       = $row->voted < 0 ? abs($row->voted) : 0;
			$table->lang         = $language;
			$table->source_id    = $row->id;
			$table->source       = $source;
			$table->store();
		}
	}
}
