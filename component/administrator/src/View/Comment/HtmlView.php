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

namespace Joomla\Component\Jcomments\Administrator\View\Comment;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\Content\Administrator\Helper\ContentHelper;

class HtmlView extends BaseHtmlView
{
	protected $item;
	protected $reports;
	protected $params;

	/**
	 * @var    \Joomla\CMS\Form\Form
	 * @since  4.1
	 */
	protected $form;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0
	 */
	public function display($tpl = null)
	{
		$this->item    = $this->get('Item');
		$this->reports = $this->get('Reports');
		$this->form    = $this->get('Form');
		$this->params  = ComponentHelper::getParams('com_jcomments');

		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   4.0
	 *
	 * @throws  \Exception
	 */
	protected function addToolbar()
	{
		$app        = Factory::getApplication();
		$userId     = $app->getIdentity()->get('id');
		$canDo      = ContentHelper::getActions('com_jcomments', 'component');
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);
		$toolbar    = Toolbar::getInstance();

		$app->input->set('hidemainmenu', 1);
		ToolbarHelper::title(Text::_('A_COMMENT_EDIT'), 'comment');

		if (!$checkedOut && $canDo->get('core.edit'))
		{
			ToolbarHelper::apply('comment.apply');
			ToolbarHelper::save('comment.save');
		}

		if (!empty($this->item->language) && $this->item->language !== '*' && Multilanguage::isEnabled())
		{
			$linkLang = '&lang=' . $this->item->language;
		}
		else
		{
			$linkLang = '';
		}

		$toolbar->preview(Route::link('site', 'index.php?option=com_jcomments&task=comment.show' . $linkLang . '&id=' . $this->item->id))
			->bodyHeight(80)
			->modalWidth(90);

		ToolbarHelper::cancel('comment.cancel', 'JTOOLBAR_CLOSE');
	}
}
