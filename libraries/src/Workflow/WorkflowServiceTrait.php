<?php
/**
 * Joomla! Content Management System
 *
 * @copyright  Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\CMS\Workflow;

use Joomla\Utilities\ArrayHelper;

defined('JPATH_PLATFORM') or die;

/**
 * Trait for component workflow service.
 *
 * @since  4.0.0
 */
trait WorkflowServiceTrait
{

  /**
   * Returns an array of possible conditions for the component.
   *
   * @return  array
   *
   * @since   __DEPLOY_VERSION__
   */
  public static function getConditions(): array
  {
    if (defined('self::CONDITION_NAMES')) {
      return self::CONDITION_NAMES;
    } else {
      return Workflow::CONDITION_NAMES;
    }
  }
}
