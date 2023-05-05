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
use Joomla\Component\Jcomments\Site\Helper\ContentHelper as JcommentsContentHelper;

/** @var Joomla\Component\Jcomments\Site\View\User\HtmlView $this */

$wa = $this->document->getWebAssetManager();
$wa->useScript('kwood.more')
	->useStyle('jcomments.style');
?>
<script type="text/javascript">
	(function ($) {
		$(document).ready(function () {
			$('.read-more').more({
				length: 80,
				wordBreak: true,
				moreText: '<?php echo Text::_('JSHOW'); ?>',
				lessText: '<?php echo Text::_('JHIDE'); ?>'
			});
		});
	})(jQuery);
</script>
<div class="container-fluid mt-2">
	<div class="h6"><?php echo Text::_('COMMENTS_LIST'); ?></div>

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
								<th scope="col" style="min-width: 100px;">
									<?php echo Text::_('JOBJECT'); ?> / <?php echo Text::_('JARTICLE'); ?>
								</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $item): ?>
							<tr>
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
												   class="read-more"><?php echo $item->object_title; ?></a>
											<?php endif; ?>
										</div>
										<div class="col-3">
											<span class="small text-muted"><?php echo LayoutHelper::render('joomla.content.language', $item); ?></span>
										</div>
									</div>
									<div class="row h6 bg-secondary bg-opacity-10 py-2">
										<div class="col-9">
											<a href="<?php echo JcommentsContentHelper::getPermalink($item); ?>"
											   target="_blank"><?php echo Text::_('COMMENT_ITEM'); ?></a>
										</div>
										<div class="col-3"><?php echo Text::_('JDATE'); ?></div>
									</div>
									<div class="row">
										<div class="col-9">
											<span class="read-more">
												<?php JcommentsContentHelper::prepareComment($item);
												echo $item->comment;
												?>
											</span>
										</div>
										<div class="col-3">
											<?php echo HTMLHelper::_('date', $item->date, 'DATE_FORMAT_LC6'); ?>
										</div>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<?php endif; ?>
				</div>
			</div>
		</div>
	</form>

	<?php if ($this->pagination->pagesTotal > 1): ?>
		<div class="w-100">
			<p class="float-end pt-3 pe-2">
				<?php echo $this->pagination->getPagesCounter(); ?>
			</p>
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
	<?php endif; ?>
</div>
