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

defined('_JEXEC') or die;

/** @var array $displayData */

$comment = $displayData['comment'];

if (!isset($comment->isgood) || !isset($comment->ispoor))
{
	return;
}

$value = $comment->vote_value;

if ($value < 0)
{
	$class = ' badge bg-danger';
}
elseif ($value > 0)
{
	$class = ' badge bg-success';
	$value = '+' . $value;
}
else
{
	$class = '';
	$value = '';
}
?>
<span class="vote-value<?php echo $class; ?>"><?php echo $value; ?></span>
