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
use Joomla\CMS\Router\Route;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useStyle('jcomments.backend_style');
?>
<form action="<?php echo Route::_('index.php?option=com_jcomments&view=import'); ?>" method="post" name="adminForm"
	  id="adminForm">
	<div class="row">
		<div class="col-md-12">
			<div class="j-main-container">
				<?php if (empty($this->items)): ?>
					<div class="alert alert-info">
						<span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
						<?php echo Text::_('A_IMPORT_NO_SOURCES'); ?>
					</div>
				<?php else: ?>
					<table class="adminlist table">
						<thead>
						<tr>
							<th scope="col" class="w-50 d-md-table-cell">
								<?php echo Text::_('A_IMPORT_HEADING_COMPONENT'); ?>
							</th>
							<th scope="col" class="w-20 d-none d-md-table-cell">
								<?php echo Text::_('A_IMPORT_HEADING_AUTHOR'); ?>
							</th>
							<th scope="col" class="w-20 d-none d-md-table-cell">
								<?php echo Text::_('A_IMPORT_HEADING_LICENSE'); ?>
							</th>
							<th scope="col" class="w-5 d-md-table-cell">
								<?php echo Text::_('A_IMPORT_HEADING_COMMENTS'); ?>
							</th>
							<th scope="col" class="w-5 d-md-table-cell">
							</th>
						</tr>
						</thead>
						<tbody>
						<?php foreach ($this->items as $i => $item): ?>
							<tr class="row<?php echo $i % 2; ?>">
								<td class="nowrap">
									<?php echo $this->escape($item->name); ?>
								</td>
								<td class="text-center hidden-phone">
									<a href="<?php echo $item->siteUrl; ?>">
										<?php echo $item->author; ?>
									</a>
								</td>
								<td class="text-center hidden-phone">
									<a href="<?php echo $item->licenseUrl; ?>">
										<?php echo $item->license; ?>
									</a>
								</td>
								<td class="text-center nowrap">
									<?php echo $item->comments; ?>
								</td>
								<td class="text-center nowrap">
									<?php
									echo HTMLHelper::_(
										'jcomments.modal',
										'jcomments-import-' . $i, 'A_IMPORT_BUTTON_IMPORT',
										'index.php?option=com_jcomments&task=import.modal&source=' . $item->code . '&tmpl=component',
										'A_COMMENTS', 'window.location.reload();', 'download', 'btn-micro', 500, 210
									);
									?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
