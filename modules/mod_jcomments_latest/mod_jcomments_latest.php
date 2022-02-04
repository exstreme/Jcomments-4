<?php
/**
 * JComments Latest - Shows latest comments
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Module\LatestComments\Site\Helper\LatestCommentsHelper;

/** @var \Joomla\Registry\Registry $params */
$list = LatestCommentsHelper::getList($params);

if (!empty($list))
{
	\Joomla\CMS\Factory::getApplication()->getLanguage()->load('com_content');

	$grouped          = false;
	$commentsGrouping = $params->get('comments_grouping', 'none');
	$itemHeading      = $params->get('item_heading', 4);

	if ($commentsGrouping !== 'none')
	{
		$grouped = true;
		$list    = LatestCommentsHelper::groupBy($list, $commentsGrouping);
	}
}

require ModuleHelper::getLayoutPath('mod_jcomments_latest', $params->get('layout', 'default'));
