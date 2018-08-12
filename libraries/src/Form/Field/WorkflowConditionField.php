<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_workflow
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
namespace Joomla\CMS\Form\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Workflow\Workflow;

/**
 * Workflow States field.
 *
 * @since  __DEPLOY_VERSION__
 */
class WorkflowConditionField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var     string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $type = 'WorkflowCondition';

	/**
	 * The extension where we're
	 *
	 * @var     string
	 * @since  __DEPLOY_VERSION__
	 */
	protected $extension = 'com_content';

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as as an array container for the field.
	 *                                       For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                       full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$success = parent::setup($element, $value, $group);

		if ($success)
		{
			if (strlen($element['extension']))
			{
				$this->extension =  (string) $element['extension'];
			}
			else
			{
			  $this->extension = Factory::getApplication()->input->getCmd('extension');
      }
		}

		return $success;
	}

  /**
   * Method to get the field options.
   *
   * @return  array  The field option objects.
   *
   * @since   __DEPLOY_VERSION__
   */
  protected function getOptions()
  {
    $options = [];

    $component = Factory::getApplication()->bootComponent($this->extension);
    if ($component instanceof WorkflowServiceInterface)
    {
      $options = $component->getConditions();
    }

		// Merge any additional options in the XML definition.
		return array_merge(parent::getOptions(), $options);
	}
}
