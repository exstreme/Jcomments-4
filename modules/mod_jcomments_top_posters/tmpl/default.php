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

use Joomla\CMS\Language\Text;

/** @var object $params */
/** @var object $module */

if (!empty($list)): ?>
	<ul class="jcomments-top-posters list-group list-group-flush <?php echo $params->get('moduleclass_sfx'); ?>">
		<?php foreach ($list as $key => $item):
			$ariaDescribed = 'top-posters-' . $module->id . '-' . $key;
			?>
			<li class="list-group-item d-flex justify-content-between align-items-center" style="padding-left: 0; padding-right: 0;">
				<div class="user-name start-0 text-truncate">
					<?php if ($params->get('show_avatar')):
						if (!empty($item->profileLink)): ?>
							<span class="avatar-img">
								<a href="<?php echo $item->profileLink; ?>" target="<?php echo $item->profileLinkTarget; ?>"
								   style="text-decoration: none;">
									<img src="<?php echo $item->avatar; ?>" width="24" height="24" alt="">
								</a>
							</span>
						<?php else: ?>
							<span class="avatar-img">
								<img src="<?php echo $item->avatar; ?>" width="24" height="24" alt="">
							</span>
						<?php endif; ?>
					<?php endif; ?>

					<?php echo $item->displayAuthorName; ?>
				</div>
				<div class="stats end-0">
					<?php if ($params->get('show_comments_count')): ?>
						<span class="badge bg-primary rounded-pill" aria-describedby="<?php echo $ariaDescribed; ?>-count">
							<?php echo $item->commentsCount; ?>
						</span>
						<div role="tooltip" id="<?php echo $ariaDescribed; ?>-count">
							<?php echo Text::_('MOD_JCOMMENTS_TOP_POSTERS_FIELD_COMMENTS_COUNT_LABEL'); ?>
						</div>
					<?php endif; ?>

					<?php if ($params->get('show_votes') == 1): ?>
						<span class="votes align-middle" aria-describedby="<?php echo $ariaDescribed; ?>-votes">
							<?php if ($item->votes < 0): ?>
								<span class="text-danger">-<?php echo $item->votes; ?></span>
							<?php elseif ($item->votes > 0): ?>
								<span class="text-success">+<?php echo $item->votes; ?></span>
							<?php else: ?>
								<span class="text-secondary"><?php echo $item->votes; ?></span>
							<?php endif; ?>
						</span>
						<div role="tooltip" id="<?php echo $ariaDescribed; ?>-votes">
							<?php echo Text::_('MOD_JCOMMENTS_TOP_POSTERS_FIELD_VOTES_LABEL'); ?>
						</div>
					<?php elseif ($params->get('show_votes') == 2): ?>
						<span class="votes align-middle" aria-describedby="<?php echo $ariaDescribed; ?>-votes">
							<span class="text-success">+<?php echo $item->isgood; ?></span> | <span class="text-danger">-<?php echo $item->ispoor; ?></span>
						</span>
						<div role="tooltip" id="<?php echo $ariaDescribed; ?>-votes">
							<?php echo Text::_('MOD_JCOMMENTS_TOP_POSTERS_FIELD_VOTES_LABEL'); ?>
						</div>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
<?php endif;
