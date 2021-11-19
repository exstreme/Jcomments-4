<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       4.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

/** @var JoomlaTuneTemplate $displayData */
$comment = $displayData->getVar('comment');
$value   = intval($comment->isgood - $comment->ispoor);

// If current value is 0 and user has no rights to vote - hide 0
if ($value == 0 && $displayData->getVar('button-vote', 0) == 0 && $displayData->getVar('get_comment_vote', 0) == 0):
	return;
else:
	if ($value < 0)
	{
		$class = 'badge bg-danger';
	}
	elseif ($value > 0)
	{
		$class = 'badge bg-success';
		$value = '+' . $value;
	}
	else
	{
		$class = 'badge bg-secondary';
	}
	?>
	<span class="vote-value <?php echo $class; ?>"><?php echo $value; ?></span>
<?php endif;
