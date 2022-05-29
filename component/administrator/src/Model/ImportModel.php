<?php
/**
 * JComments - Joomla Comment System
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Component\Jcomments\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

class ImportModel extends BaseDatabaseModel
{
	protected $_adapters = array();
	protected static $initialised = false;

	public function __construct($config = array())
	{
		parent::__construct($config);
	}

	public function getItems()
	{
		$app      = Factory::getApplication();
		$tables   = $this->getDbo()->getTableList();
		$adapters = $this->getAdapters();

		$items = array();

		foreach ($adapters as $adapter)
		{
			$tableName = $adapter->getTableName();
			$tableName = str_replace('#__', $app->get('dbprefix'), $tableName);

			if (in_array($tableName, $tables))
			{
				$item             = new \StdClass;
				$item->code       = $adapter->getCode();
				$item->name       = $adapter->getName();
				$item->author     = $adapter->getAuthor();
				$item->license    = $adapter->getLicense();
				$item->licenseUrl = $adapter->getLicenseUrl();
				$item->siteUrl    = $adapter->getSiteUrl();
				$item->comments   = $adapter->getCount();

				$items[] = $item;
			}
		}

		return $items;
	}

	public function import($source, $language, $start = 0, $limit = 100)
	{
		$adapters = $this->getAdapters();

		if (isset($adapters[$source]))
		{
			if ($start == 0)
			{
				$this->deleteComments(strtolower($source));
			}

			$adapter = $adapters[$source];
			$adapter->execute($language, $start, $limit);

			$total = $adapter->getCount();
			$count = $this->getCommentsCount($source);

			if ($total == $count)
			{
				$this->updateParent($source);
			}

			$this->setState($this->getName() . '.total', $total);
			$this->setState($this->getName() . '.count', $count);

			return true;
		}

		return false;
	}

	public function updateParent($source)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->update($db->quoteName('#__jcomments') . ' AS c1, ' . $db->quoteName('#__jcomments') . ' AS c2')
			->set('c1.parent = c2.id')
			->where('c1.source = c2.source')
			->where('c1.id <> c2.id')
			->where('c1.parent <> 0')
			->where('c1.parent = c2.source_id')
			->where('c1.source = ' . $db->quote($source));

		$db->setQuery($query);
		$db->execute();
	}

	public function getCommentsCount($source)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__jcomments'))
			->where($db->quoteName('source') . '=' . $db->quote($source));

		$db->setQuery($query);

		return $db->loadResult();
	}

	public function deleteComments($source)
	{
		$db = $this->getDbo();

		$query = $db->getQuery(true)
			->delete()
			->from($db->quoteName('#__jcomments'))
			->where($db->quoteName('source') . '=' . $db->quote($source));

		$db->setQuery($query);
		$db->execute();

		$query = $db->getQuery(true)
			->delete()
			->from($db->quoteName('#__jcomments_subscriptions'))
			->where($db->quoteName('source') . '=' . $db->quote($source));

		$db->setQuery($query);
		$db->execute();
	}

	public function getAdapters()
	{
		if (!self::$initialised)
		{
			require_once JPATH_BASE . '/components/com_jcomments/classes/import/adapter.php';

			$this->_adapters = array();
			$path            = JPATH_BASE . '/components/com_jcomments/classes/import/adapters';
			$files = Folder::files($path, '\.php');

			foreach ($files as $file)
			{
				require_once $path . '/' . $file;

				$name      = File::stripExt($file);
				$className = 'JCommentsImport' . ucfirst($name);

				if (class_exists($className))
				{
					$adapter                              = new $className;
					$this->_adapters[$adapter->getCode()] = $adapter;
				}
			}
		}

		return $this->_adapters;
	}
}
