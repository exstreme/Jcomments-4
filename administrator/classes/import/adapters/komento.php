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

class JCommentsImportKomento extends JCommentsImportAdapter
{
	public function __construct()
	{
		$this->code       = 'komento';
		$this->extension  = 'com_komento';
		$this->name       = 'Komento';
		$this->author     = 'Stack Ideas';
		$this->license    = 'GNU/GPL';
		$this->licenseUrl = 'http://www.gnu.org/licenses/gpl-2.0.html';
		$this->siteUrl    = 'http://stackideas.com/komento.html';
		$this->tableName  = '#__komento_comments';
	}

	public function execute($language, $start = 0, $limit = 100)
	{
		$db     = Factory::getContainer()->get('DatabaseDriver');
		$source = $this->getCode();

		$query = $db->getQuery(true);

		$query->select('c.*');
		$query->from($db->quoteName($this->tableName) . ' AS c');
		$query->select('u.username as user_username, u.name as user_name, u.email as user_email');
		$query->join('LEFT', $db->quoteName('#__users') . ' AS u ON c.created_by = u.id');
		$query->order($db->escape('c.created'));

		$db->setQuery($query, $start, $limit);
		$rows = $db->loadObjectList();

		foreach ($rows as $row)
		{
			$table               = Table::getInstance('Comment', 'JCommentsTable');
			$table->object_id    = $row->cid;
			$table->object_group = $row->component;
			$table->parent       = $row->parent_id;
			$table->userid       = $row->created_by;
			$table->name         = isset($row->user_name) ? $row->user_name : $row->name;
			$table->username     = isset($row->user_username) ? $row->user_username : $row->name;
			$table->email        = isset($row->user_email) ? $row->user_email : $row->email;
			$table->homepage     = $row->url;
			$table->title        = $row->title;
			$table->comment      = $row->comment;
			$table->ip           = $row->ip;
			$table->published    = $row->published;
			$table->date         = $row->created;
			$table->lang         = $language;
			$table->source_id    = $row->id;
			$table->source       = $source;
			$table->store();
		}
	}
}
