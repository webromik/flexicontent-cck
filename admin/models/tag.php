<?php
/**
 * @package         FLEXIcontent
 * @version         3.3
 *
 * @author          Emmanuel Danan, Georgios Papadakis, Yannick Berges, others, see contributor page
 * @link            https://flexicontent.org
 * @copyright       Copyright © 2018, FLEXIcontent team, All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

require_once('base/base.php');

/**
 * FLEXIcontent Component Tag Model
 *
 */
class FlexicontentModelTag extends FCModelAdmin
{
	//use FCModelTraitNestableRecord;

	/**
	 * Record name, (parent class property), this is used for: naming session data, XML file of class, etc
	 *
	 * @var string
	 */
	protected $name = 'tag';

	/**
	 * Record database table
	 *
	 * @var string
	 */
	var $records_dbtbl = 'flexicontent_tags';

	/**
	 * Record jtable name
	 *
	 * @var string
	 */
	var $records_jtable = 'flexicontent_tags';

	/**
	 * Column names
	 */
	var $state_col   = 'published';
	var $name_col    = 'name';
	var $parent_col  = null;//'parent_id';

	/**
	 * Record primary key
	 *
	 * @var int
	 */
	var $_id = null;

	/**
	 * Record data
	 *
	 * @var object
	 */
	var $_record = null;

	/**
	 * Events context to use during model FORM events and diplay PREPARE events triggering
	 *
	 * @var object
	 */
	var $events_context = 'com_tags.tag';

	/**
	 * Flag to indicate adding new records with next available ordering (at the end),
	 * this is ignored if this record DB model does not have 'ordering'
	 *
	 * @var boolean
	 */
	var $useLastOrdering = false;

	/**
	 * Plugin group used to trigger events
	 *
	 * @var boolean
	 */
	var $plugins_group = null;

	/**
	 * Records real extension
	 *
	 * @var string
	 */
	var $extension_proxy = null;
	var $event_recid_col = 'jtag_id';

	/**
	 * Context to use for registering (language) associations
	 *
	 * @var string
	 */
	var $associations_context = false;

	/**
	 * Various record specific properties
	 *
	 */
	// ...

	/**
	 * Constructor
	 *
	 * @since 3.3.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->canManage = FlexicontentHelperPerm::getPerm()->CanTags;
		$this->canCreate = FlexicontentHelperPerm::getPerm()->CanCreateTags;
	}


	/**
	 * Legacy method to get the record
	 *
	 * @return	object
	 *
	 * @since	1.0
	 */
	public function getTag($pk = null)
	{
		return parent::getRecord($pk);
	}


	/**
	 * Method to initialise the record data
	 *
	 * @param   object      $record    The record being initialized
	 * @param   boolean     $initOnly  If true then only a new record will be initialized without running the _afterLoad() method
	 *
	 * @return	boolean	True on success
	 *
	 * @since	1.5
	 */
	protected function _initRecord(&$record = null, $initOnly = false)
	{
		parent::_initRecord($record, $initOnly);

		// Set some new record specific properties, note most properties already have proper values
		// Either the DB default values (set by getTable() method) or the values set by _afterLoad() method
		$record->id							= 0;
		$record->name						= null;
		$record->alias					= null;
		$record->published			= 1;
		$record->checked_out		= 0;
		$record->checked_out_time	= '';

		$this->_record = $record;

		return true;
	}


	/**
	 * Legacy method to store the record, use save() instead
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.2.0
	 */
	public function store($data)
	{
		return parent::store($data);
	}


	/**
	 * Method to preprocess the form.
	 *
	 * @param   JForm   $form   A JForm object.
	 * @param   mixed   $data   The data expected for the form.
	 * @param   string  $plugins_group  The name of the plugin group to import and trigger
	 *
	 * @return  void
	 *
	 * @see     JFormField
	 * @since   1.6
	 * @throws  Exception if there is an error in the form event.
	 */
	protected function preprocessForm(JForm $form, $data, $plugins_group = null)
	{
		parent::preprocessForm($form, $data, $plugins_group);
	}


	/**
	 * Method to change the title & alias.
	 *
	 * @param   integer  $parent_id  If applicable, the id of the parent (e.g. assigned category)
	 * @param   string   $alias      The alias / name.
	 * @param   string   $title      The title / label.
	 *
	 * @return  array    Contains the modified title and alias / name.
	 *
	 * @since   1.7
	 */
	protected function generateNewTitle($parent_id, $alias, $title)
	{
		// Alter the title & alias
		$table = $this->getTable();

		while ($table->load(array('alias' => $alias)))
		{
			$title = StringHelper::increment($title);
			$alias = StringHelper::increment($alias, 'dash');
		}

		return array($title, $alias);
	}


	/**
	 * Method to check if the user can edit the record
	 *
	 * @return	boolean	True on success
	 *
	 * @since	3.2.0
	 */
	public function canEdit($record = null)
	{
		$record  = $record ?: $this->_record;
		$user    = JFactory::getUser();

		return !$record || !$record->id
			? $this->canCreate
			: $this->canManage;
	}


	/**
	 * Method to check if the user can edit record 's state
	 *
	 * @return	boolean	True on success
	 *
	 * @since	3.2.0
	 */
	public function canEditState($record = null)
	{
		$record  = $record ?: $this->_record;
		$user    = JFactory::getUser();

		return $this->canManage;
	}


	/**
	 * Method to check if the user can delete the record
	 *
	 * @return	boolean	True on success
	 *
	 * @since	3.2.0
	 */
	public function canDelete($record = null)
	{
		$record  = $record ?: $this->_record;
		$user    = JFactory::getUser();

		return $this->canManage;
	}


	/**
	 * Method to do some record / data preprocessing before call JTable::bind()
	 *
	 * Note. Typically called inside this MODEL 's store()
	 *
	 * @param   object     $record   The record object
	 * @param   array      $data     The new data array
	 *
	 * @since	3.2.0
	 */
	protected function _prepareBind($record, & $data)
	{
		// Call parent class bind preparation
		parent::_prepareBind($record, $data);
	}


	/**
	 * Method to do some work after record has been stored
	 *
	 * Note. Typically called inside this MODEL 's store()
	 *
	 * @param   object     $record   The record object
	 * @param   array      $data     The new data array
	 *
	 * @since	3.2.0
	 */
	protected function _afterStore($record, & $data)
	{
		parent::_afterStore($record, $data);
	}


	/**
	 * Method to do some work after record has been loaded via JTable::load()
	 *
	 * Note. Typically called inside this MODEL 's store()
	 *
	 * @param	object   $record   The record object
	 *
	 * @since	3.2.0
	 */
	protected function _afterLoad($record)
	{
		parent::_afterLoad($record);
	}


	/**
	 * START OF MODEL SPECIFIC METHODS
	 */


	/**
	 * Method to change the state of a record
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.0
	 */
	function setitemstate($id, $state = 1, $cleanCache = true)
	{
		$app  = JFactory::getApplication();
		$user = JFactory::getUser();

		$jinput     = JFactory::getApplication()->input;
		$dispatcher = JEventDispatcher::getInstance();

		$option = $jinput->get('option', '', 'cmd');
		$view = $jinput->get('view', '', 'cmd');
		$format = $jinput->get('format', 'html', 'cmd');

		$jinput->set('isflexicontent', 'yes');
		static $event_failed_notice_added = false;

		if ( !$id )
		{
			return false;
		}

		if (empty($this->using_native_tags))
		{
			$query = 'UPDATE #__flexicontent_tags'
				. ' SET published = ' . (int) $state
				. ' WHERE id IN (' . (int) $id . ')'
			;
			$this->_db->setQuery( $query );
			$this->_db->execute();
			return true;
		}

		// Add all children to the list
		$cid = array($id);
		if ($state!=1) $this->_addTags($id, $cid);

		// Add all parents to the list
		if ($state==1) $this->_addTags($id, $cid, 'parents');

		JArrayHelper::toInteger($cid, null);
		$cids = implode( ',', $cid );

		// Get the owner of all tags
		$query = 'SELECT id, created_user_id'
			. ' FROM #__tags as c'
			. ' WHERE id IN (' . $cids . ')'
			;
		$this->_db->setQuery( $query );
		$tags = $this->_db->loadObjectList('id');

		// Check access to change state of tags
		foreach ($cid as $tagid)
		{
			$hasEditState			= $user->authorise('core.edit.state', 'com_tags.tag.' . $tagid);
			$hasEditStateOwn	= $user->authorise('core.edit.state.own', 'com_tags.tag.' . $tagid) && $tags[$tagid]->created_user_id==$user->get('id');
			if (!$hasEditState && !$hasEditStateOwn)
			{
				$msg = 'You are not authorised to change state of tag with id: '. $tagid
					.'<br />NOTE: when publishing a tag the parent tags will get published'
					.'<br />NOTE: when unpublishing a tag the children tags will get unpublished';

				$this->setError($msg);
				return false;
			}
		}

		$query = 'UPDATE #__tags'
			. ' SET published = ' . (int) $state
			. ' WHERE id IN (' . $cids . ')'
		;
		$this->_db->setQuery( $query );
		$this->_db->execute();
		
		
		// ****************************************************************
		// Trigger Event 'onContentChangeState' of Joomla's Content plugins
		// ****************************************************************
		// Make sure we import flexicontent AND content plugins since we will be triggering their events
		JPluginHelper::importPlugin('content');
		
		$item = new stdClass();
		
		// Compatibility steps, so that 3rd party plugins using the change state event work properly
		$jinput->set('view', 'tags');
		$jinput->set('option', 'com_tags');

		$result = $dispatcher->trigger($this->event_change_state, array('com_tags.tag', (array) $id, $state));
		
		// Revert compatibilty steps ... besides the plugins using the change state event, should have updated DB state value anyway
		$jinput->set('view', $view);
		$jinput->set('option', $option);
		
		if (in_array(false, $result, true) && !$event_failed_notice_added)
		{
			$app->enqueueMessage('At least 1 plugin event handler for onContentChangeState failed', 'warning');
			$event_failed_notice_added = true;
			return false;
		}

		if ($cleanCache)
		{
			$this->cleanCache(null, 0);
			$this->cleanCache(null, 1);
		}
		return true;
	}


	/**
	 * Method to add children/parents to a specific tag
	 *
	 * @param int $id
	 * @param array $list
	 * @param string $type
	 * @return oject
	 * 
	 * @since 1.0
	 */
	function _addTags($id, &$list, $type = 'children')
	{
		// Initialize variables
		$return = true;

		$get = $type == 'children' ? 'id' : 'parent_id';
		$source = $type == 'children' ? 'parent_id' : 'id';

		// Get all rows with parent of $id
		$query = 'SELECT ' . $get
			. ' FROM #__tags as c'
			. ' AND ' . $source . ' = ' . (int) $id . ' AND ' . $get . ' <> 1';
		$this->_db->setQuery( $query );
		$rows = $this->_db->loadObjectList();

		// Recursively iterate through all children
		foreach ($rows as $row)
		{
			$found = false;
			foreach ($list as $idx)
			{
				if ($idx == $row->$get)
				{
					$found = true;
					break;
				}
			}
			if (!$found)
			{
				$list[] = $row->$get;
			}
			$return = $this->_addTags($row->$get, $list, $type);
		}

		return $return;
	}
}