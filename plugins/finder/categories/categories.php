<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Finder.Categories
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseQuery;
use Joomla\Registry\Registry;

JLoader::register('FinderIndexerAdapter', JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/adapter.php');

/**
 * Smart Search adapter for Joomla Categories.
 *
 * @since  2.5
 */
class PlgFinderCategories extends FinderIndexerAdapter
{
	/**
	 * The plugin identifier.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $context = 'Categories';

	/**
	 * The extension name.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $extension = 'com_categories';

	/**
	 * The sublayout to use when rendering the results.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $layout = 'category';

	/**
	 * The type of content that the adapter indexes.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $type_title = 'Category';

	/**
	 * The table name.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $table = '#__categories';

	/**
	 * The field the published state is stored in.
	 *
	 * @var    string
	 * @since  2.5
	 */
	protected $state_field = 'published';

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/**
	 * Method to remove the link information for items that have been deleted.
	 *
	 * @param   string  $context  The context of the action being performed.
	 * @param   JTable  $table    A JTable object containing the record to be deleted
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public function onFinderDelete($context, $table)
	{
		if ($context === 'com_categories.category')
		{
			$id = $table->id;
		}
		elseif ($context === 'com_finder.index')
		{
			$id = $table->link_id;
		}
		else
		{
			return true;
		}

		// Remove item from the index.
		return $this->remove($id);
	}

	/**
	 * Smart Search after save content method.
	 * Reindexes the link information for a category that has been saved.
	 * It also makes adjustments if the access level of the category has changed.
	 *
	 * @param   string   $context  The context of the category passed to the plugin.
	 * @param   JTable   $row      A JTable object.
	 * @param   boolean  $isNew    True if the category has just been created.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public function onFinderAfterSave($context, $row, $isNew)
	{
		// We only want to handle categories here.
		if ($context === 'com_categories.category')
		{
			// Check if the access levels are different.
			if (!$isNew && $this->old_access != $row->access)
			{
				// Process the change.
				$this->itemAccessChange($row);
			}

			// Reindex the category item.
			$this->reindex($row->id);

			// Check if the parent access level is different.
			if (!$isNew && $this->old_cataccess != $row->access)
			{
				$this->categoryAccessChange($row);
			}
		}

		return true;
	}

	/**
	 * Smart Search before content save method.
	 * This event is fired before the data is actually saved.
	 *
	 * @param   string   $context  The context of the category passed to the plugin.
	 * @param   JTable   $row      A JTable object.
	 * @param   boolean  $isNew    True if the category is just about to be created.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	public function onFinderBeforeSave($context, $row, $isNew)
	{
		// We only want to handle categories here.
		if ($context === 'com_categories.category')
		{
			// Query the database for the old access level and the parent if the item isn't new.
			if (!$isNew)
			{
				$this->checkItemAccess($row);
				$this->checkCategoryAccess($row);
			}
		}

		return true;
	}

	/**
	 * Method to update the link information for items that have been changed
	 * from outside the edit screen. This is fired when the item is published,
	 * unpublished, archived, or unarchived from the list view.
	 *
	 * @param   string   $context  The context for the category passed to the plugin.
	 * @param   array    $pks      An array of primary key ids of the category that has changed state.
	 * @param   integer  $value    The value of the state that the category has been changed to.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function onFinderChangeState($context, $pks, $value)
	{
		// We only want to handle categories here.
		if ($context === 'com_categories.category')
		{
			/*
			 * The category published state is tied to the parent category
			 * published state so we need to look up all published states
			 * before we change anything.
			 */
			foreach ($pks as $pk)
			{
				$query = clone $this->getStateQuery();
				$query->where($query->quoteName('a.id') . ' = :plgFinderCategoriesId')
					->bind(':plgFinderCategoriesId', (int) $pk);


				$this->db->setQuery($query);
				$item = $this->db->loadObject();

				// Translate the state.
				$state = null;

				if ($item->parent_id != 1)
				{
					$state = $item->cat_state;
				}

				$temp = $this->translateState($value, $state);

				// Update the item.
				$this->change($pk, 'state', $temp);

				// Reindex the item.
				$this->reindex($pk);
			}
		}

		// Handle when the plugin is disabled.
		if ($context === 'com_plugins.plugin' && $value === 0)
		{
			$this->pluginDisable($pks);
		}
	}

	/**
	 * Method to index an item. The item must be a FinderIndexerResult object.
	 *
	 * @param   FinderIndexerResult  $item  The item to index as a FinderIndexerResult object.
	 *
	 * @return  void
	 *
	 * @since   2.5
	 * @throws  Exception on database error.
	 */
	protected function index(FinderIndexerResult $item)
	{
		// Check if the extension is enabled.
		if (ComponentHelper::isEnabled($this->extension) === false)
		{
			return;
		}

		// Extract the extension element
		$parts = explode('.', $item->extension);
		$extension_element = $parts[0];

		// Check if the extension that owns the category is also enabled.
		if (ComponentHelper::isEnabled($extension_element) === false)
		{
			return;
		}

		$item->setLanguage();

		$extension = ucfirst(substr($extension_element, 4));

		// Initialize the item parameters.
		$item->params = new Registry($item->params);

		$item->metadata = new Registry($item->metadata);

		/*
		 * Add the metadata processing instructions based on the category's
		 * configuration parameters.
		 */
		// Add the meta author.
		$item->metaauthor = $item->metadata->get('author');

		// Handle the link to the metadata.
		$item->addInstruction(FinderIndexer::META_CONTEXT, 'link');
		$item->addInstruction(FinderIndexer::META_CONTEXT, 'metakey');
		$item->addInstruction(FinderIndexer::META_CONTEXT, 'metadesc');
		$item->addInstruction(FinderIndexer::META_CONTEXT, 'metaauthor');
		$item->addInstruction(FinderIndexer::META_CONTEXT, 'author');

		// Deactivated Methods
		// $item->addInstruction(FinderIndexer::META_CONTEXT, 'created_by_alias');

		// Trigger the onContentPrepare event.
		$item->summary = FinderIndexerHelper::prepareContent($item->summary, $item->params);

		// Create a URL as identifier to recognise items again.
		$item->url = $this->getUrl($item->id, $item->extension, $this->layout);

		/*
		 * Build the necessary route information.
		 * Need to import component route helpers dynamically, hence the reason it's handled here.
		 */
		$class = $extension . 'HelperRoute';

		// Need to import component route helpers dynamically, hence the reason it's handled here.
		JLoader::register($class, JPATH_SITE . '/components/' . $extension_element . '/helpers/route.php');

		if (class_exists($class) && method_exists($class, 'getCategoryRoute'))
		{
			$item->route = $class::getCategoryRoute($item->id, $item->language);
		}
		else
		{
			$item->route = ContentHelperRoute::getCategoryRoute($item->id, $item->language);
		}

		// Get the menu title if it exists.
		$title = $this->getItemMenuTitle($item->url);

		// Adjust the title if necessary.
		if (!empty($title) && $this->params->get('use_menu_title', true))
		{
			$item->title = $title;
		}

		// Translate the state. Categories should only be published if the parent category is published.
		$item->state = $this->translateState($item->state);

		// Add the type taxonomy data.
		$item->addTaxonomy('Type', 'Category');

		// Add the language taxonomy data.
		$item->addTaxonomy('Language', $item->language);

		// Get content extras.
		FinderIndexerHelper::getContentExtras($item);

		// Index the item.
		$this->indexer->index($item);
	}

	/**
	 * Method to setup the indexer to be run.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   2.5
	 */
	protected function setup()
	{
		// Load com_content route helper as it is the fallback for routing in the indexer in this instance.
		JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');

		return true;
	}

	/**
	 * Method to get the SQL query used to retrieve the list of content items.
	 *
	 * @param   mixed  $query  A JDatabaseQuery object or null.
	 *
	 * @return  JDatabaseQuery  A database object.
	 *
	 * @since   2.5
	 */
	protected function getListQuery($query = null)
	{
		$db = Factory::getDbo();

		// Check if we can use the supplied SQL query.
		$query = $query instanceof DatabaseQuery ? $query : $db->getQuery(true);

		$query->select(
			$db->quoteName(
					[
						'a.id',
						'a.title',
						'a.alias',
						'a.extension',
						'a.metakey',
						'a.metadesc',
						'a.metadata',
						'a.language',
						'a.lft',
						'a.parent_id',
						'a.level',
						'a.access',
						'a.params',
					]
				)
			)
					->select(
						$db->quoteName(
								[
									'a.description',
									'a.created_user_id',
									'a.modified_time',
									'a.modified_user_id',
									'a.created_time',
									'a.published'
								],
								[
									'summary',
									'created_by',
									'modified',
									'modified_by',
									'start_date',
									'state'
								]
							)
						);

		// Handle the alias CASE WHEN portion of the query.
		$case_when_item_alias = ' CASE WHEN ';
		$case_when_item_alias .= $query->charLength($db->quoteName('a.alias'), '!=', '0');
		$case_when_item_alias .= ' THEN ';
		$a_id = $query->castAsChar($db->quoteName('a.id'));
		$case_when_item_alias .= $query->concatenate([$a_id, 'a.alias'], ':');
		$case_when_item_alias .= ' ELSE ';
		$case_when_item_alias .= $a_id . ' END AS slug';

		$query->select($case_when_item_alias)
			->from($db->quoteName('#__categories', 'a'))
			->where($db->quoteName('a.id') . ' > 1');

		return $query;
	}

	/**
	 * Method to get a SQL query to load the published and access states for
	 * a category and its parents.
	 *
	 * @return  JDatabaseQuery  A database object.
	 *
	 * @since   2.5
	 */
	protected function getStateQuery()
	{
		$query = $this->db->getQuery(true);

		$query->select(
				$query->quoteName(
					[
						'a.id',
						'a.parent_id',
						'a.access'
					]
				)
			)
					->select(
							$query->quoteName(
							[
								'a.' . $this->state_field,
								'c.published',
								'c.access'
							],
							[
								'state',
								'cat_state',
								'cat_access'
							]
						)
					)
					->from($query->quoteName('#__categories', 'a'))
					->leftJoin(
						$query->quoteName('#__categories', 'c') .
						' ON ' . $query->quoteName('c.id') . ' = ' . $query->quoteName('a.parent_id')
					);

		return $query;
	}
}
