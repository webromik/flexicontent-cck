<?php  // *** DO NOT EDIT THIS FILE, DO NOT CREATE A COPY !! -- Galleriffic JS will be removed / replaced

/**
 * (Inline) Gallery layout  --  Galleriffic
 *
 * This layout does not support inline_info, pretext, posttext
 */


// ***
// *** Values loop
// ***

$i = -1;
foreach ($values as $n => $value)
{
	// Include common layout code for preparing values, but you may copy here to customize
	$result = include( JPATH_ROOT . '/plugins/flexicontent_fields/image/tmpl_common/prepare_value_display.php' );
	if ($result === _FC_CONTINUE_) continue;
	if ($result === _FC_BREAK_) break;

	$group_str = '';   // image grouping: not needed / not applicatble
	$field->{$prop}[] =
		'<a href="'.$srcl.'" class="fc_image_thumb thumb" name="drop">
			'.$img_legend.'
		</a>
		<div class="caption">
			<b>'.$title.'</b>
			<br/>'.$desc.'
		</div>';
}



// ***
// *** Add per field custom JS
// ***

if ( !isset(static::$js_added[$field->id][__FILE__]) )
{
	flexicontent_html::loadFramework('galleriffic');

	$js = "
	//document.write('<style>.noscript { display: none; }</style>');
	jQuery(document).ready(function() {
		// We only want these styles applied when javascript is enabled
		jQuery('div.navigation').css({'width' : '150px', 'float' : 'left'});
		jQuery('div.content').css({'display' : 'inline-block', 'float' : 'none'});

		// Initially set opacity on thumbs and add
		// additional styling for hover effect on thumbs
		var onMouseOutOpacity = 0.67;
		jQuery('#gf_thumbs ul.thumbs li').opacityrollover({
			mouseOutOpacity:   onMouseOutOpacity,
			mouseOverOpacity:  1.0,
			fadeSpeed:         'fast',
			exemptionSelector: '.selected'
		});

		// Initialize Advanced Galleriffic Gallery
		jQuery('#gf_thumbs').galleriffic({
			/*enableFancybox: true,*/
			delay:                     2500,
			numThumbs:                 4,
			preloadAhead:              10,
			enableTopPager:            true,
			enableBottomPager:         true,
			maxPagesToShow:            20,
			imageContainerSel:         '#gf_slideshow',
			controlsContainerSel:      '#gf_controls',
			captionContainerSel:       '#gf_caption',
			loadingContainerSel:       '#gf_loading',
			renderSSControls:          true,
			renderNavControls:         true,
			playLinkText:              'Play Slideshow',
			pauseLinkText:             'Pause Slideshow',
			prevLinkText:              '&lsaquo; Previous Photo',
			nextLinkText:              'Next Photo &rsaquo;',
			nextPageLinkText:          'Next &rsaquo;',
			prevPageLinkText:          '&lsaquo; Prev',
			enableHistory:             false,
			autoStart:                 false,
			syncTransitions:           true,
			defaultTransitionDuration: 900,
			onSlideChange:             function(prevIndex, nextIndex) {
				// 'this' refers to the gallery, which is an extension of jQuery('#gf_thumbs')
				this.find('ul.thumbs').children()
					.eq(prevIndex).fadeTo('fast', onMouseOutOpacity).end()
					.eq(nextIndex).fadeTo('fast', 1.0);
			},
			onPageTransitionOut:       function(callback) {
				this.fadeTo('fast', 0.0, callback);
			},
			onPageTransitionIn:        function() {
				this.fadeTo('fast', 1.0);
			}
		});
	});
	";

	if ($js) JFactory::getDocument()->addScriptDeclaration($js);

	static::$js_added[$field->id][__FILE__] = true;
}



/**
 * Include common layout code before finalize values
 */

$result = include( JPATH_ROOT . '/plugins/flexicontent_fields/image/tmpl_common/before_values_finalize.php' );
if ($result !== _FC_RETURN_)
{
	// ***
	// *** Add container HTML (if required by current layout) and add value separator (if supported by current layout), then finally apply open/close tags
	// ***

	// Add container HTML
	$field->{$prop} = '
	<div id="gf_container">
		<div id="gallery" class="content">
			<div id="gf_controls" class="controls"></div>
			<div class="slideshow-container">
				<div id="gf_loading" class="loader"></div>
				<div id="gf_slideshow" class="slideshow"></div>
			</div>
			<div id="gf_caption" class="caption-container"></div>
		</div>
		<div id="gf_thumbs" class="navigation">
			<ul class="thumbs noscript">
				<li>
				'. implode("</li>\n<li>", $field->{$prop}) .'
				</li>
			</ul>
		</div>
		<div style="clear: both;"></div>
	</div>
	';

	// Apply open/close tags
	$field->{$prop}  = $opentag . $field->{$prop} . $closetag;
}
