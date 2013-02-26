<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_content
 * @since       1.6
 */
class ContentControllerArticle extends JControllerForm
{
	/**
	 * Class constructor.
	 *
	 * @param   array  $config  A named array of configuration variables.
	 *
	 * @since   1.6
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		// An article edit form can come from the articles or featured view.
		// Adjust the redirect view on the value of 'return' in the request.
		if ($this->input->get('return') == 'featured')
		{
			$this->view_list = 'featured';
			$this->view_item = 'article&return=featured';
		}
	}

	/**
	 * Method override to check if you can add a new record.
	 *
	 * @param   array  $data  An array of input data.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowAdd($data = array())
	{
		$user = JFactory::getUser();
		$categoryId = JArrayHelper::getValue($data, 'catid', $this->input->getInt('filter_category_id'), 'int');
		$allow = null;

		if ($categoryId)
		{
			// If the category has been passed in the data or URL check it.
			$allow = $user->authorise('core.create', 'com_content.category.' . $categoryId);
		}

		if ($allow === null)
		{
			// In the absense of better information, revert to the component permissions.
			return parent::allowAdd();
		}
		else
		{
			return $allow;
		}
	}

	/**
	 * Method override to check if you can edit an existing record.
	 *
	 * @param   array   $data  An array of input data.
	 * @param   string  $key   The name of the key for the primary key.
	 *
	 * @return  boolean
	 *
	 * @since   1.6
	 */
	protected function allowEdit($data = array(), $key = 'id')
	{
		$recordId = (int) isset($data[$key]) ? $data[$key] : 0;
		$user = JFactory::getUser();
		$userId = $user->get('id');

		// Check general edit permission first.
		if ($user->authorise('core.edit', 'com_content.article.' . $recordId))
		{
			return true;
		}

		// Fallback on edit.own.
		// First test if the permission is available.
		if ($user->authorise('core.edit.own', 'com_content.article.' . $recordId))
		{
			// Now test the owner is the user.
			$ownerId = (int) isset($data['created_by']) ? $data['created_by'] : 0;
			if (empty($ownerId) && $recordId)
			{
				// Need to do a lookup from the model.
				$record = $this->getModel()->getItem($recordId);

				if (empty($record))
				{
					return false;
				}

				$ownerId = $record->created_by;
			}

			// If the owner matches 'me' then do the test.
			if ($ownerId == $userId)
			{
				return true;
			}
		}

		// Since there is no asset tracking, revert to the component permissions.
		return parent::allowEdit($data, $key);
	}

	/**
	 * Method to run batch operations.
	 *
	 * @param   object  $model  The model.
	 *
	 * @return  boolean   True if successful, false otherwise and internal error is set.
	 *
	 * @since   1.6
	 */
	public function batch($model = null)
	{
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Set the model
		$model = $this->getModel('Article', '', array());

		// Preset the redirect
		$this->setRedirect(JRoute::_('index.php?option=com_content&view=articles' . $this->getRedirectToListAppend(), false));

		return parent::batch($model);
	}

	/**
	 * Function that allows child controller access to model data after the data has been saved.
	 *
	 * @param   JModelLegacy  $model  The data model object.
	 * @param   array         $validData   The validated data.
	 *
	 * @return	void
	 * @since	1.6
	 */
	protected function postSaveHook(JModelLegacy $model, $validData = array())
	{
		//Most of this should go into JAdminFormcontent
		$task = $this->getTask();

		$item = $model->getItem();
		if (isset($item->attribs) && is_array($item->attribs))
		{
			$registry = new JRegistry;
			$registry->loadArray($item->attribs);
			$item->attribs = (string) $registry;
		}
		if (isset($item->images) && is_array($item->images))
		{
			$registry = new JRegistry;
			$registry->loadArray($item->images);
			$item->images = (string) $registry;
		}
		if (isset($item->urls) && is_array($item->urls))
		{
			$registry = new JRegistry;
			$registry->loadArray($item->urls);
			$item->urls = (string) $registry;
		}
		if (isset($item->metadata) && is_array($item->metadata))
		{
			$registry = new JRegistry;
			$registry->loadArray($item->metadata);
			$item->metadata = (string) $registry;
		}
		$id =  $item->id;

		$fieldMap = Array(
			'core_title' => "'" . $item->title . "'",
			'core_alias' => "'" . $item->alias . "'",
			'core_body' => "'" . $item->introtext . "'",
			'core_state' => $item->state,
			'core_checked_out_user_id' => $item->checked_out,
			'core_checked_out_time' => "'" . $item->checked_out_time  . "'",
			'core_access' => $item->access,
			'core_params' => "'" . $item->attribs . "'",
			'core_featured' => $item->featured,
			'core_metadata' => "'" . $item->metadata . "'",
			'core_created_user_id' => $item->created_by,
			'core_created_by_alias' => "'" . $item->created_by_alias . "'" ,
			'core_created_time' => "'" . $item->created  . "'",
			'core_modified_user_id' => $item->modified_by,
			'core_modified_time' => "'" . $item->modified  . "'",
			'core_language' => "'" . $item->language . "'",
			'core_publish_up' => "'" . $item->publish_up . "'",
			'core_publish_down' => "'" . $item->publish_down . "'",
			'core_content_item_id' => $item->id,
			'core_type_alias' => "'" . 'com_content.article' . "'",
			'asset_id' => $item->asset_id,
			'core_images' => "'" . $item->images . "'",
			'core_urls' => "'" . $item->urls . "'",
			'core_hits' => "'" . $item->hits . "'",
			'core_version' => "'" . $item->version . "'",
			'core_ordering' => "'" . $item->ordering . "'",
			'core_metakey' => "'" . $item->metakey . "'",
			'core_metadesc' => "'" . $item->metadesc . "'",
			'core_catid' => "'" . $item->catid . "'",
			'core_xreference' => "'" . $item->xreference . "'",
			);

		$tags = $validData['tags'];
		$isNew = $validData['id'] == 0 ? 1 : 0;

		// Store the tag data if the article data was saved.
		if ($tags[0] != '')
		{
			$tagsHelper = new JTags;
			$tagsHelper->tagItem($id, 'com_content.article', $tags, $fieldMap, $isNew);
		}
	}
}
