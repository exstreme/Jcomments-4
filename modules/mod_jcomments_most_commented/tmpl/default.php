<?php
/**
 * JComments Most Commented - Shows most commented items
 *
 * @package           JComments
 * @author            JComments team
 * @copyright     (C) 2006-2016 Sergey M. Litvinov (http://www.joomlatune.ru)
 *                (C) 2016-2022 exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license           GNU General Public License version 2 or later; GNU/GPL: https://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

/** @var object $params */

if (!empty($list)): ?>
	<ul class="jcomments-most-commented list-group list-group-flush <?php echo $params->get('moduleclass_sfx'); ?>">
		<?php foreach ($list as $item): ?>
		<li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
			<a href="<?php echo $item->link; ?>#comments"><?php echo $item->title; ?></a>

			<?php if ($params->get('showCommentsCount')): ?>
				<span class="badge bg-success rounded-pill">+<?php echo $item->commentsCount; ?></span>
			<?php endif; ?>
		</li>
		<?php endforeach; ?>
	</ul>
<?php endif;
