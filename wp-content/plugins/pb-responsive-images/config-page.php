<?php

//check permissions
if ( !current_user_can('manage_options') )
	die(__('Cheatin&#8217; uh?'));

$options = RIPOptions::options();

$format_src = '
	<p>
		<label for="rip::format::%1$s::media">%2$s</label>
		<input class="media" type="text" name="rip::format::%1$s::media" id="rip::format::%1$s::media" value="%5$s"/>

		<span>
		<label for="rip::format::%1$s::query">%3$s</label>
		<input type="text" name="rip::format::%1$s::query" id="rip::format::%1$s::query" value="%6$s"/>
		</span>

		<span>
		<input type="checkbox" name="rip::format::%1$s::fallback" id="rip::format::%1$s::fallback" %7$s />
		<label for="rip::format::%1$s::fallback">%4$s</label>
		</span>

		<span>
		<input type="hidden" name="rip::format::%1$s::order" id="rip::format::%1$s::order" value="%1$s" />
		<input type="hidden" name="rip::format::%1$s::trashed" id="rip::format::%1$s::trashed" value="" />
		</span>

		<a href="#delete" class="delete"></a>
	</p>';
?>

<style>
#slir-options{width:50%}
#slir-options th{text-align:left}

.pb-header{background:url(../wp-content/plugins/pb-responsive-images/images/logo.png) no-repeat 95% 50%,url(../wp-content/plugins/pb-responsive-images/images/bg-head.png) no-repeat 100% 0;background-color:#DCEDF3;margin: 50px 0 30px;box-shadow: 0 0 20px rgba(0, 0, 0, .4) inset;padding: 25px 30px 30px;position:relative}
#setting-error-settings_updated{position: absolute;bottom: 100%;left: 0;margin: 0 0 5px; width:50%;}
.rip-intro{margin: 0;border-bottom: 1px dashed #999;padding: 0 0 20px;}
.rip-form h3{margin-top:30px}
.formats{width:auto}
.formats p{border-top:1px solid #EEE;border-bottom:1px solid #EEE;background:#FFF;cursor:move;position:relative;margin:0 0 -1px;padding:5px 20px 5px 5px}
.formats p span{display:inline-block;vertical-align:middle}
.formats input[type=text]{margin:0 10px 0 5px}
.formats input[type=checkbox]{margin:0 0 0 0}
.formats input.media{width:40%}
.formats .delete{display:block;width:10px;height:10px;background:url(images/xit.gif) no-repeat 0 0;position:absolute;top:50%;right:5px;margin:-5px 0 0}
.formats .delete:hover{background-position:100% 0}
.options input[type=text]{width:50%}

#tab-panel-rip_additional_functions ul{list-style:none;margin:10px 0;}
#tab-panel-rip_additional_functions li{padding:10px 0;list-style:none;margin:0;}
#tab-panel-rip_additional_functions small{display:block;margin:0 0 10px;color:#666;}
#tab-panel-rip_additional_functions pre{border:1px solid #eee;padding:5px 10px;}
</style>

<script type="text/javascript">
	jQuery(function($){
		var format_html = '<?php echo str_replace("\n","",sprintf($format_src,'{index}',_('Media'),_('Query'),_('Fallback Image'),'','','')); ?>';

		$('#setting-error-settings_updated').delay(1500).slideUp(400);

		$("#formats .sortable" )
			.sortable({ containment: 'parent' })
			.bind("sortupdate", function(event, ui) {
				$('p',this).each(function(index){
					$('input[type="hidden"]',this).val(index);
				});
			});

		$('#rip-config .add').on('click',function(){
			var index = $('#formats .sortable p').length,
				$format = $(format_html.replace(/{index}/g,index)).appendTo('#formats .sortable');

			return false;
		});

		$('input[value="Restore Defaults"]').click(function(){
			if(!confirm('Restoring defaults will discard all changes, and is non-reversable. Are you sure?'))
				return false;
		});

		$(document).on('click','#formats .delete',function(){
			$p = $(this).parents('p');
			$p.hide();
			$('input[name$="trashed"]',$p).val('on');
			return false;
		}).on('change','#formats input[name$="fallback"]',function(){
			if($(this).is(':checked'))
				$('#formats input[name$="fallback"]').not(this).attr('checked',false);
		});
	});
</script>

<div class="wrap">
    <div class="pb-header">
		<div class="icon32" id="icon-options-general"><br></div>
	    <h2><?php _e('PB Responsive Images'); ?></h2>
	</div>

    <form action="options.php" method="post" id="rip-config" class="rip-form">
        <input type="hidden" name="rip_submit" value="true" />
        <input type="hidden" name="<?php echo RIPConfig::$rip_nonce; ?>" id="<?php echo RIPConfig::$rip_nonce; ?>" value="<?php echo wp_create_nonce( plugin_basename(RIP_CONFIG) ); ?>" />
        
        <p class="rip-intro">The PB Responsive Images Polyfill automatically reformats all images in the post content into the picture tag proposed by the
        	<a href="http://www.w3.org/community/respimg/" target="_blank">Responsive Images Community Group on w3.org</a>.
        	For help on implementing this plugin, use the "Help" tab on the upper right, or post your questions to
        	<a href="http://wordpress.org/support/plugin/pb-responsive-images" target="_blank">the forums on wordpress.org</a>.</p>

    	<h3><?php _e('Formats'); ?></h3>
    	<p>
    		<?php _e('Formats are applied in descending order - the last viable media query is the one selected. Order is important, similar to CSS.'); ?><br>
    		<?php _e('Additionally, one image is noted as the "Fallback Image". This is the image that is loaded when JavaScript is disabled.'); ?></p>

        <fieldset id="formats" class="formats">
            <div class="sortable">
				<?php
                $formats = $options->formats;

                foreach($formats as $index => $format){
                    $format->media = $options->filterBrowserPrefixesOut($format->media,array('webkit','moz','o'),'min-device-pixel-ratio');
                	
                    printf($format_src,$index,
                		_('Media'),_('Query'),_('Fallback Image'),
                		$format->media,$format->query,(isset($format->fallback) && $format->fallback) ? 'checked="checked"' : ''
                	);
				} //foreach ?>
            </div>
        </fieldset>
        <p><a class="button-secondary add">New Format</a></p>

    	<h3><?php _e('Advanced Options'); ?></h3>
        <fieldset id="options" class="options">
            <p>
				<?php printf('<input type="checkbox" name="rip::enable_scripts" id="rip::enable_scripts" %1$s />',
                	($options->enable_scripts === true) ? 'checked="checked"' : ''); ?>
                <label for="rip::enable_scripts">
                	<strong><?php _e('Embed plugin scripts in the header.'); ?></strong>
                	<?php printf(__('You will need to provide them yourself, if unchecked. The linked scripts are <a href="%1$s" target="_blank">Matchmedia</a> and <a href="%2$s" target="_blank">Picturefill</a>.'),
                		plugins_url('scripts/matchmedia.js',RIP_FILE),
                		plugins_url('scripts/picturefill.js',RIP_FILE)
                		); ?>
                </label>
            </p>
            <p>
				<?php printf('<input type="checkbox" name="rip::disable_wordpress_resize" id="rip::disable_wordpress_resize" %1$s />',
                	($options->disable_wordpress_resize === true) ? 'checked="checked"' : ''); ?>
                <label for="rip::disable_wordpress_resize">
                	<strong><?php _e('Disable WordPress automatic image resizing.'); ?></strong>
                	<?php _e('With this checked, after upload, only full-size images can be inserted into posts. Recommended if this plugin is properly configured, to insure SLIR will work with the highest resolution images.'); ?>
                </label>
            </p>
        </fieldset>

    	<h3><?php _e('SLIR Base URL'); ?></h3>
        <fieldset id="rewrite" class="options">
        	<p><label for="rip::slir_base"><?php _e('The base URL for SLIR. This plugin will attempt to use mod_rewrite to set this automatically. Otherwise, you\'ll need to set this manually using your preferred rewrite method.'); ?></label></p>
            <p>
                <?php printf('<input type="text" name="rip::slir_base" id="rip::slir_base" value="%1$s" />',
                	$options->slir_base); ?>
            </p>
        </fieldset>
        
        <div id="rip-actions">
    		<input type="submit" name="submit" value="Restore Defaults" accesskey="r" class="button-secondary">
        	<input type="submit" name="submit" value="Save Changes" accesskey="s" class="button-primary">
        </div>
    </form>
</div>