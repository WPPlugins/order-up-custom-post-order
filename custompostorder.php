<?php
/**
 * @package Order Up!
 */
/*
Plugin Name: Custom Post Order
Plugin URI: http://drewgourley.com/order-up-custom-ordering-for-wordpress/
Description: Allows for the ordering of posts and custom post types through a simple drag-and-drop interface.
Version: 2.0
Author: Drew Gourley
Author URI: http://drewgourley.com/
License: GPLv2 or later
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

$custompostorder_defaults = array('post' => 0);
$args = array( 'public' => true, '_builtin' => false ); 
$output = 'objects';
$post_types = get_post_types( $args, $output );
foreach ( $post_types as $post_type ) {
	$custompostorder_defaults[$post_type->name] = 0;
}
$custompostorder_defaults = apply_filters('custompostorder_defaults', $custompostorder_defaults);
$custompostorder_settings = get_option('custompostorder_settings');
$custompostorder_settings = wp_parse_args($custompostorder_settings, $custompostorder_defaults);
add_action('admin_init', 'custompostorder_register_settings');
function custompostorder_register_settings() {
	register_setting('custompostorder_settings', 'custompostorder_settings', 'custompostorder_settings_validate');
}
function custompostorder_update_settings() {
	global $custompostorder_settings;
	if(isset($custompostorder_settings['update'])) {
		echo '<div class="updated fade" id="message"><p>Custom Post Order settings '.$custompostorder_settings['update'].'.</p></div>';
		unset($custompostorder_settings['update']);
		update_option('custompostorder_settings', $custompostorder_settings);
	}
}
function custompostorder_settings_validate($input) {
	$input['post'] = ($input['post'] == 1 ? 1 : 0);
	$args = array( 'public' => true, '_builtin' => false );
	$output = 'objects';
	$post_types = get_post_types( $args, $output );
	foreach ( $post_types as $post_type ) {
		$input[$post_type->name] = ($input[$post_type->name] == 1 ? 1 : 0);
	}
	return $input;
}

function custompostorder_menu() {    
	$args = array( 'public' => true, '_builtin' => false ); 
	$output = 'objects';
	$post_types = get_post_types( $args, $output );
	add_menu_page(__('Post Order'),  __('Post Order'), 'edit_posts', 'custompostorder', 'custompostorder', plugins_url('images/post_order.png', __FILE__), 120); 
	add_submenu_page('custompostorder', __('Order Posts'), __('Order Posts'), 'edit_posts', "custompostorder", 'custompostorder'); 
	foreach ( $post_types as $post_type ) {
		add_submenu_page('edit.php?post_type='.$post_type->name, __('Order '.$post_type->label), __('Order '.$post_type->label), 'edit_posts', 'custompostorder-'.$post_type->name, 'custompostorder'); 
		add_submenu_page('custompostorder', __('Order '.$post_type->label), __('Order '.$post_type->label), 'edit_posts', 'custompostorder-'.$post_type->name, 'custompostorder'); 
	}
	add_posts_page(__('Order Posts', 'custompostorder'), __('Order Posts', 'custompostorder'), 'edit_posts', 'custompostorder', 'custompostorder');
}
function custompostorder_css() {
	$pos_page = $_GET['page'];
	$pos_args = 'custompostorder';
	$pos = strpos( $pos_page, $pos_args );
	if ( $pos !== false ) {
		wp_enqueue_style('custompostorder', plugins_url('css/custompostorder.css', __FILE__), 'screen');
	}
}
function custompostorder_js_libs() {
	$pos_page = $_GET['page'];
	$pos_args = 'custompostorder';
	$pos = strpos( $pos_page, $pos_args );
	if ( $pos !== false ) {
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-sortable');
	}
}
add_action('admin_menu', 'custompostorder_menu');
add_action('admin_menu', 'custompostorder_css');
add_action('admin_print_scripts', 'custompostorder_js_libs');

function custompostorder() {
	global $custompostorder_settings;
	$options = $custompostorder_settings;
	$settings = '';
	$parent_ID = 0;
	if ( $_GET['page'] == 'custompostorder' ) { 
		$args = array( 'public' => true, '_builtin' => false ); 
		$output = 'objects';
		$post_types = get_post_types( $args, $output );
		foreach ( $post_types as $post_type ) {
			$settings .= '<input name="custompostorder_settings[' . $post_type->name . ']" type="hidden" value="' . $options[$post_type->name] . '" />';
		}
		$settings .= '<input name="custompostorder_settings[post]" type="checkbox" value="1" ' . checked('1', $options['post'], false) . ' /> <label for="custompostorder_settings[post]">Check this box if you want to enable Automatic Sorting of all queries from this post type.</label>';
		$type_label = 'Posts';
		$type = 'post';
	} else {
		$args = array( 'public' => true, '_builtin' => false ); 
		$output = 'objects';
		$post_types = get_post_types( $args, $output );
		foreach ( $post_types as $post_type ) {
			$com_page = 'custompostorder-'.$post_type->name;
			if ( $_GET['page'] == $com_page ) { 
				$settings .= '<input name="custompostorder_settings[' . $post_type->name . ']" type="checkbox" value="1" ' . checked('1', $options[$post_type->name], false) . ' /> <label for="custompostorder_settings[' . $post_type->name . ']">Check this box if you want to enable Automatic Sorting of all queries from this post type.</label>';
				$type_label = $post_type->label;
				$type = $post_type->name;
			} else {
				$settings .= '<input name="custompostorder_settings[' . $post_type->name . ']" type="hidden" value="' . $options[$post_type->name] . '" />';
			}
		}
		$settings .= '<input name="custompostorder_settings[post]" type="hidden" value="' . $options['post'] . '" />';
	}
	if (isset($_POST['go-sub-posts'])) { 
		$parent_ID = $_POST['sub-posts'];
	}
	elseif (isset($_POST['hidden-parent-id'])) { 
		$parent_ID = $_POST['hidden-parent-id'];
	}
	if (isset($_POST['return-sub-posts'])) { 
		$parent_post = get_post($_POST['hidden-parent-id']);
		$parent_ID = $parent_post->post_parent;
	}
	$message = "";
	if (isset($_POST['order-submit'])) { 
		custompostorder_update_order();
	}
	custompostorder_update_settings();
?>
<div class='wrap'>
	<?php screen_icon('custompostorder'); ?>
	<h2><?php _e('Order ' . $type_label, 'custompostorder'); ?></h2>
	<form name="custom-order-form" method="post" action="">
		<?php $args = array(
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'post_parent' => $parent_ID, 
			'post_type' => $type,
			'posts_per_page' => get_option('posts_per_page'),
			'paged' => max( 1, $_GET['paged'] )
			);
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) { ?>
		<div id="poststuff" class="metabox-holder">
			<div class="widget order-widget">
				<h3 class="widget-top"><?php _e( $type_label, 'custompostorder') ?> | <small><?php _e('Order the ' . $type_label . ' by dragging and dropping them into the desired order.', 'custompostorder') ?></small></h3>
				<div class="misc-pub-section">
					<ul id="custom-order-list">
						<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<li id="id_<?php the_ID(); ?>" class="lineitem"><?php the_title(); ?></li>
						<?php endwhile; ?>
					</ul>
				</div>
				<?php $big = 32768;
				$args = array(
					'base' => str_replace( $big, '%#%', get_pagenum_link( $big ) ),
					'format' => '?paged=%#%',
					'prev_next' => false,
					'current' => max( 1, $_GET['paged'] ),
					'total' => $query->max_num_pages
					); 
				$pagination = paginate_links($args); 
				if ( !empty($pagination) ) { ?>
				<div class="misc-pub-section">
					<div class="tablenav" style="margin:0">
						<div class="tablenav-pages">
							<span class="pagination-links"><?php echo $pagination; ?></span>
						</div>
					</div>
				</div>
				<?php } ?>
				<div class="misc-pub-section misc-pub-section-last">
					<?php if ($parent_ID != 0) { ?>
						<input type="submit" class="button" style="float:left" id="return-sub-posts" name="return-sub-posts" value="<?php _e('Return to Parent Page', 'custompostorder'); ?>" />
					<?php } ?>
					<div id="publishing-action">
						<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" id="custom-loading" style="display:none" alt="" />
						<input type="submit" name="order-submit" id="order-submit" class="button-primary" value="<?php _e('Update Page Order', 'custompostorder') ?>" />
					</div>
					<div class="clear"></div>
					</div>
				<input type="hidden" id="hidden-custom-order" name="hidden-custom-order" />
				<input type="hidden" id="hidden-parent-id" name="hidden-parent-id" value="<?php echo $parent_ID; ?>" />
			</div>
			<?php $options = custompostorder_sub_query( $query, $type ); if( !empty($options) ) { ?>
			<div class="widget order-widget">
				<h3 class="widget-top"><?php _e('Sub-' . $type_label, 'custompostorder'); ?> | <small><?php _e('Choose a ' . $type_label . ' from the drop down to order its sub-posts.', 'custompostorder'); ?></small></h3>
				<div class="misc-pub-section misc-pub-section-last">
					<select id="sub-posts" name="sub-posts">
						<?php echo $options; ?>
					</select>
					<input type="submit" name="go-sub-posts" class="button" id="go-sub-posts" value="<?php _e('Order Sub-posts', 'custompostorder') ?>" />
				</div>
			</div>		
			<?php } ?>
		</div>
		<?php } else { ?>
		<p><?php _e('No posts found', 'customtaxorder'); ?></p>
		<?php } ?>
	</form>
	<form method="post" action="options.php">
		<?php settings_fields('custompostorder_settings'); ?>
		<table class="form-table">
			<tr valign="top"><th scope="row">Auto-Sort Queries</th>
			<td><?php echo $settings; ?></td>
			</tr>
		</table>
		<input type="hidden" name="custompostorder_settings[update]" value="Updated" />
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Settings') ?>" />
		</p>
	</form>
</div>
<?php if ( $query->have_posts() ) { ?>
<script type="text/javascript">
// <![CDATA[
	jQuery(document).ready(function($) {
		$("#custom-loading").hide();
		$("#order-submit").click(function() {
			orderSubmit();
		});
	});
	function custompostorderAddLoadEvent(){
		jQuery("#custom-order-list").sortable({ 
			placeholder: "sortable-placeholder", 
			revert: false,
			tolerance: "pointer" 
		});
	};
	addLoadEvent(custompostorderAddLoadEvent);
	function orderSubmit() {
		var newOrder = jQuery("#custom-order-list").sortable("toArray");
		jQuery("#custom-loading").show();
		jQuery("#hidden-custom-order").val(newOrder);
		return true;
	}
// ]]>
</script>
<?php }
}

function custompostorder_update_order() {
	if (isset($_POST['hidden-custom-order']) && $_POST['hidden-custom-order'] != "") { 
		global $wpdb;
		$offset = ( max( 1, $_GET['paged'] ) - 1 ) * get_option('posts_per_page');
		$new_order = $_POST['hidden-custom-order'];
		$IDs = explode(",", $new_order);
		$result = count($IDs);
		for($i = 0; $i < $result; $i++)	{
			$str = str_replace("id_", "", $IDs[$i]);
			$order = $i + $offset;
			$update = array('ID' => $str, 'menu_order' => $order);
			wp_update_post( $update );
		}
		echo '<div id="message" class="updated fade"><p>'. __('Post order updated successfully.', 'custompostorder').'</p></div>';
	} else {
		echo '<div id="message" class="error fade"><p>'. __('An error occured, order has not been saved.', 'custompostorder').'</p></div>';
	}
}
function custompostorder_sub_query( $query, $type ) {
	$options = '';
	while ( $query->have_posts() ) : $query->the_post(); $page_ID = get_the_ID(); $args = array( 'post_parent' => $page_ID, 'post_type' => $type ); $subpages = new WP_Query( $args );
		if ( $subpages->have_posts() ) { 
			while ( $subpages->have_posts() ) : $subpages->the_post(); $options .= '<option value="' . $page_ID . '">' . get_the_title($page_ID) . '</option>'; endwhile; 
		} 
	endwhile;
	return $options;
}

function custompostorder_order_posts($orderby) {
	global $wpdb;
	$orderby = "$wpdb->posts.menu_order ASC";
	return $orderby;
}
function custompostorder_sort( $query ) {
	global $custompostorder_settings;
	$options = $custompostorder_settings;
	$post_type = $query->query_vars['post_type'];
	if ( empty( $post_type ) ) { $post_type = 'post'; }
	if ( $options[$post_type] == 1 && !isset($_GET['orderby']) ) {
		add_filter('posts_orderby', 'custompostorder_order_posts');
	}
}
add_action('pre_get_posts', 'custompostorder_sort');
?>