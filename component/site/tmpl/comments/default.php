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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var $this Joomla\Component\Jcomments\Site\View\Comments\HtmlView */

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->document->getWebAssetManager();
$wa->useScript('jquery')
	->registerAndUseScript('com_wrapper.iframe', 'com_wrapper/iframe-height.min.js', [], ['defer' => true]);
$input = Factory::getApplication()->input;
$locked = $this->params->get('comments_locked');

if ($this->params->get('form_position'))
{
	echo $this->loadTemplate('iframe');
}
?>
<div class="comments-list-header">
	<h6 class="float-start me-2"><?php echo Text::_('COMMENTS_LIST_HEADER'); ?></h6>
	<div class="small">
		<?php if (!$locked): ?>
			<a href="#" title="<?php echo Text::_('BUTTON_REFRESH'); ?>"
			   onclick="jcomments.showPage(<?php echo $this->objectID; ?>, '<?php echo $this->objectGroup; ?>', 0); return false;">
				<span aria-hidden="true" class="icon-loop icon-fw"></span></a>
		<?php endif; ?>

		<?php if ($this->params->get('enable_rss') && !$locked): ?>
			<a href="<?php echo Route::_('index.php?option=com_jcomments&view=comments&task=rss&object_id=' . $this->objectID . '&object_group=' . $this->objectGroup . '&type=rss&format=feed', false); ?>"
			   title="<?php echo Text::_('BUTTON_RSS'); ?>" target="_blank">
				<span aria-hidden="true" class="icon-rss icon-fw"></span>
			</a>
		<?php endif; ?>
	</div>
</div>
<div class="clearfix"></div>

<div id="comments" class="mb-2">
	<div class="d-flex align-items-center">
		<div class="spinner-border spinner-border-sm text-info" role="status" aria-hidden="true"></div>
		<span class="ms-2">Loading comments...</span>
	</div>
</div>

<div class="comments-list-footer">
	<?php if (!$locked): ?>
		<div class="comments-refresh">
			<a href="#" title="<?php echo Text::_('BUTTON_REFRESH'); ?>"
			   onclick="jcomments.showPage(<?php echo $this->objectID; ?>, '<?php echo $this->objectGroup; ?>', 0); return false;">
				<span aria-hidden="true" class="icon-loop icon-fw me-1"></span><?php echo Text::_('BUTTON_REFRESH'); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php if ($this->params->get('enable_rss') && !$locked): ?>
		<div class="comments-rss">
			<a href="<?php echo Route::_('index.php?option=com_jcomments&view=comments&task=rss&object_id=' . $this->objectID . '&object_group=' . $this->objectGroup . '&type=rss&format=feed'); ?>"
			   title="<?php echo Text::_('BUTTON_RSS'); ?>" target="_blank">
				<span aria-hidden="true" class="icon-rss icon-fw me-1"></span><?php echo Text::_('BUTTON_RSS'); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php if ($this->canSubscribe && !$locked):
		$text = $this->isSubscribed ? Text::_('BUTTON_UNSUBSCRIBE') : Text::_('BUTTON_SUBSCRIBE');
		$func = $this->isSubscribed ? 'remove' : 'add';
		$url  = 'index.php?option=com_jcomments&task=subscription.' . $func . '&object_id=' . $this->objectID
			. '&object_group=' . $this->objectGroup . '&return=' . base64_encode(Uri::getInstance());
		?>
		<div class="comments-subscription">
			<a href="<?php echo Route::_($url); ?>" class="cmd-subscribe" title="<?php echo $text; ?>" rel="nofollow">
				<span aria-hidden="true" class="icon-mail icon-fw me-1"></span><?php echo $text; ?>
			</a>
		</div>
	<?php endif; ?>
</div>

<?php if (!$this->params->get('form_position'))
{
	echo $this->loadTemplate('iframe');
}
