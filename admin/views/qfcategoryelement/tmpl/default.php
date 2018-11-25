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
$app = JFactory::getApplication();
$option = $app->input->get('option');
global $globalcats;

$useAssocs = flexicontent_db::useAssociations();

$list_total_cols = 7;
if ( $useAssocs ) $list_total_cols++;

$tip_class = FLEXI_J30GE ? ' hasTooltip' : ' hasTip';
$this->tooltip_class = $tip_class;
$btn_class = FLEXI_J30GE ? 'btn' : 'fc_button fcsimple';
?>

<script>
// delete active filter
function delFilter(name)
{
	//if(window.console) window.console.log('Clearing filter:'+name);
	var myForm = jQuery('#adminForm');
	var filter = jQuery('#'+name);
	if (filter.attr('type')=='checkbox')
		filter.checked = '';
	else
		filter.val('');
}

function delAllFilters() {
	delFilter('search'); delFilter('filter_type'); delFilter('filter_state');
	delFilter('filter_cats'); delFilter('filter_author'); delFilter('filter_id');
	delFilter('startdate'); delFilter('enddate'); delFilter('filter_lang');
	delFilter('filter_tag'); delFilter('filter_access');
	delFilter('filter_level');
	jQuery('#filter_subcats').val('1');
	jQuery('.fc_field_filter').val('');
}
</script>

<div id="flexicontent" class="flexicontent">

<form action="index.php?option=com_flexicontent&amp;view=qfcategoryelement&amp;tmpl=component" method="post" name="adminForm" id="adminForm">

	<div id="fc-filters-header">
		<span class="fc-filter nowrap_box">
			<span class="filter-search btn-group">
				<input type="text" name="search" id="search" placeholder="<?php echo JText::_( 'FLEXI_SEARCH' ); ?>" value="<?php echo htmlspecialchars($this->lists['search'], ENT_QUOTES, 'UTF-8'); ?>" class="inputbox" />
			</span>
			<span class="btn-group hidden-phone">
				<button title="<?php echo JText::_('FLEXI_APPLY_FILTERS'); ?>" class="<?php echo $btn_class; ?>" onclick="document.adminForm.limitstart.value=0; Joomla.submitform();"><?php echo FLEXI_J30GE ? '<i class="icon-search"></i>' : JText::_('FLEXI_GO'); ?></button>
				<button title="<?php echo JText::_('FLEXI_RESET_FILTERS'); ?>" class="<?php echo $btn_class; ?>" onclick="document.adminForm.limitstart.value=0; delAllFilters(); Joomla.submitform();"><?php echo FLEXI_J30GE ? '<i class="icon-remove"></i>' : JText::_('FLEXI_CLEAR'); ?></button>
			</span>
		</span>

		<span class="fc-filter nowrap_box">
			<span class="limit nowrap_box" style="display: inline-block;">
				<label class="label">
					<?php echo JText::_('JGLOBAL_DISPLAY_NUM'); ?>
				</label>
				<?php
				$pagination_footer = $this->pagination->getListFooter();
				if (strpos($pagination_footer, '"limit"') === false) echo $this->pagination->getLimitBox();
				?>
			</span>

			<span class="fc_item_total_data nowrap_box badge badge-info">
				<?php echo @$this->resultsCounter ? $this->resultsCounter : $this->pagination->getResultsCounter(); // custom Results Counter ?>
			</span>

			<?php if (($getPagesCounter = $this->pagination->getPagesCounter())): ?>
			<span class="fc_pages_counter nowrap_box fc-mssg-inline fc-info fc-nobgimage">
				<?php echo $getPagesCounter; ?>
			</span>
			<?php endif; ?>
		</span>
	</div>

	<div id="fc-filters-box">
		<span class="fc-filter nowrap_box">
		<?php echo $this->lists['filter_level']; ?>
		</span>

		<span class="fc-filter nowrap_box">
		<?php echo $this->lists['filter_lang']; ?>
		</span>

		<span class="fc-filter nowrap_box">
		<?php echo $this->lists['filter_author']; ?>
		</span>

		<span class="fc-filter nowrap_box">
		<?php echo $this->lists['filter_cats']; ?>
		</span>

		<span class="fc-filter nowrap_box">
		<?php echo $this->lists['filter_state']; ?>
		</span>

		<span class="fc-filter nowrap_box">
		<?php echo $this->lists['filter_access']; ?>
		</span>
	</div>


	<table class="adminlist table fcmanlist">
	<thead>
		<tr>
			<th><?php echo JText::_( 'FLEXI_NUM' ); ?></th>
			<th class="title"><?php echo JHtml::_('grid.sort', 'FLEXI_CATEGORY', 'c.title', $this->lists['order_Dir'], $this->lists['order'] ); ?></th>

		<?php if ($useAssocs) : ?>
			<th class="hideOnDemandClass <?php echo !$this->assocs_id ? 'hidden-phone hidden-tablet' : ''; ?>">
				<?php echo JText::_('FLEXI_ASSOCIATIONS'); ?>
			</th>
		<?php endif; ?>

			<th width="" nowrap="nowrap"><?php echo JHtml::_('grid.sort', 'FLEXI_AUTHOR', 'author', $this->lists['order_Dir'], $this->lists['order'] ); ?></th>
			<th width="" nowrap="nowrap"><?php echo JText::_( 'FLEXI_STATE' ); ?></th>
			<th width=""><?php echo JHtml::_('grid.sort', 'FLEXI_ACCESS', 'c.access', $this->lists['order_Dir'], $this->lists['order'] ); ?></th>
			<th width="" class="nowrap"><?php echo JHtml::_('grid.sort', 'FLEXI_LANGUAGE', 'c.language', $this->lists['order_Dir'], $this->lists['order'] ); ?></th>
			<th width="" class="nowrap"><?php echo JHtml::_('grid.sort', 'FLEXI_ID', 'c.id', $this->lists['order_Dir'], $this->lists['order'] ); ?></th>
		</tr>
	</thead>

	<tbody>
		<?php
		$k = 0;

		// Add 1 collapsed row to the empty table to allow border styling to apply
		if (!count($this->rows))
		{
			echo '<tr class="collapsed_row"><td colspan="'.$list_total_cols.'"></td></tr>';
		}

		foreach ($this->rows as $i => $row)
		{
			$published = JHtml::_('jgrid.published', $row->published, $i, 'categories.', $canChange=0 );
			?>

		<tr class="<?php echo 'row' . ($k % 2); ?>">

			<td>
				<div class="adminlist-table-row"></div>
				<?php echo $this->pagination->getRowOffset( $i ); ?>
			</td>

			<td class="col_title">
				<!--div class="adminlist-table-row"></div-->
				<?php
					$activate_row = !empty($row->is_current_association) || empty($this->lang_assocs[$row->id]);

					$parentcats_ids = isset($globalcats[$row->id]) ? $globalcats[$row->id]->ancestorsarray : array();
					$pcpath = array();

					foreach($parentcats_ids as $pcid)
					{
						$pcpath[] = $globalcats[$pcid]->title;
					}

					$pcpath = implode($pcpath, ' / ');
				?>
				<?php if ($row->level > 1) echo str_repeat('.&nbsp;&nbsp;&nbsp;', $row->level - 1) . '<sup>|_</sup>'; ?>

				<span class="<?php echo $this->tooltip_class; ?>" title="<?php echo JHtml::tooltipText(JText::_('FLEXI_SELECT'), $row->title . '<br/><br/>' . $pcpath, 0, 1); ?>">
					<?php if ($activate_row): ?>
						<a style="cursor: pointer;" href="javascript:;" onclick="window.parent.fcSelectCategory('<?php echo $row->id; ?>', '<?php echo str_replace( array("'", "\""), array("\\'", ""), $row->title ); ?>');" class="<?php echo $tip_class; ?>" title="<?php echo JHtml::tooltipText(JText::_('FLEXI_SELECT'), $pcpath, 0, 1); ?>" >
					<?php else: ?>
						<a style="cursor: default;" href="javascript:;" onclick="var box = jQuery('#assoc_not_allowed_msg'); fc_itemelement_view_handle = fc_showAsDialog(box, 300, 200, null, { title: '<?php echo JText::_('FLEXI_ABOUT', true); ?>'}); return false;">
					<?php endif; ?>
							<?php echo htmlspecialchars($row->title, ENT_QUOTES, 'UTF-8'); ?>
						</a>
				</span>

				<?php
					echo !empty($row->is_current_association) ? ' <span class="label label-association label-warning">' . JText::_('FLEXI_CURRENT') . '</span> ' : '';
				?>
			</td>

			<?php if ($useAssocs) : ?>
			<td class="<?php echo !$this->assocs_id ? 'hidden-phone hidden-tablet' : ''; ?>">
				<?php
				if (!empty($this->lang_assocs[$row->id]))
				{
					$row_modified = strtotime($row->modified_time) ?: strtotime($row->created_time);

					foreach($this->lang_assocs[$row->id] as $assoc_item)
					{
						// Joomla article manager show also current item, so we will not skip it
						$is_current = $assoc_item->id == $row->id;
						$assoc_modified = strtotime($assoc_item->modified) ?: strtotime($assoc_item->created);

						$_title = flexicontent_html::getToolTip(
							($is_current ? '' : JText::_( $assoc_modified < $row_modified ? 'FLEXI_EARLIER_THAN_THIS' : 'FLEXI_LATER_THAN_THIS')),
							( !empty($this->langs->{$assoc_item->lang}) ? ' <img src="'.$this->langs->{$assoc_item->lang}->imgsrc.'" alt="'.$assoc_item->lang.'" /> ' : '').
							($assoc_item->lang === '*' ? JText::_('FLEXI_ALL') : (!empty($this->langs->{$assoc_item->lang}) ? $this->langs->{$assoc_item->lang}->name: '?')).' <br/> '.
							$assoc_item->title, 0, 1
						);

						echo '
						<span class="fc_assoc_translation label label-association ' . $this->tooltip_class . ($assoc_modified < $row_modified ? ' fc_assoc_later_mod' : '').'" title="'.$_title.'" >
							'.($assoc_item->lang=='*' ? JText::_('FLEXI_ALL') : strtoupper($assoc_item->shortcode ?: '?')).'
						</span>';
					}
				}
				?>
			</td>
			<?php endif ; ?>


			<td align="center">
				<?php echo $row->author; ?>
			</td>

			<td align="center">
				<?php echo $published; ?>
			</td>

			<td align="center"><?php echo $row->access_level; ?></td>

			<td align="center" class="col_lang">

				<?php if ( 0 && !empty($row->lang) && !empty($this->langs->{$row->lang}->imgsrc) ) : ?>
					<img class="<?php echo $tip_class; ?>" title="<?php echo flexicontent_html::getToolTip(JText::_( 'FLEXI_LANGUAGE' ), ($row->lang=='*' ? JText::_("All") : (!empty($row->lang) ? $this->langs->{$row->lang}->name : '')), 0, 1); ?>"
						src="<?php echo $this->langs->{$row->lang}->imgsrc; ?>" alt="<?php echo $row->lang; ?>" />
				<?php elseif( !empty($row->lang) ) : ?>
					<?php echo ($row->lang=='*'  ?  JText::_("All")  :  (!empty($row->lang) ? $this->langs->{$row->lang}->name : '')); ?>
				<?php endif; ?>

			</td>

			<td align="center"><?php echo $row->id; ?></td>

		</tr>
		<?php
			$k++;
		}
		?>
	</tbody>

	<tfoot>
		<tr>
			<td colspan="<?php echo $list_total_cols; ?>">
				<?php echo $this->pagination->getListFooter(); ?>
			</td>
		</tr>
	</tfoot>

	</table>
	<input type="hidden" name="task" value="" />
	<input type="hidden" id="filter_order" name="filter_order" value="<?php echo $this->lists['order']; ?>" />
	<input type="hidden" id="filter_order_Dir" name="filter_order_Dir" value="<?php echo $this->lists['order_Dir']; ?>" />
	<input type="hidden" name="fcform" value="1" />
	<?php echo JHtml::_('form.token'); ?>

	<?php echo $this->assocs_id ? '
		<input type="hidden" name="assocs_id" value="'.$this->assocs_id.'" />'
		: ''; ?>
</form>
</div><!-- #flexicontent end -->

<div id="assoc_not_allowed_msg" style="display: none;">
	<?php echo JText::_('FLEXI_ITEM_TRANSLATION_IN_USE'); ?>
</div>