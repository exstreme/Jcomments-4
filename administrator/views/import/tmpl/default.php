<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('jcomments.stylesheet');
HTMLHelper::_('bootstrap.tooltip');
HTMLHelper::_('formbehavior.chosen', 'select');

$containerClass = empty($this->sidebar) ? '' : 'span10';
?>
<form action="<?php echo Route::_('index.php?option=com_jcomments&view=import'); ?>" method="post" name="adminForm"
	  id="adminForm">
	<div id="j-main-container" class="<?php echo $containerClass; ?>">
		<div id="jc">
			<table class="adminlist table table-striped" id="adminlist" cellspacing="1">
				<thead>
				<tr>
					<th width="50%" class="left nowrap">
						<?php echo Text::_('A_IMPORT_HEADING_COMPONENT'); ?>
					</th>
					<th width="20%" class="center hidden-phone">
						<?php echo Text::_('A_IMPORT_HEADING_AUTHOR'); ?>
					</th>
					<th width="20%" class="center nowrap hidden-phone">
						<?php echo Text::_('A_IMPORT_HEADING_LICENSE'); ?>
					</th>
					<th width="5%" class="center nowrap">
						<?php echo Text::_('A_IMPORT_HEADING_COMMENTS'); ?>
					</th>
					<th width="5%" class="center nowrap">
					</th>
				</tr>
				</thead>
				<tbody>
				<?php if (count($this->items)) : ?>
					<?php foreach ($this->items as $i => $item) : ?>
						<tr class="row<?php echo $i % 2; ?>">
							<td class="nowrap has-context">
								<?php echo $this->escape($item->name); ?>
							</td>
							<td class="center hidden-phone">
								<a href="<?php echo $item->siteUrl; ?>">
									<?php echo $item->author; ?>
								</a>
							</td>
							<td class="center hidden-phone">
								<a href="<?php echo $item->licenseUrl; ?>">
									<?php echo $item->license; ?>
								</a>
							</td>
							<td class="center nowrap">
								<?php echo $item->comments; ?>
							</td>
							<td class="center nowrap">
								<?php
								echo HTMLHelper::_('jcomments.modal', 'jcomments-import-' . $i, 'A_IMPORT_BUTTON_IMPORT',
									'index.php?option=com_jcomments&task=import.modal&source=' . $item->code . '&tmpl=component',
									'A_COMMENTS', 'window.location.reload();', 'download', 'btn-micro', 500, 210);
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else: ?>
					<tr>
						<td colspan="5" class="center">
							<?php echo Text::_('A_IMPORT_NO_SOURCES'); ?>
						</td>
					</tr>
				<?php endif; ?>
				</tbody>
			</table>
		</div>

		<input type="hidden" name="task" value=""/>
		<?php echo HTMLHelper::_('form.token'); ?>
	</div>
</form>
