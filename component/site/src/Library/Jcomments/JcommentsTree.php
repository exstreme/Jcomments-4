<?php
/**
 * Simple tree class
 *
 * @package           JoomlaTune.Framework
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

namespace Joomla\Component\Jcomments\Site\Library\Jcomments;

defined('_JEXEC') or die;

// Check for double include
if (!defined('JOOMLATUNE_TREE'))
{
	define('JOOMLATUNE_TREE', 1);

	/**
	 * JComments class for tree functions
	 *
	 * @since  1.0
	 */
	class JcommentsTree
	{
		/**
		 * @var   array  Children list
		 * @since 1.0
		 */
		protected $children = array();

		/**
		 * Class constructor
		 *
		 * @param   array  $items  Array of all objects (objects must contain id and parent fields)
		 *
		 * @since  1.0
		 */
		public function __construct($items)
		{
			$this->children = array();

			foreach ($items as $v)
			{
				$pt   = $v->parent;
				$list = $this->children[$pt] ?? array();
				array_push($list, $v);
				$this->children[$pt] = $list;
			}
		}

		private function buildTree($id, $list = array(), int $maxlevel = 9999, int $level = 0, $number = '')
		{
			if (isset($this->children[$id]) && $level <= $maxlevel)
			{
				if ($number != '')
				{
					$number .= '.';
				}

				$i = 1;

				foreach ($this->children[$id] as $v)
				{
					$id                  = $v->id;
					$list[$id]           = $v;
					$list[$id]->level    = $level;
					$list[$id]->number   = $number . $i;
					$list[$id]->children = isset($this->children[$id]) ? count($this->children[$id]) : 0;
					$list                = $this->buildTree($id, $list, $maxlevel, $level + 1, $list[$id]->number);
					$i++;
				}
			}

			return $list;
		}

		private function getDescendants($id, $list = array(), int $maxlevel = 9999, int $level = 0)
		{
			if (isset($this->children[$id]) && $level <= $maxlevel)
			{
				foreach ($this->children[$id] as $v)
				{
					$id     = $v->id;
					$list[] = $v->id;
					$list   = $this->getDescendants($id, $list, $maxlevel, $level + 1);
				}
			}

			return $list;
		}

		/**
		 * Return objects tree
		 *
		 * @param   integer  $id  Node id (by default node id is 0 - root node)
		 *
		 * @return  array
		 *
		 * @since   1.0
		 */
		public function get(int $id = 0): array
		{
			return $this->buildTree($id);
		}

		/**
		 * Return children items for given node or empty array for empty children list
		 *
		 * @param   integer  $id  Node id (by default node id is 0 - root node)
		 *
		 * @return  array
		 *
		 * @since   1.0
		 */
		public function children(int $id = 0): array
		{
			return $this->children[$id] ?? array();
		}

		/**
		 * Return array with descendants id for given node
		 *
		 * @param   integer  $id  node id (by default node id is 0 - root node)
		 *
		 * @return  array
		 *
		 * @since   1.0
		 */
		public function descendants(int $id = 0): array
		{
			return $this->getDescendants($id);
		}
	}
}
