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
?>
<div class="list-group list-group-flush <?php echo $params->get('moduleclass_sfx'); ?>">
	<?php foreach ($list as $item): ?>
		<div class="list-group-item list-group-item-action">
			<?php if ($params->get('show_object_title')): ?>

				<div class="d-flex justify-content-between">
					<h<?php echo $itemHeading; ?> class="mb-1 w-75 text-truncate">
						<?php if ($params->get('link_object_title', 1) && $item->object_link != ''): ?>
							<a href="<?php echo $item->object_link;?>"><?php echo $item->displayObjectTitle; ?></a>
						<?php else: ?>
							<?php echo $item->displayObjectTitle; ?>
						<?php endif; ?>
					</h<?php echo $itemHeading; ?>>

					<?php if ($params->get('show_comment_date')): ?>
						<small>
							<?php if ($params->get('date_type') == 'absolute'): ?>
								<span class="icon-calendar icon-fw" aria-hidden="true"></span>
							<?php endif; ?>
							<?php echo $item->displayDate; ?>
						</small>
					<?php endif; ?>
				</div>
				<p class="mb-1">
					<?php if ($params->get('show_comment_title') && $item->displayCommentTitle): ?>
						<span class="d-inline-block w-75 text-truncate"><strong><?php echo $item->displayCommentTitle; ?></strong></span><br>
					<?php endif; ?>

					<?php echo $item->displayCommentText; ?>

					<?php if ($params->get('readmore')): ?>
						<span class="jcomments-latest-readmore">
							<a href="<?php echo $item->displayCommentLink; ?>"><?php echo $item->readmoreText; ?></a>
						</span>
					<?php endif; ?>
				</p>

			<?php else: ?>

				<?php if ($params->get('show_comment_title') && $item->displayCommentTitle): ?>

					<div class="d-flex justify-content-between">
						<span class="mb-1 w-75 text-truncate"><strong><?php echo $item->displayCommentTitle; ?></strong></span>

						<?php if ($params->get('show_comment_date')): ?>
							<small>
								<?php if ($params->get('date_type') == 'absolute'): ?>
									<span class="icon-calendar icon-fw" aria-hidden="true"></span>
								<?php endif; ?>
								<?php echo $item->displayDate; ?>
							</small>
						<?php endif; ?>
					</div>
					<p class="mb-1">
						<?php echo $item->displayCommentText; ?>

						<?php if ($params->get('readmore')): ?>
							<span class="jcomments-latest-readmore">
								<a href="<?php echo $item->displayCommentLink; ?>"><?php echo $item->readmoreText; ?></a>
							</span>
						<?php endif; ?>
					</p>

				<?php else: ?>

					<div class="d-flex justify-content-between">
						<p class="mb-1">
							<?php echo $item->displayCommentText; ?>

							<?php if ($params->get('readmore')): ?>
								<span class="jcomments-latest-readmore">
								<a href="<?php echo $item->displayCommentLink; ?>"><?php echo $item->readmoreText; ?></a>
							</span>
							<?php endif; ?>
						</p>
					</div>

				<?php endif; ?>
			<?php endif; ?>

			<?php if ($params->get('show_comment_date') && (!$params->get('show_object_title') && !$params->get('show_comment_title'))): ?>
				<small>
					<?php if ($params->get('date_type') == 'absolute'): ?>
						<span class="icon-calendar icon-fw" aria-hidden="true"></span>
					<?php endif; ?>
					<?php echo $item->displayDate; ?>
				</small>
				<?php if ($params->get('show_comment_author')): ?><br><?php endif; ?>
			<?php endif; ?>

			<?php if ($params->get('show_comment_author')):
				if ($params->get('show_avatar')):
					if (!empty($item->profileLink)):
						$author = '<span class="avatar-img">
							<a href="' . $item->profileLink . '" target="' . $item->profileLinkTarget . '" style="text-decoration: none;">
								<img src="' . $item->avatar . '" width="24" height="24" alt="">
							</a>
						</span> ' . $item->displayAuthorName;
					else:
						$author = '<span class="avatar-img"><img src="' . $item->avatar . '" width="24" height="24" alt=""></span> ' . $item->displayAuthorName;
					endif;
				else:
					$author = $item->displayAuthorName;
				endif;
				?>
				<small class="text-secondary createdby author">
					<?php echo Text::sprintf('COM_CONTENT_WRITTEN_BY', $author); ?>
				</small>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</div>
