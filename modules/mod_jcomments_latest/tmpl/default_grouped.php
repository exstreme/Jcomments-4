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

use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

/** @var object $params */
/** @var object $list */
/** @var integer $itemHeading */
/** @var object $module */
?>
<div class="list-group list-group-flush <?php echo $params->get('moduleclass_sfx'); ?>">
	<?php foreach ($list as $groupName => $group): ?>
		<div class="list-group-item list-group-item-action">
			<div class="d-flex justify-content-between">
				<h<?php echo $itemHeading; ?> class="mb-1 w-75 text-truncate">
					<?php if ($params->get('link_object_title', 1) && $group[0]->object_link != ''): ?>
						<a href="<?php echo $group[0]->object_link;?>"><?php echo $groupName; ?></a>
					<?php else: ?>
						<?php echo $groupName; ?>
					<?php endif; ?>
				</h<?php echo $itemHeading; ?>>
			</div>
			<div class="mb-1">
				<div class="list-group list-group-flush">
					<?php foreach ($group as $key => $item):
						$ariaDescribed = 'latest-comments-' . $module->id . '-' . $key; ?>

						<div class="list-group-item">
							<?php if ($params->get('show_comment_title') && $item->displayCommentTitle): ?>
								<div class="d-flex w-100 justify-content-between">
									<h6 class="mb-1 w-75 text-truncate"><?php echo $item->displayCommentTitle; ?></h6>

									<?php if ($params->get('show_comment_date')): ?>
										<small>
											<?php if ($params->get('date_type') == 'absolute'): ?>
												<span class="icon-calendar icon-fw" aria-hidden="true"></span>
											<?php endif; ?>
											<?php echo $item->displayDate; ?>
										</small>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<p class="mb-1">
								<?php echo $item->displayCommentText; ?>

								<?php if ($params->get('readmore')) : ?>
									<span class="jcomments-latest-readmore">
									<a href="<?php echo $item->displayCommentLink; ?>"><?php echo $item->readmoreText; ?></a>
								</span>
								<?php endif; ?>
							</p>

							<?php if (!$params->get('show_comment_title') && $params->get('show_comment_date')): ?>
								<small>
									<?php if ($params->get('date_type') == 'absolute'): ?>
										<span class="icon-calendar icon-fw" aria-hidden="true"></span>
									<?php endif; ?>
									<?php echo $item->displayDate; ?>
								</small>
								<?php if ($params->get('show_comment_author')): ?><br><?php endif; ?>
							<?php endif; ?>

							<?php if ($params->get('show_comment_author')): ?>
								<span class="text-secondary small">
									<?php echo Text::sprintf('COM_CONTENT_WRITTEN_BY', ''); ?>
								</span>

								<?php if ($params->get('show_avatar')):
									if (!empty($item->profileLink)): ?>

										<span class="avatar-img">
											<a href="<?php echo $item->profileLink; ?>"
											   target="<?php echo $item->profileLinkTarget; ?>"
											   style="text-decoration: none;">
												<img src="<?php echo $item->avatar; ?>" width="24" height="24" alt="">
											</a>
										</span>
									<?php else: ?>
										<span class="avatar-img">
											<img src="<?php echo $item->avatar; ?>" width="24" height="24" alt="">
										</span>
									<?php endif; ?>

									<small class="text-secondary createdby author">
										<?php echo $item->displayAuthorName; ?>
									</small>

								<?php else: ?>
									<small class="text-secondary createdby author">
										<?php echo $item->displayAuthorName; ?>
									</small>
								<?php endif; ?>
							<?php endif; ?>
						</div>

					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>
