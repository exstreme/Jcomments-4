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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/** @var Joomla\Component\Jcomments\Site\View\Comments\HtmlView $this */

$wa = $this->document->getWebAssetManager();

// WebAssetManager assets registry not exist in Application until dispatch happen.
$wa->useScript('bootstrap.modal')
	->useScript('bootstrap.collapse');

$locked      = $this->params->get('comments_locked');
$feedUrl     = 'index.php?option=com_jcomments&view=comments&task=rss&object_id=' . $this->objectID
	. '&object_group=' . $this->objectGroup . '&type=rss&format=feed';
$view        = \Joomla\CMS\Factory::getApplication()->input->getWord('view');
?>
<div class="container-fluid px-0 mt-2 comments" id="comments">
	<?php if ($this->params->get('form_position') && $view != 'comments'):
		echo $this->loadTemplate('iframe');
	endif; ?>

	<div class="row comments-list-header">
		<div class="col col-auto h5 me-2"><?php echo Text::_('COMMENTS_LIST'); ?>
			<span class="text-info total-comments"><?php echo $this->totalComments; ?></span>
		</div>
		<div class="col small">
			<?php if (!$this->params->get('load_cached_comments') && !$locked): ?>
				<a href="#" title="<?php echo Text::_('BUTTON_REFRESH'); ?>" class="me-1 refresh-list"
				   onclick="Jcomments.loadComments(<?php echo $this->objectID; ?>, '<?php echo $this->objectGroup; ?>', 0); return false;">
					<span aria-hidden="true" class="fa icon-loop"></span>
				</a>
			<?php endif; ?>

			<?php if ($this->params->get('enable_rss') && !$locked): ?>
				<a href="<?php echo Route::_($feedUrl); ?>"
				   title="<?php echo Text::_('BUTTON_RSS'); ?>" target="_blank">
					<span aria-hidden="true" class="fa icon-rss"></span>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="row mb-2 comments-list-container"
		 data-object-id="<?php echo $this->objectID; ?>"
		 data-object-group="<?php echo $this->objectGroup; ?>"
		 data-list-url="<?php echo Route::_('index.php?option=com_jcomments&view=comments'); ?>"
		 data-load="<?php echo $this->params->get('load_cached_comments') ? 'static' : 'dynamic'; ?>"
		 data-object-url="<?php echo Route::_(Uri::getInstance(), true, 0, true); ?>"
		 data-nav-prefix="<?php echo isset($this->pagination) ? $this->pagination->prefix : ''; ?>"
		 data-template="<?php echo $this->params->get('template_view'); ?>">
		<?php if ($this->params->get('load_cached_comments')):
			echo $this->loadTemplate($this->templateView);
		else: ?>

			<div class="d-flex align-items-center">
				<div class="spinner-border spinner-border-sm text-info" role="status" aria-hidden="true"></div>
				<span class="ms-2"><?php echo Text::_('COMMENTS_LOADING'); ?></span>
			</div>

		<?php endif; ?>
	</div>

	<div class="row comments-list-footer">
		<?php if (!$this->params->get('load_cached_comments') && !$locked): ?>
			<div class="comments-refresh">
				<a href="#" title="<?php echo Text::_('BUTTON_REFRESH'); ?>" class="refresh-list"
				   onclick="Jcomments.loadComments(<?php echo $this->objectID; ?>, '<?php echo $this->objectGroup; ?>', 0); return false;">
					<span aria-hidden="true" class="fa icon-loop me-1"></span><?php echo Text::_('BUTTON_REFRESH'); ?>
				</a>
			</div>
		<?php endif; ?>

		<?php if ($this->params->get('enable_rss') && !$locked): ?>
			<div class="comments-rss">
				<a href="<?php echo Route::_($feedUrl); ?>"
				   title="<?php echo Text::_('BUTTON_RSS'); ?>" target="_blank">
					<span aria-hidden="true" class="fa icon-rss me-1"></span><?php echo Text::_('BUTTON_RSS'); ?>
				</a>
			</div>
		<?php endif; ?>

		<?php if ($this->canSubscribe && !$locked):
			$text = $this->isSubscribed ? Text::_('LINK_UNSUBSCRIBE') : Text::_('LINK_SUBSCRIBE');
			$func = $this->isSubscribed ? 'remove' : 'add';
			$url  = 'index.php?option=com_jcomments&task=subscription.' . $func . '&object_id=' . $this->objectID
				. '&object_group=' . $this->objectGroup . '&return=' . base64_encode(Uri::getInstance());
			?>
			<div class="comments-subscription">
				<a href="<?php echo Route::_($url); ?>" class="cmd-subscribe" title="<?php echo $text; ?>" rel="nofollow">
					<span aria-hidden="true" class="fa icon-mail me-1"></span><?php echo $text; ?>
				</a>
			</div>
		<?php endif; ?>
	</div>

	<?php if (!$this->params->get('form_position') && $view != 'comments'):
		echo $this->loadTemplate('iframe');
	endif; ?>
</div>
