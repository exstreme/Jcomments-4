<?php
/**
 * JComments - Joomla Comment System
 *
 * @version 4.0
 * @package JComments
 * @author Sergey M. Litvinov (smart@joomlatune.ru) & exstreme (info@protectyoursite.ru) & Vladimir Globulopolis
 * @copyright (C) 2006-2022 by Sergey M. Litvinov (http://www.joomlatune.ru) & exstreme (https://protectyoursite.ru) & Vladimir Globulopolis (https://xn--80aeqbhthr9b.com/ru/)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die;

class JCommentsViewObjects extends JCommentsViewLegacy
{
	function display($tpl = null)
	{
		if ($this->getLayout() == 'modal') {
			$this->url = str_replace('/administrator', '', JRoute::_('index.php?option=com_jcomments&task=refreshObjectsAjax&tmpl=component'));
			$this->hash = md5(JFactory::getApplication('administrator')->getCfg('secret'));

			JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
			JHtml::_('jcomments.stylesheet');
			JHtml::_('jcomments.jquery');

			$document = JFactory::getDocument();
			$document->addScript(JURI::root(true) . '/administrator/components/com_jcomments/assets/js/jcomments.progressbar.js');
			$document->addScript(JURI::root(true) . '/administrator/components/com_jcomments/assets/js/jcomments.objects.js?v=5');

			parent::display($tpl);

			return;
		}

		parent::display($tpl);
	}
}