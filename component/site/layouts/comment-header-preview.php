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

use Joomla\CMS\Language\Text;

/** @var array $displayData */
?>
<div class="row justify-content-start py-1 border-bottom rounded text-bg-primary comment-preview-title">
	<div class="col-12">
		<span class="icon icon-eye pe-2"></span> <?php echo Text::_('COMMENT_PREVIEW_TITLE'); ?>
		<a href="#" class="d-inline-block link-light float-end close-preview" title="<?php echo Text::_('JCLOSE'); ?>">
			<span class="icon icon-cancel"></span>
		</a>
	</div>
</div>
