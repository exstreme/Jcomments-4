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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;
use Joomla\Component\Jcomments\Site\Library\Jcomments\JcommentsFactory;

/** @var Joomla\Component\Jcomments\Site\View\User\HtmlView $this */
/** @var Joomla\Component\Jcomments\Site\Model\UserModel::getVotesQuery $this->item */

$wa = $this->document->getWebAssetManager();
$wa->useScript('multiselect')
	->useScript('bootstrap.collapse')
	->useScript('kwood.more')
	->useStyle('jcomments.style');

$acl = JcommentsFactory::getAcl();
?>
<script type="text/javascript">
	(function ($) {
		$(document).ready(function () {
			$('.read-more').more({
				length: 200,
				wordBreak: true,
				moreText: '<?php echo Text::_('JSHOW'); ?>',
				lessText: '<?php echo Text::_('JHIDE'); ?>'
			});
		});
	})(jQuery);
</script>
<div class="container-fluid mt-2">
	<div class="col col-auto h5 me-2"><?php echo Text::_('VOTES_LIST'); ?>
		<span class="text-info ps-2 total-votes"><?php echo $this->total; ?></span>
	</div>
	<div class="col col-auto m-2 stat-votes">
		<span class="text-success me-4"><span class="icon-thumbs-up pe-2" aria-hidden="true"></span><?php echo $this->voteStats['good']; ?></span>
		<span class="text-danger"><span class="icon-thumbs-down pe-2" aria-hidden="true"></span><?php echo $this->voteStats['bad']; ?></span>
	</div>

	<form action="<?php echo Route::_('index.php?option=com_jcomments'); ?>" method="post" name="adminForm"
		  id="adminForm" autocomplete="off">
		<div class="row">
			<div class="col-md-12">
				<div id="j-main-container" class="j-main-container">
					<?php if (empty($this->items)) : ?>
						<div class="alert alert-info">
							<span class="icon-info-circle" aria-hidden="true"></span>
							<span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
							<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
						</div>
					<?php else : ?>

					<table class="table table-hover itemList">
						<thead>
							<tr>
								<td class="w-1">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</td>
								<th scope="col" style="min-width: 100px;">
									<?php echo Text::_('JOBJECT'); ?> / <?php echo Text::_('JARTICLE'); ?>
								</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $item): ?>
							<tr>
								<td class="text-center align-top">
								<?php if ($acl->canVote($item)): ?>
									<?php echo HTMLHelper::_('grid.id', $i, $item->vote_id, false, 'cid', 'cb', $item->object_title); ?>
								<?php endif; ?>
								</td>
								<td>
									<div class="row mb-1">
										<div class="col-9">
											<?php if (empty($item->object_title)): ?>
												<span class="text-warning">
													<span class="icon-exclamation-triangle" aria-hidden="true"></span>
													<?php echo Text::_('OBJECT_NOT_FOUND'); ?>
												</span>
											<?php else: ?>
												<a href="<?php echo Route::_($item->object_link); ?>" target="_blank"
												   class="read-more"><?php echo $this->escape($item->object_title); ?></a>
											<?php endif; ?>
										</div>
										<div class="col-3">
											<span class="small text-muted"><?php echo LayoutHelper::render('joomla.content.language', $item); ?></span>
										</div>
									</div>
									<div class="row h6 bg-light py-2">
										<div class="col-8"><?php echo Text::_('COMMENT_ITEM'); ?></div>
										<div class="col-3"><?php echo Text::_('JDATE'); ?></div>
										<div class="col-1"></div>
									</div>
									<div class="row">
										<div class="col-8 comment-text text-break">
											<span class="read-more">
												<?php
												JcommentsContentHelper::prepareComment($item);
												echo $item->comment;
												?>
											</span>
										</div>
										<div class="col-3">
											<?php echo HTMLHelper::_('date', $item->date, 'DATE_FORMAT_LC6'); ?>
										</div>
										<div class="col-1">
											<?php if ($item->value == 1): ?>
												<span class="link-success icon-thumbs-up" aria-hidden="true" title="<?php echo Text::_('BUTTON_VOTE_GOOD'); ?>"></span>
											<?php elseif ($item->value == -1): ?>
												<span class="link-danger icon-thumbs-down" aria-hidden="true" title="<?php echo Text::_('BUTTON_VOTE_BAD'); ?>"></span>
											<?php endif; ?>
										</div>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
						<?php if ($acl->canVote): ?>
						<tfoot>
							<tr>
								<td colspan="3">
									<input type="hidden" name="task" value="comments.removeVotes">
									<input type="hidden" name="boxchecked" value="0"/>
									<input type="hidden" name="return" value="<?php echo base64_encode(Joomla\CMS\Uri\Uri::getInstance()); ?>">
									<?php echo HTMLHelper::_('form.token'); ?>

									<?php echo LayoutHelper::render(
										'joomla.toolbar.standard',
										array(
											'id'             => 'jc_submit',
											'task'           => 'user.removeVotes',
											'listCheck'      => true,
											'btnClass'       => 'button-remove btn btn-danger btn-sm',
											'htmlAttributes' => 'type="button"',
											'class'          => 'icon-delete me-2',
											'text'           => Text::_('JACTION_DELETE'),
											'message'        => Text::_('BUTTON_VOTES_REMOVE_CONFIRM')
										)
									); ?>
								</td>
							</tr>
						</tfoot>
						<?php endif; ?>
					</table>

					<?php endif; ?>
				</div>
			</div>
		</div>
	</form>

	<form action="<?php echo htmlspecialchars(Uri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">
		<div class="w-100">
			<?php if ($this->pagination->total > 5): ?>
				<div class="btn-group">
					<label for="limit" class="visually-hidden">
						<?php echo Text::_('JGLOBAL_DISPLAY_NUM'); ?>
					</label>
					<?php echo $this->pagination->getLimitBox(); ?>
				</div>
			<?php endif; ?>
			<span class="ms-2 float-end"><?php echo $this->pagination->getResultsCounter(); ?></span>
		</div>
		<div class="w-100">
			<p class="float-end pt-3 pe-2">
				<?php echo $this->pagination->getPagesCounter(); ?>
			</p>
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
	</form>
</div>
