<?php
/**
 * JComments Top Posters - Shows list of top posters
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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/** @var object $params */

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

if (!empty($list)): ?>
	<ul class="jcomments-top-posters list-group list-group-flush <?php echo $params->get('moduleclass_sfx'); ?>">
		<?php foreach ($list as $item): ?>
			<li class="list-group-item d-flex justify-content-between align-items-center" style="padding-left: 0; padding-right: 0;">
				<div class="user-name start-0 text-truncate">
					<?php if ($params->get('show_avatar')): ?>
						<img src="<?php echo $item->avatar; ?>" width="24" height="24" alt="" class="gravatar-img">
					<?php endif; ?>

					<?php echo $item->displayAuthorName; ?>
				</div>
				<div class="stats end-0">
					<?php if ($params->get('show_comments_count')): ?>
						<span class="badge bg-primary rounded-pill hasTooltip"
							  title="<?php echo Text::_('MOD_JCOMMENTS_TOP_POSTERS_FIELD_COMMENTS_COUNT_LABEL'); ?>">
							<?php echo $item->commentsCount; ?>
						</span>
					<?php endif; ?>

					<?php if ($params->get('show_votes') == 1): ?>
						<span class="votes hasTooltip"
						      title="<?php echo Text::_('MOD_JCOMMENTS_TOP_POSTERS_FIELD_VOTES_LABEL'); ?>">
							<?php if ($item->votes < 0): ?>
								<span class="badge bg-danger rounded-pill">-<?php echo $item->votes; ?></span>
							<?php elseif ($item->votes > 0): ?>
								<span class="badge bg-success rounded-pill">+<?php echo $item->votes; ?></span>
							<?php else: ?>
								<span class="badge bg-secondary rounded-pill"><?php echo $item->votes; ?></span>
							<?php endif; ?>
						</span>
					<?php elseif ($params->get('show_votes') == 2): ?>
						<span class="votes hasTooltip"
							  title="<?php echo Text::_('MOD_JCOMMENTS_TOP_POSTERS_FIELD_VOTES_LABEL'); ?>">
							<span class="badge bg-success rounded-pill">+<?php echo $item->isgood; ?></span><span class="badge bg-danger rounded-pill">-<?php echo $item->ispoor; ?></span>
						</span>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif;
