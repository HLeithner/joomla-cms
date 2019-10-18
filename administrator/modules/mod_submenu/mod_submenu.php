<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  mod_submenu
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\Component\Menus\Administrator\Helper\MenusHelper;
use Joomla\Module\Submenu\Administrator\Menu\Menu;

$menutype = $params->get('menutype', '*');
$root     = false;

if ($menutype === '*')
{
	$name   = $params->get('preset', 'system');
	$root = MenusHelper::loadPreset($name);
}
else
{
	$root = MenusHelper::getMenuItems($menutype, true);
}

if ($root && $root->hasChildren())
{
	Factory::getLanguage()->load(
		'mod_menu',
		JPATH_ADMINISTRATOR,
		Factory::getLanguage()->getTag(),
		true
	);

	Menu::preprocess($root);

	// Render the module layout
	require ModuleHelper::getLayoutPath('mod_submenu', $params->get('layout', 'default'));
}
