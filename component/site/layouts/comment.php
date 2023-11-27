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

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;

/**
 * @var array                     $displayData
 * @var object                    $comment
 * @var \Joomla\Registry\Registry $commentData
 */

$comment        = $displayData['comment'];
$params         = $displayData['params'];
$app            = Factory::getApplication();
$user           = $app->getIdentity();
$commentData    = $comment->commentData;
$return         = base64_encode(Joomla\CMS\Uri\Uri::getInstance());
$editUrl        = 'index.php?option=com_jcomments&view=form&tmpl=component&object_id=' . $comment->object_id
	. '&object_group=' . $comment->object_group . '&return=' . $return;
$reportUrl      = 'index.php?option=com_jcomments&view=form&tmpl=component&object_id=' . $comment->object_id
	. '&object_group=' . $comment->object_group . '&comment_id=' . $comment->id . '&layout=report';
$groupClass     = '';
$usernameClass  = '';
$boxClass       = '';
$userGroup      = '';
$publishedClass = !$comment->published ? ' bg-light text-muted' : '';
$comment->date  = $comment->date ?? 'now';

// True by default
$comment->bottomPanel = !isset($comment->bottomPanel);

if (is_object($comment->labels) && $comment->labels->enable == 1 && $comment->deleted == 0)
{
	if ($comment->labels->display_usergroup > '')
	{
		if (strpos($comment->labels->display_usergroup, 'id:') !== false)
		{
			$userGroups = \Joomla\CMS\Helper\UserGroupsHelper::getInstance()->getAll();
			$userGroup  = explode(':', $comment->labels->display_usergroup);
			$userGroup  = trim($userGroup[1]);
			$userGroup  = $userGroups[$userGroup]->title;
		}
		else
		{
			$userGroup = $this->escape(trim($comment->labels->display_usergroup));
		}
	}

	$groupClass    = $comment->labels->group_css . ' font-monospace';
	$usernameClass = $comment->labels->label_css;
	$boxClass      = ' ' . $comment->labels->box_css;
}

Text::script('BUTTON_DELETE_CONIRM');
?>
<div class="comment border rounded mb-2 p-1<?php echo $boxClass; ?><?php echo $publishedClass; ?>"
	 id="comment-<?php echo $comment->id; ?>" data-id="<?php echo $comment->id; ?>">
	<div class="row mb-2 comment-header">
		<?php if ($commentData->get('showAvatar')): ?>

		<div class="col-auto comment-avatar-container">
			<div class="comment-avatar">
				<?php
				/** @note The profileLink and profileLinkTarget comming from avatar plugin and set to default
				 *        in JcommentsContentHelper::prepareComment
				 */
				if ($comment->profileLink > ''): ?>
					<a href="<?php echo $comment->profileLink; ?>" target="<?php echo $comment->profileLinkTarget; ?>">
						<img src="<?php echo $comment->avatar; ?>" class="object-fit-scale" alt="">
					</a>
				<?php else: ?>
					<img src="<?php echo $comment->avatar; ?>" class="object-fit-scale" alt="">
				<?php endif; ?>
			</div>
		</div>

		<?php endif; ?>

		<div class="col comment-header-info">
			<?php if (($commentData->get('showTitle') > 0) && ($comment->title != '')): ?>
				<div class="row row-cols-auto">
					<div class="comment-title text-muted text-break w-100"><?php echo $this->escape($comment->title); ?></div>
				</div>
			<?php endif; ?>

			<div class="row row-cols-auto text-muted comment-info">
				<?php if (isset($comment->permaLink)): ?>
				<div class="col permalink">
					<a href="<?php echo $comment->permaLink; ?>" onclick="Jcomments.scrollToByHash(this.href);return false;"
					   class="link-secondary comment-anchor" title="<?php echo Text::_('BUTTON_PERMALINK'); ?>">
						<span class="fa fa-hashtag" aria-hidden="true"></span> <?php echo $commentData->get('number'); ?>
					</a>
				</div>
				<?php endif; ?>

				<?php if (!empty($comment->author)): ?>
					<div class="col createdby" itemprop="author" itemscope itemtype="https://schema.org/Person">
						<?php
						if ($comment->user_blocked == 1)
						{
							$usernameBlocked = ' text-decoration-line-through';
							$usernameTitle = Text::_('NOTIFICATION_COMMENT_NAME') . '. ' . Text::_('ERROR_USER_BLOCKED');
						}
						else
						{
							$usernameBlocked = '';
							$usernameTitle = Text::_('NOTIFICATION_COMMENT_NAME');
						}

						if ($commentData->get('showHomepage') && !empty($comment->homepage)): ?>
							<a href="<?php echo $comment->homepage; ?>" rel="nofollow" itemprop="url" target="_blank"
							   class="<?php echo $usernameClass; ?> username"
							   title="<?php echo $usernameTitle; ?>">
								<span class="fa icon-user" aria-hidden="true"></span> <span itemprop="name"
									  class="<?php echo $usernameBlocked; ?>"><?php echo $comment->author; ?></span>
							</a>
						<?php else: ?>
							<span class="<?php echo $usernameClass; ?> username"
								  title="<?php echo $usernameTitle; ?>">
								<span class="fa icon-user" aria-hidden="true"></span> <span itemprop="name"
									  class="<?php echo $usernameBlocked; ?>"><?php echo $comment->author; ?></span>
							</span>
						<?php endif; ?>

						<?php if ($groupClass > '' && $comment->deleted == 0): ?>
							<span class="<?php echo $comment->labels->group_css; ?> usergroup">
								<span itemprop="jobTitle"><?php echo $userGroup; ?></span>
							</span>
						<?php endif; ?>

						<?php if (($commentData->get('showEmail') > 0) && (!empty($comment->email))): ?>
							<span class="email"><?php echo $comment->email; ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<div class="col published">
					<span class="fa icon-calendar" aria-hidden="true"></span>
					<time datetime="<?php echo HTMLHelper::_('date', $comment->date, 'c'); ?>" itemprop="datePublished">
						<?php echo HTMLHelper::_('date', $comment->date, 'DATE_FORMAT_LC5'); ?>
					</time>
				</div>

				<?php if ($comment->parent > 0): ?>
					<div class="col parent-comment-link">
						<a href="<?php echo $comment->parentLink; ?>" title="<?php echo Text::_('GOTO_PREV_COMMENT_ANCHOR'); ?>">
							<span class="fa icon-arrow-up-4" aria-hidden="true"></span>
						</a>
					</div>
				<?php endif; ?>

				<?php if ($commentData->get('showVote')): ?>
					<div class="col-auto ms-auto comments-vote">
						<div class="row" id="comment-vote-holder-<?php echo $comment->id; ?>">
						<?php if ($comment->userPanel->get('button.vote')): ?>

							<div class="col vote-up">
								<a href="#" class="toolbar-button-vote link-success"
								   title="<?php echo Text::_('BUTTON_VOTE_GOOD'); ?>"
								   data-url="<?php echo Route::_('index.php?option=com_jcomments&task=comment.voteUp', true, 0, true); ?>">
									<span class="icon-thumbs-up" aria-hidden="true"></span>
								</a>
							</div>
							<div class="col vote-down">
								<a href="#" class="toolbar-button-vote link-danger"
								   title="<?php echo Text::_('BUTTON_VOTE_BAD'); ?>"
								   data-url="<?php echo Route::_('index.php?option=com_jcomments&task=comment.voteDown', true, 0, true); ?>">
									<span class="icon-thumbs-down" aria-hidden="true"></span>
								</a>
							</div>

						<?php endif; ?>

							<div class="col vote-result">
								<?php echo LayoutHelper::render('comment-vote-value', $displayData, '', array('component' => 'com_jcomments')); ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<div class="row py-2 text-break w-100 comment-body-container" id="comment-body-<?php echo $comment->id; ?>">
		<div class="comment-text<?php echo $comment->deleted == 1 ? ' text-secondary' : ''; ?>"><?php echo $comment->comment; ?></div>
	</div>

	<?php if ($comment->bottomPanel && $comment->deleted == 0): ?>
	<div class="row mx-1 pt-1 border-top comment-panels">
		<?php if ($comment->adminPanel->get('show')): ?>
			<div class="col-auto ps-0 admin-panel">

				<?php if ($comment->adminPanel->get('button.edit')): ?>
					<a class="toolbar-button-edit" href="#" title="<?php echo Text::_('JACTION_EDIT'); ?>"
					   data-edit-url="<?php echo Route::_($editUrl . '&comment_id=' . $comment->id, true, 0, true); ?>"
					   onclick="Jcomments.showEditForm(this);return false;">
						<span class="icon-edit" aria-hidden="true"></span>
					</a>
				<?php endif; ?>

				<?php if ($comment->adminPanel->get('button.publish')):
					if ($comment->published)
					{
						$stateTask = 'unpublish';
						$stateClass = 'icon-' . $stateTask . ' link-secondary';
					}
					else
					{
						$stateTask = 'publish';
						$stateClass = 'icon-' . $stateTask . ' link-success';
					} ?>
					<a class="toolbar-button-state" href="#" title="<?php echo Text::_(strtoupper($stateTask)); ?>"
					   data-url="<?php echo Route::_('index.php?option=com_jcomments&task=comment.' . $stateTask, true, 0, true); ?>">
						<span class="<?php echo $stateClass; ?>" aria-hidden="true"></span>
					</a>
				<?php endif; ?>

				<?php if ($comment->adminPanel->get('button.delete')): ?>
					<a class="toolbar-button-delete link-danger" href="#"
					   data-url="<?php echo Route::_('index.php?option=com_jcomments&task=comment.delete', true, 0, true); ?>"
					   title="<?php echo Text::_('JACTION_DELETE'); ?>">
						<span class="icon-trash" aria-hidden="true"></span>
					</a>
				<?php endif; ?>

				<?php if ($comment->adminPanel->get('button.ip')):
					$ripeUrl = 'https://apps.db.ripe.net/db-web-ui/query?searchtext=' . $comment->ip;
					$errorText = Text::sprintf('ERROR_NEWWINDOW_BLOCKED', $ripeUrl); ?>
					<a class="toolbar-button-ip<?php echo $comment->banned == 1 ? ' link-secondary text-decoration-line-through' : ''; ?>"
					   href="<?php echo $ripeUrl; ?>" target="_blank" title="<?php echo Text::_('BUTTON_IP') . ' ' . $comment->ip; ?>"
					   onclick="Jcomments.openWindow('<?php echo $ripeUrl; ?>', '<?php echo $errorText; ?>');return false;">
						<?php echo $comment->ip; ?>
					</a>
				<?php endif; ?>

				<?php if ($comment->adminPanel->get('button.ban') && !$comment->banned): ?>
					<a href="#" data-url="<?php echo Route::_('index.php?option=com_jcomments&task=comment.banIP', true, 0, true); ?>"
					   class="toolbar-button-ban" title="<?php echo Text::_('BUTTON_BANIP'); ?>">
						<span class="fa fa-ban" aria-hidden="true"></span>
					</a>
				<?php endif; ?>

			</div>
		<?php endif; ?>

		<?php if ($comment->userPanel->get('button.quote')
			|| $comment->userPanel->get('button.reply')
			|| $comment->userPanel->get('button.report')):

			$userPanelLinkClass = !$comment->published ? ' pe-none' : '';
			$userPanelLinkAriaAttr = !$comment->published ? ' tabindex="-1" aria-disabled="true"' : '' ?>
			<div class="col pe-0 text-end user-panel">
				<?php if ($comment->userPanel->get('button.reply') && $app->input->getCmd('task') != 'show'): ?>
					<a href="#" class="toolbar-button-reply<?php echo $userPanelLinkClass; ?>"<?php echo $userPanelLinkAriaAttr; ?>
					   onclick="Jcomments.showAddForm(true);return false;">
						<?php echo Text::_('BUTTON_REPLY'); ?>
					</a><?php if ($comment->userPanel->get('button.quote') || ($comment->userPanel->get('button.report') && $comment->userid != $user->get('id'))): ?> &vert;<?php endif; ?>
				<?php endif; ?>

				<?php if ($comment->userPanel->get('button.quote')): ?>
					<a href="#" class="toolbar-button-reply-quote<?php echo $userPanelLinkClass; ?>"<?php echo $userPanelLinkAriaAttr; ?>
					   data-edit-url="<?php echo Route::_($editUrl . '&parent=' . $comment->id . '&quote=1', true, 0, true); ?>"
					   onclick="Jcomments.showEditForm(this);return false;">
						<?php echo Text::_('BUTTON_REPLY_WITH_QUOTE'); ?>
					</a>
				<?php endif; ?>

				<?php if ($comment->userPanel->get('button.report') && $comment->userid != $user->get('id')): ?>
					<?php if ($comment->userPanel->get('button.quote') && $comment->userPanel->get('button.reply')): ?> &vert;<?php endif; ?>
					<a href="#" data-url="<?php echo Route::_($reportUrl . '&return=' . base64_encode($reportUrl), true, 0, true); ?>"
					   class="toolbar-button-report link-warning" title="<?php echo Text::_('BUTTON_REPORT'); ?>">
						<span class="fa icon-exclamation-triangle" aria-hidden="true"></span>
					</a>
				<?php endif; ?>

				<?php if (isset($comment->children) && $comment->children != 0): ?>
					<?php if ($params->get('template_view') == 'tree'): ?> &vert;<?php endif; ?>
					<a href="#" title="<?php echo Text::_('BUTTON_HIDE'); ?>" class="toolbar-button-child-toggle link-secondary"
					   data-title-hide="<?php echo Text::_('BUTTON_HIDE'); ?>"
					   data-title-show="<?php echo Text::_('BUTTON_SHOW'); ?>">
						<span class="icon-chevron-up" aria-hidden="true"></span>
					</a>
				<?php endif; ?>
			</div>
		<?php else: ?>
			<?php if ($params->get('template_view') == 'tree'): ?>
				<div class="col pe-0 text-end user-panel">
					<a href="#" title="<?php echo Text::_('BUTTON_HIDE'); ?>" class="toolbar-button-child-toggle link-secondary"
					   data-title-hide="<?php echo Text::_('BUTTON_HIDE'); ?>"
					   data-title-show="<?php echo Text::_('BUTTON_SHOW'); ?>">
						<span class="icon-chevron-up" aria-hidden="true"></span>
					</a>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php endif; ?>
</div>

<?php
if ($comment->bottomPanel)
{
	// This will be loaded only once.
	echo HTMLHelper::_(
		'bootstrap.renderModal',
		'reportModal',
		array(
			'title'  => Text::_('REPORT_TO_ADMINISTRATOR'),
			'backdrop' => 'static',
			'height' => '100%',
			'width'  => '100%',
			'footer' => ''
		),
		'<iframe width="100%" onload="Jcomments.iframeHeight(this);" style="overflow: hidden;" class="reportFormFrame"'
		. ' id="reportFormFrame" name="' . bin2hex(random_bytes(4)) . '"></iframe>'
	);
}
