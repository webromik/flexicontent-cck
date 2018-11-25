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
use Joomla\CMS\Table\Table;
use Joomla\CMS\Table\User;

jimport('legacy.model.list');

/**
 * Flexicontent Component Categoryelement Model
 *
 */
class FlexicontentModelQfcategoryelement extends JModelList
{
	/**
	 * Record database table
	 *
	 * @var string
	 */
	var $records_dbtbl = 'categories';

	/**
	 * Record jtable name
	 *
	 * @var string
	 */
	var $records_jtable = 'flexicontent_categories';

	/**
	 * Column names
	 */
	var $state_col      = 'published';
	var $name_col       = 'title';
	var $parent_col     = 'parent_id';
	var $created_by_col = 'created_user_id';

	/**
	 * (Default) Behaviour Flags
	 */
	protected $listViaAccess = true;
	protected $copyRelations = false;

	/**
	 * Search and ordering columns
	 */
	var $search_cols = array(
		'FLEXI_TITLE' => 'title',
		'FLEXI_ALIAS' => 'alias',
		'FLEXI_NOTES' => 'note',
	);
	var $default_order     = 'c.lft';
	var $default_order_dir = 'ASC';

	/**
	 * List filters that are always applied
	 */
	var $hard_filters = array('c.extension' => FLEXI_CAT_EXTENSION);

	/**
	 * Record rows
	 *
	 * @var array
	 */
	var $_data = null;

	/**
	 * Rows total
	 *
	 * @var integer
	 */
	var $_total = null;

	/**
	 * Pagination object
	 *
	 * @var object
	 */
	var $_pagination = null;

	/**
	 * Associated record translations
	 *
	 * @var array
	 */
	var $_translations = null;


	/**
	 * Constructor
	 *
	 * @since 3.3.0
	 */
	public function __construct($config = array())
	{
		$app    = JFactory::getApplication();
		$jinput = $app->input;
		$option = $jinput->getCmd('option', '');
		$view   = $jinput->getCmd('view', '');
		$layout = $jinput->getString('layout', 'default');
		$fcform = $jinput->getInt('fcform', 0);

		// Make session index more specific ... (if needed by this model)
		$this->assocs_id = $jinput->getInt('assocs_id', 0);

		// Call parent after setting ... $this->view_id
		parent::__construct($config);

		$p = $option.'.'.$view.'.';


		// Parameters of the view, in our case it is only the component parameters
		$this->cparams = JComponentHelper::getParams( 'com_flexicontent' );

		// *****************************
		// Pagination: limit, limitstart
		// *****************************

		$limit      = $fcform ? $jinput->get('limit', $app->getCfg('list_limit'), 'int')  :  $app->getUserStateFromRequest( $p.'limit', 'limit', $app->getCfg('list_limit'), 'int');
		$limitstart = $fcform ? $jinput->get('limitstart',                     0, 'int')  :  $app->getUserStateFromRequest( $p.'limitstart', 'limitstart', 0, 'int' );

		// In case limit has been changed, adjust limitstart accordingly
		$limitstart = ( $limit != 0 ? (floor($limitstart / $limit) * $limit) : 0 );
		$jinput->set( 'limitstart',	$limitstart );

		$this->setState('limit', $limit);
		$this->setState('limitstart', $limitstart);

		$app->setUserState($p.'limit', $limit);
		$app->setUserState($p.'limitstart', $limitstart);
	}


	/**
	 * Method to get item data
	 *
	 * @access public
	 * @return object
	 */
	function getData()
	{
		// Catch case of guest user submitting in frontend
		if (!JFactory::getUser()->id)
		{
			return $this->_data = array();
		}

		$lang_assocs = array();

		if ($this->assocs_id)
		{
			$lang_assocs = flexicontent_db::getLangAssocs(
				array($this->assocs_id),
				(object) array(
					'table'    => 'categories',
					'context'  => 'com_categories.item',
					'created'  => 'created_time',
					'modified' => 'modified_time',
				)
			);
		}

		$print_logging_info = $this->cparams->get('print_logging_info');
		if ( $print_logging_info )  global $fc_run_times;

		// Lets load the records if it doesn't already exist
		if ($this->_data === null)
		{
			if (1)
			{
				// 1, get filtered, limited, ordered items
				$query = $this->_buildQuery();

				if ( $print_logging_info )  $start_microtime = microtime(true);
				$this->_db->setQuery($query, $this->getState('limitstart'), $this->getState('limit'));
				$rows = $this->_db->loadObjectList();
				if ( $print_logging_info ) @$fc_run_times['execute_main_query'] += round(1000000 * 10 * (microtime(true) - $start_microtime)) / 10;

				// 2, get current items total for pagination
				$this->_db->setQuery("SELECT FOUND_ROWS()");
				$this->_total = $this->_db->loadResult();

				// 3, get item ids
				$query_ids = array();
				foreach ($rows as $row)
				{
					$query_ids[] = $row->id;
				}
			}

			// 4, get item data
			if (count($query_ids)) $query = $this->_buildQuery($query_ids);
			if ( $print_logging_info )  $start_microtime = microtime(true);
			$_data = array();
			if (count($query_ids))
			{
				$_data = $this->_db->setQuery($query)->loadObjectList('id');
			}
			if ( $print_logging_info ) @$fc_run_times['execute_sec_queries'] += round(1000000 * 10 * (microtime(true) - $start_microtime)) / 10;

			// 5, reorder items and get cat ids
			$this->_data = array();
			foreach($query_ids as $id)
			{
				$item = $_data[$id];
				$this->_data[] = $item;

				if (isset($lang_assocs[$this->assocs_id][$id]))
				{
					$item->is_current_association = 1;
				}
			}
		}

		return $this->_data;
	}


	/**
	 * Method to get the total nr of the records
	 *
	 * @return integer
	 *
	 * @since	1.5
	 */
	public function getTotal()
	{
		// Catch case of guest user submitting in frontend
		if (!JFactory::getUser()->id)
		{
			return $this->_total = 0;
		}

		// Lets load the records if it was not calculated already via using SQL_CALC_FOUND_ROWS + 'SELECT FOUND_ROWS()'
		if ($this->_total === null)
		{
			$query = $this->_buildQuery();
			$this->_total = $this->_getListCount($query);
		}

		return $this->_total;
	}


	/**
	 * Method to get a pagination object for the records
	 *
	 * @return object
	 *
	 * @since	1.5
	 */
	public function getPagination()
	{
		// Create pagination object if it doesn't already exist
		if (empty($this->_pagination))
		{
			require_once (JPATH_COMPONENT_SITE.DS.'helpers'.DS.'pagination.php');
			$this->_pagination = new FCPagination( $this->getTotal(), $this->getState('limitstart'), $this->getState('limit') );
		}

		return $this->_pagination;
	}


	/**
	 * Method to build the query for the records
	 *
	 * @return  JDatabaseQuery   The DB Query object
	 *
	 * @since   3.3.0
	 */
	protected function _buildQuery($query_ids = false)
	{
		if (!$query_ids)
		{
			$query = $this->_db->getQuery(true)
				->select('SQL_CALC_FOUND_ROWS c.id')
				->select('ua.name AS author')
				->from('#__' . $this->records_dbtbl . ' AS c')
				->join('LEFT', '#__users as ua ON ua.id = c.' . $this->created_by_col)
			;

			// Get the WHERE, HAVING and ORDER BY clauses for the query
			$this->_buildContentWhere($query);
			//$this->_buildContentHaving($query);
			$this->_buildContentOrderBy($query);

			// Add always-active ("hard") filters
			//$this->_buildHardFiltersWhere($query);
		}
		else
		{
			$query = $this->_db->getQuery(true)
				->select('c.*')
				->select('CASE WHEN level.title IS NULL THEN CONCAT_WS(\'\', \'deleted:\', c.access) ELSE level.title END AS access_level')
				->select('ua.name AS author')
				->from('#__' . $this->records_dbtbl . ' AS c')
				->join('LEFT', '#__viewlevels as level ON level.id = c.access')
				->join('LEFT', '#__users as ua ON ua.id = c.' . $this->created_by_col)
				->where('c.id IN (' . implode(',', $query_ids) . ')')
				->group('c.id');
		}

		//echo nl2br(str_replace('#__', 'jos_', $query));
		//echo str_replace('#__', 'jos_', $query->__toString());

		return $query;
	}


	/**
	 * Method to build the orderby clause of the query for the Items
	 *
	 * @access private
	 * @return string
	 * @since 1.0
	 */
	function _buildContentOrderBy($query = null)
	{
		$app = JFactory::getApplication();
		$jinput  = $app->input;
		$option  = $jinput->get('option', '', 'cmd');
		$view    = $jinput->get('view', '', 'cmd');

		$filter_order     = $app->getUserStateFromRequest( $option.'.'.$view.'.filter_order',     'filter_order',     'c.lft',      'cmd' );
		$filter_order_Dir = $app->getUserStateFromRequest( $option.'.'.$view.'.filter_order_Dir', 'filter_order_Dir', '',           'cmd' );

		$orderby = $filter_order.' '.$filter_order_Dir . ($filter_order != 'c.lft' ? ', c.lft' : '');
		if ($query)
			$query->order($orderby);
		else
			return ' ORDER BY '. $orderby;
	}


	/**
	 * Method to build the where clause of the query for the Items
	 *
	 * @access private
	 * @return string
	 * @since 1.0
	 */
	protected function _buildContentWhere($query = null)
	{
		$app    = JFactory::getApplication();
		$user   = JFactory::getUser();

		$jinput  = $app->input;
		$option  = $jinput->get('option', '', 'cmd');
		$view    = $jinput->get('view', '', 'cmd');

		$assocs_id = $jinput->get('assocs_id', 0, 'int');

		if ($assocs_id)
		{
			$item_lang   = $app->getUserStateFromRequest( $option.'.'.$view.'.item_lang', 'item_lang', '', 'string' );
			$created_by  = $app->getUserStateFromRequest( $option.'.'.$view.'.created_by', 'created_by', 0, 'int' );

			$assocanytrans = $user->authorise('flexicontent.assocanytrans', 'com_flexicontent');
			if (!$assocanytrans && !$created_by)
			{
				$created_by = $user->id;
				$app->setUserState( $option.'.'.$view.'.created_by', $created_by );
			}
		}

		$filter_state  = $app->getUserStateFromRequest( $option.'.'.$view.'.filter_state', 'filter_state', '', 'cmd' );
		$filter_cats   = $app->getUserStateFromRequest( $option.'.'.$view.'.filter_cats',  'filter_cats',  0,  'int' );

		$filter_level  = $app->getUserStateFromRequest( $option.'.'.$view.'.filter_level', 'filter_level', 0,  'int' );
		$filter_lang   = $app->getUserStateFromRequest( $option.'.'.$view.'.filter_lang',  'filter_lang',  '', 'string' );
		$filter_author = $app->getUserStateFromRequest( $option.'.'.$view.'.filter_author','filter_author','', 'cmd' );
		$filter_access = $app->getUserStateFromRequest( $option.'.'.$view.'.filter_access','filter_access','', 'cmd' );

		$filter_lang   = $assocs_id && $item_lang  ? $item_lang  : $filter_lang;
		$filter_author = $assocs_id && $created_by ? $created_by : $filter_author;

		$search = $app->getUserStateFromRequest( $option.'.'.$view.'.search', 'search', '', 'string' );
		$search = StringHelper::trim( StringHelper::strtolower( $search ) );

		$where = array();
		$where[] = "c.extension = '".FLEXI_CAT_EXTENSION."' ";

		// Filter by publications state
		if (is_numeric($filter_state)) {
			$where[] = 'c.published = ' . (int) $filter_state;
		}
		elseif ( $filter_state === '') {
			$where[] = 'c.published IN (0, 1)';
		}
		elseif ( $filter_state ) {
			if ( $filter_state == 'P' ) {
				$where[] = 'c.published = 1';
			} else if ($filter_state == 'U' ) {
				$where[] = 'c.published = 0';
			} else if ($filter_state == 'A' ) {
				$where[] = 'c.published = 2';
			}
		}

		// Filter by access level
		if (strlen($filter_access))
		{
			$where[] = 'c.access = '.(int) $filter_access;
		}

		// Filter by parent category
		if ($filter_cats)
		{
			// Limit category list to those contain in the subtree of the choosen category
			$where[] = ' c.id IN (SELECT cat.id FROM #__categories AS cat JOIN #__categories AS parent ON cat.lft BETWEEN parent.lft AND parent.rgt WHERE parent.id='. (int) $filter_cats.')';
		} else {
			// Limit category list to those containing CONTENT (joomla articles)
			$where[] = ' (c.lft >= ' . $this->_db->Quote(FLEXI_LFT_CATEGORY) . ' AND c.rgt <= ' . $this->_db->Quote(FLEXI_RGT_CATEGORY) . ')';
		}

		// Filter by depth level
		if ($filter_level)
		{
			$where[] = 'c.level <= '.(int) $filter_level;
		}

		// Filter by language
		if ( $filter_lang ) {
			$where[] = 'c.language = '.$this->_db->Quote( $filter_lang );
		}

		// Filter by author / owner
		if ( strlen($filter_author) ) {
			$where[] = 'c.created_user_id = ' . $filter_author;
		}

		// Implement View Level Access
		if (!$user->authorise('core.admin'))
		{
			$groups	= implode(',', JAccess::getAuthorisedViewLevels($user->id));
			$where[] = 'c.access IN ('.$groups.')';
		}

		// Filter by search word (can be also be  id:NN  OR author:AAAAA)
		if ( !empty($search) ) {
			if (stripos($search, 'id:') === 0) {
				$where[] = 'c.id = '.(int) substr($search, 3);
			}
			elseif (stripos($search, 'author:') === 0) {
				$search = $this->_db->Quote('%'.$this->_db->escape(substr($search, 7), true).'%');
				$where[] = '(u.name LIKE '.$search.' OR u.username LIKE '.$search.')';
			}
			else {
				$search = $this->_db->Quote('%'.$this->_db->escape($search, true).'%');
				$where[] = '(c.title LIKE '.$search.' OR c.alias LIKE '.$search.' OR c.note LIKE '.$search.')';
			}
		}

		if ($query)
			foreach($where as $w) $query->where($w);
		else
			return count($where) ? ' WHERE '.implode(' AND ', $where) : '';
	}


	/**
	 * Method to get item (language) associations
	 *
	 * @param		array   $ids       An array of records is
	 * @param		object  $config    An object with configuration for getting associations
	 *
	 * @return	array   An array with associations of the records list
	 *
	 * @since   3.3.0
	 */
	public function getLangAssocs($ids = null, $config = null)
	{
		$config = $config ?: (object) array(
			'table'    => $this->records_dbtbl,
			'context'  => 'com_categories.item',
			'created'  => 'created_time',
			'modified' => 'modified_time',
		);

		if ($ids)
		{
			return flexicontent_db::getLangAssocs($ids, $config);
		}

		// If items array is empty, just return empty array
		elseif (empty($this->_data))
		{
			return array();
		}

		// Get associated translations
		elseif ($this->_translations === null)
		{
			$ids = array();

			foreach ($this->_data as $item)
			{
				$ids[] = $item->id;
			}

			$this->_translations = flexicontent_db::getLangAssocs($ids, $config);
		}

		return $this->_translations;
	}


	/**
	 * START OF MODEL SPECIFIC METHODS
	 */


	/**
	 * Method to get author list for filtering
	 *
	 * @return array
	 * @since 1.5
	 */
	function getAuthorslist ()
	{
		$query = 'SELECT i.created_by AS id, u.name AS name'
				. ' FROM #__content AS i'
				. ' LEFT JOIN #__users AS u ON u.id = i.created_by'
				. ' GROUP BY i.created_by'
				. ' ORDER BY u.name'
				;
		$this->_db->setQuery($query);

		return $this->_db->loadObjectList();
	}
}
