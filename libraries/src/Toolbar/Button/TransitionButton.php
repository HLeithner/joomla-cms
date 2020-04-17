<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Toolbar\Button;

\defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\ToolbarButton;
use Joomla\Utilities\ArrayHelper;

/**
 * Render dropdown buttons.
 *
 * @method self extension(string $value)
 * @method string getExtension()
 *
 * @since  4.0.0
 */
class TransitionButton extends DropdownButton
{
	/**
	 * Property layout.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $layout = 'joomla.toolbar.transition';

	public function __construct(string $name = '', string $text = '', array $options = [])
	{
		$text = $text ?? 'JTOOLBAR_CHANGE_STATUS';

		parent::__construct($name, $text, $options);

		$this->toggleSplit(false)
				 ->icon('fas fa-ellipsis-h')
				 ->buttonClass('btn btn-action')
				 ->listCheck(true);
	}

	public function transitions(string $extension, CMSObject $canDo)
	{
		$this->extension($extension);
/*
		$childBar = $this->getChildToolbar();

			$childBar->publish('articles.publish')->listCheck(true);

			$childBar->unpublish('articles.unpublish')->listCheck(true);

		if ($canDo->get('core.edit.state'))
		{
			$childBar->standardButton('featured')
							 ->text('JFEATURE')
							 ->task('articles.featured')
							 ->listCheck(true);

			$childBar->standardButton('unfeatured')
							 ->text('JUNFEATURE')
							 ->task('articles.unfeatured')
							 ->listCheck(true);
		}

		if ($canDo->get('core.execute.transition'))
		{
			$childBar->archive('articles.archive')->listCheck(true);
		}

		if ($canDo->get('core.edit.state'))
		{
			$childBar->checkin('articles.checkin')->listCheck(true);
		}

		if ($canDo->get('core.execute.transition'))
		{
			$childBar->trash('articles.trash')->listCheck(true);
		}

		// Add a batch button
		if ($user->authorise('core.create', 'com_content')
			&& $user->authorise('core.edit', 'com_content')
			&& $user->authorise('core.execute.transition', 'com_content'))
		{
			$childBar->popupButton('batch')
							 ->text('JTOOLBAR_BATCH')
							 ->selector('collapseModal')
							 ->listCheck(true);
		}
		*/
	}

	/**
	 * Method to configure available option accessors.
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	protected static function getAccessors(): array
	{
		return \array_merge(
			parent::getAccessors(),
			[
				'extension',
			]
		);
	}
}
