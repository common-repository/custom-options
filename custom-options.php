<?php
/*
Plugin Name: Custom Options
Plugin URI: http://jacobgsp.com/wordpress/
Description: Allows you to create custom options that you can easily update via the `Options` administration panel and also allows you to use mentioned options in your theme using a simple PHP function: `get_custom_option ( $slug [, $default_value, $field ] )`. Very simple, yet efficient.
Version: 1.2
Author: Jacob Guite-St-Pierre
Author URI: http://jacobgsp.com
*/

/*  Copyright 2011  Jacob Guite-St-Pierre  (email : jacob@jacobgsp.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


/*
* Define the array where everything is saved in the database
*/
define('GSP_CUSTOM_OPTIONS_ARRAY', 'gsp_custom_options');

/*
* Actions & Filters
*/
register_activation_hook(__FILE__, 'gsp_custom_options_setup');

add_action('admin_menu',		'gsp_custom_options_menu');

add_filter('contextual_help',	'gsp_custom_options_help');


/*
* Plugin Init
* Here we run functions when the plugin is first initialized.
* @since 091228
*/
function gsp_custom_options_setup() { // init function
	return true; // because I don't need any init... I'm cool like that.
}

/*
* Permission Filter
* Here we add a filter allowing to decide what 
* @since 120213
*/
function gsp_custom_options_default_capability($capability) {
	return 'manage_options';
}
add_filter('gsp_custom_options_capability', 'gsp_custom_options_default_capability');

/*
* Admin menu
* @since 091228
*/
function gsp_custom_options_menu() {
	$capability = apply_filters('gsp_custom_options_capability', 'manage_options');
	add_options_page(__('Options'), __('Options'), $capability, 'custom_options', 'gsp_custom_options_form'); // add ze menu
}

/*
* Contextual Help Text
* @since 091228
*/
function gsp_custom_options_help($text) {
	if (!empty($_GET['page']) && $_GET['page'] == 'custom_options' ) { // this whole part will need updating later…
    	$text = '
		<div class="metabox-prefs">
    		<p>
    			To use a Custom Option, use the function <em>string get_custom_option ( $slug [, $default_value, $field ] )</em> in your theme.<br />
    			The <em>$default_value</em> parameter is optional, but will be used if the specified option cannot be found.<br />
    			The <em>$field</em> parameter is optional, but if specified <em>label</em> it will return the option label.<br />
    			The function returns a string therefore it has to be preceded by an <em>echo</em> in order to be displayed.
    		</p>
    		<p>
    			<strong>Example:</strong><br />
    			Twitter: &#60;?php echo get_custom_option("twitter", "jacobgsp", "value"); ?&#62;
    		</p>
    		<p>
    			To modify the default capability able to use Custom Options, use the filter <em>gsp_custom_options_capability</em> in your theme functions.php file.<br />
    		</p>
    		<p>
    			<strong>Example to allow Editors to use Custom Options:</strong><br />
    			function custom_options_capability($capability) {
				&nbsp;&nbsp;return "publish_pages";
				}
				add_filter("gsp_custom_options_capability", "custom_options_capability");
    		</p>
		</div>';
    }
    return $text;
} 

/*
* Plugin Page
* @since 091228
*/
function gsp_custom_options_form() {

	$options = get_option(GSP_CUSTOM_OPTIONS_ARRAY); // grab the array of options

	/*
	* Page setup
	* Let's use the Settings icon!
	*/
	echo '
		<div class="wrap"><div id="icon-options-general" class="icon32"><br /></div>
		<h2>'.__('Options').'</h2>';
	
	/*
	* Form posted!
	* Time to save the options
	*/	
	if (!empty($_POST) && // check if something was posted
		check_admin_referer('gsp_custom_options_action','gsp_custom_options_nonce_field') && // check the referer
		wp_verify_nonce($_POST['gsp_custom_options_nonce_field'],'gsp_custom_options_action')): // check the nonce
	
		if (!empty($_POST['options'])) // check if options were posted
			$options = $_POST['options']; // let's overwrite the array, this could potentially be a bad idea, let's see later...
		
		if (!empty($_POST['delete_all'])): // check if the delete checkbox was checked
			foreach ($_POST['options'] as $option): // loop through the posted options
				if (!empty($option['delete'])) // check if the option was marked for deletion
					unset($options[$option['slug']]); // remove the option of the array
			endforeach;
		endif;
		
		
		if (!empty($_POST['new_option']['label']) && !empty($_POST['new_option']['slug']) && !empty($_POST['new_option']['value'])): // check if a new option was posted
		
			$_POST['new_option']['slug'] = sanitize_title_with_dashes($_POST['new_option']['slug']); // sanitize the slug the same way permalinks are sanitized
		
			$new_option[$_POST['new_option']['slug']] = $_POST['new_option']; // save the new option to an array
			
			if (empty($options)) // check if no options exist
				$options = $new_option; // make the new option the array
			else
				$options = array_merge($options, $new_option); // if options exist, merge them babies
				
		endif;
		
		asort($options); // sort the array alphabetically and keep the indexes
		
		echo '<div class="updated"><p><strong>'.__('Settings saved.').'</strong></p></div>'; // ouput confirmation on the screen
		
		update_option(GSP_CUSTOM_OPTIONS_ARRAY, $options); // our work is done here gents, let's save the options to the database and get home in time for dinner...

	endif;	
				
	/*
	* Display the form header
	*/
	echo '<form method="post" action="">';
	
	wp_nonce_field('gsp_custom_options_action','gsp_custom_options_nonce_field'); // nounce nounce baby
	
	if (!empty($options)): // display the table only if there are options
	
		$alt = false; // set a boolean for alternate row colors
		
		echo '
			<ul class="subsubsub">
				<li class="all"><a class="current">'.__('All').' <span class="count">('.count($options).')</span></a></li>
			</ul>
			<table class="widefat fixed" cellspacing="0">
			<thead>
			<tr>
			<th scope="col" class="manage-column check-column" width="1"><input type="checkbox" /></th>
			<th scope="col" class="manage-column column-title" width="30%">'.__("Label").'</th>
			<th scope="col" class="manage-column column-title" width="50%">'.__("Value").'</th>
			<th scope="col" class="manage-column column-title" width="20%">'.__("Slug").'</th>
			</tr>
			</thead>
			<tbody>';
		
		/*
		* Loop through the options array
		*/
		foreach ($options as $option):
		
			$alt=!$alt; // sexy alternate row colors
			if ($alt)
				$class=' class="alternate"';
			else
				$class='';
			
			// let's save the title and slug to input hidden fieds that way they can easily be edited via the inspector... Yeah I know... I cheat.  
			echo '
				<tr'.$class.'>
				<th class="check-column">
					<input type="checkbox" name="options['.$option['slug'].'][delete]" />
				</th>
				<td class="post-title">
					<label for="options['.$option['slug'].'][value]" style="display:block;">' . ucfirst($option['label']) . '</label>
					<input type="hidden" name="options['.$option['slug'].'][label]" value="'.$option['label'].'" />
				</td>';
					
				$value = stripslashes($option['value']); // sanitize the value like content
				
				if (strlen($value) > 60 && strpos($value,' ') !== false) // if the value is long enough and contains a space, diaply it in a textarea
					echo '
						<td>
							<textarea name="options['.$option['slug'].'][value]" id="options['.$option['slug'].'][value]" rows="3" style="width: 90%;">'.$value.'</textarea></td>';
				else // if not, use an input text I guess...
					echo '<td><input type="text" name="options['.$option['slug'].'][value]" id="options['.$option['slug'].'][value]" value="'.$value.'" class="regular-text" style="width: 90%;" /></td>';
			echo '
				<td>
					<small>'.$option['slug'].'</small>
					<input type="hidden" name="options['.$option['slug'].'][slug]" value="'.$option['slug'].'" />
				</td>
				</tr>';

		endforeach;
		
		/*
		* Display the form footer
		*/
		echo '
			</tbody>
			<tfoot>
			<tr>
			<th scope="col" class="manage-column check-column" width="1"><input type="checkbox" /></th>
			<th scope="col" class="manage-column column-title" width="30%">'.__("Label").'</th>
			<th scope="col" class="manage-column column-title" width="50%">'.__("Value").'</th>
			<th scope="col" class="manage-column column-title" width="20%">'.__("Slug").'</th>
			</tr>
			</tfoot>
			</table>
			<p style="text-align: right;">
			<small>'.__("Confirm deletion of checked options").'</small> <input type="checkbox" name="delete_all" />
			</p>';
	endif;

	/*
	* New Option
	*/
	echo '
		<h3>'.__('New') . ' ' . __('Option').'</h3>
		<table class="form-table" cellspacing="0">
			<tr valign="top">
				<th scope="row"><label for="new_option[label]">' . __('Label') . '</label></th>
				<td><input type="text" name="new_option[label]" id="new_option[label]" value="" class="regular-text"/></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="new_option[slug]">' . __('Slug') . ' <small><em>(must be unique)</em></small></label></th>
				<td><input type="text" name="new_option[slug]" id="new_option[slug]" value="" class="regular-text"/></td>
			</tr>
			<tr valign="top">
				<th scope="row"><label for="new_option[value]">' . __('Value') . '</label></th>
				<td><textarea name="new_option[value]" id="new_option[value]" rows="3" class="large-text code"></textarea></td>
			</tr>
		</table>';
	
	/*
	* End of form
	*/
	echo '
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button-primary" value="'.__("Save Changes").'" />
		</p>
	</form>
</div>';

}

/*
* Theme Functions
* @since 091228
*/
function get_custom_option($slug, $default='', $field = 'value') {
	$options = get_option(GSP_CUSTOM_OPTIONS_ARRAY); // grab the options array
	if (empty($options[$slug]['value'])): // check if the option exists
		return $default; // return the default value
	else: // At last! The option exists!
		if ($field != 'value' && $field != 'label') // make sure the field asked is valid
			$field = 'value'; // set default to value
		return stripslashes($options[$slug][$field]); // return the sanitized option!
	endif;
}


/*
* What's up?
*/