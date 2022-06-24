<?php
/**
 * JComments Top Posters - Shows list of top posters
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
use Joomla\Module\TopPosters\Site\Helper\TopPostersHelper;

/** @var \Joomla\Registry\Registry $params */
$list = TopPostersHelper::getList($params);

require ModuleHelper::getLayoutPath('mod_jcomments_top_posters', $params->get('layout', 'default'));
