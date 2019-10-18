<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_config
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

defined('_JEXEC') or die;

$this->name        = Text::_('COM_CONFIG_PERMISSION_SETTINGS');
$this->description = '';
$this->fieldsname  = 'permissions';
$this->formclass   = 'form-no-margin options-grid-form options-grid-form-full';
$this->showlabel   = false;

echo LayoutHelper::render('joomla.content.options_default', $this);
