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
 * @note          This template used only when edit single comment. Not for an edit form in comment list.
 *
 **/

defined('_JEXEC') or die;

use Joomla\CMS\Layout\LayoutHelper;

/** @var Joomla\Component\Jcomments\Site\View\Form\HtmlView $this */

$this->document->getWebAssetManager()
	->useScript('jcomments.core');

echo LayoutHelper::render(
	'form',
	array(
		// A viewObject property is required for proper loading of 'params' layout.
		'viewObject'    => &$this,
		'params'        => $this->params,
		'displayForm'   => $this->displayForm,
		'canViewForm'   => $this->canViewForm,
		'canComment'    => $this->canComment,
		'returnPage'    => $this->returnPage,
		'form'          => $this->form,
		'item'          => $this->item
	),
	'',
	array('component' => 'com_jcomments')
);
