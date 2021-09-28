<?php
/**
 * JComments - Joomla Comment System
 *
 * @version       3.0
 * @package       JComments
 * @author        Sergey M. Litvinov (smart@joomlatune.ru)
 * @copyright (C) 2006-2013 by Sergey M. Litvinov (http://www.joomlatune.ru)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;
?>
<?php if (count($this->groups)): ?>
    <div id="permissions-sliders" class="tabbable tabs-left">
        <?php echo HTMLHelper::_('bootstrap.startTabSet', 'permissions',array('active' => 'Public')); ?>
        <?php foreach ($this->groups as $group):
            $form = $this->permissionForms[$group->value]; ?>
            <?php echo HTMLHelper::_('bootstrap.addTab', 'permissions',$group->text,$group->text); ?>
            <div class="row">
                <div class="col-lg-4">
                    <fieldset class="options-form">
                        <legend><?php echo Text::_('A_RIGHTS_POST'); ?></legend>
                        <?php foreach ($form->getFieldset('post') as $field) : ?>
                            <div class="control-group">
                                <div class="controls">
                                    <?php echo $field->input; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                    <fieldset class="options-form">
                        <legend><?php echo Text::_('A_RIGHTS_MISC'); ?></legend>
                        <?php foreach ($form->getFieldset('features') as $field) : ?>
                            <div class="control-group">
                                <div class="controls">
                                    <?php echo $field->input; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>
                </div>

                <div class="col-lg-4">
                    <fieldset class="options-form">
                        <legend><?php echo Text::_('A_RIGHTS_ADMINISTRATION'); ?></legend>
                        <?php foreach ($form->getFieldset('administration') as $field) : ?>
                            <div class="control-group">
                                <div class="controls">
                                    <?php echo $field->input; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>

                </div>

                <div class="col-lg-4">
                    <fieldset class="options-form">
                        <legend><?php echo Text::_('A_RIGHTS_BBCODE'); ?></legend>
                        <?php foreach ($form->getFieldset('bbcodes') as $field) : ?>
                            <div class="control-group">
                                <div class="controls">
                                    <?php echo $field->input; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>

                    <fieldset class="options-form">
                        <legend><?php echo Text::_('A_RIGHTS_view'); ?></legend>
                        <?php foreach ($form->getFieldset('view') as $field) : ?>
                            <div class="control-group">
                                <div class="controls">
                                    <?php echo $field->input; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </fieldset>

                </div>

            </div>
            <?php echo HTMLHelper::_('bootstrap.endTab'); ?>
        <?php endforeach; ?>
        <?php echo HTMLHelper::_('bootstrap.endTabSet'); ?>
    </div>
<?php endif;