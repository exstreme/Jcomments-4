<?php
/**
 * JComments Latest Commented - Shows latest commented items
 *
 * @version           4.0.0
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
	<ul class="jcomments-latest-commented list-group list-group-flush <?php echo $params->get('moduleclass_sfx'); ?>">

		<?php foreach ($list as $item):
			if ($params->get('showCommentsCount')): ?>

			<li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
				style="padding-left: 0; padding-right: 0;">
				<a href="<?php echo $item->link; ?>#comments"><?php echo $item->title; ?></a>
				<span class="badge bg-primary rounded-pill"><?php echo $item->commentsCount; ?></span>
			</li>

			<?php else: ?>

			<li class="list-group-item list-group-item-action d-flex justify-content-between"
			    style="padding-left: 0; padding-right: 0;">
				<a href="<?php echo $item->link; ?>#comments"><?php echo $item->title; ?></a>
			</li>

			<?php endif;
		endforeach; ?>

	</ul>
<?php endif;
