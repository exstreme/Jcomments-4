<?php
/**
 * JComments plugin for EasyBlog posts support (https://stackideas.com/easyblog)
 *
 * @version       4.0
 * @package       JComments
 * @copyright (C) 2006-2016 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @copyright (C) 2016 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsObjectinfo;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsPlugin;
use Joomla\Database\ParameterType;

class jc_com_easyblog extends JcommentsPlugin
{
	public function getObjectInfo($id, $language = null)
	{
		$info = new JcommentsObjectInfo;

		$routerHelper = JPATH_ADMINISTRATOR . '/components/com_easyblog/includes/router.php';

		if (is_file($routerHelper))
		{
			require_once $routerHelper;

			/** @var \Joomla\Database\DatabaseInterface $db */
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);

			$query->select($db->quoteName(array('id', 'title', 'created_by', 'category_id')))
				->from($db->quoteName('#__easyblog_post'))
				->where('id = :id')
				->bind(':id', $id, ParameterType::INTEGER);

			$row = $db->loadObject();

			if (!empty($row))
			{
				$info->category_id = $row->category_id;
				$info->title       = $row->title;
				$info->userid      = $row->created_by;
				$info->link        = EBR::_('index.php?option=com_easyblog&view=entry&id=' . $id);
			}
		}

		return $info;
	}
}
