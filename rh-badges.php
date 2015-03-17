<?php
/*
Plugin Name: Pegleg Badges
Version: 1.2.2
Description: A Stack Overflow inspired plugin which allows users to acquire badges. Badges are created and managed through the standard WordPress Dashboard.
Author: B. Jordan
Author URI: http://pegleg.com.au

Copyright (c) 2009
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt

    This file is part of WordPress.
    WordPress is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Change Log
$current_version = array('1.2.2');

// Database schema version
global $rhb_db_version;
$rhb_db_version = "1.0";

// Install the plugin.
function rhb_installation() {

	global $wpdb;

	// Create the rh_badges table.

	$table_name = $wpdb->prefix . "rh_badges";
	
	if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
	
		$sql = "CREATE TABLE " . $table_name . " (
			badge_id int(9) PRIMARY KEY AUTO_INCREMENT,
			description text NOT NULL,
		  	name varchar(255) NOT NULL,
		  	type varchar(10) NOT NULL
		);";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
	// Create the rh_badge_conditions table.
	
	$table_name = $wpdb->prefix . "rh_badge_conditions";
	
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
	
		$sql = "CREATE TABLE " . $table_name . " (
			badge_id int(9) NOT NULL,
			badge_condition_id int(9) PRIMARY KEY AUTO_INCREMENT,
		  	object_type varchar(25) NOT NULL,
		  	value varchar(255) NOT NULL,
		  	count int(9) NOT NULL
		);";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
	// Create the rh_user_badges table.
	
	$table_name = $wpdb->prefix . "rh_user_badges";
	
	if($wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
	
		$sql = "CREATE TABLE " . $table_name . " (
			badge_id int(9) NOT NULL,
		  	user_id int(9) NOT NULL,
                        time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        CONSTRAINT rhub_pk UNIQUE KEY (badge_id, user_id)
		);";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
 
	add_option("rhb_db_version", $rhb_db_version);
}


// Hook for registering the install function upon plugin activation.
register_activation_hook(__FILE__,'rhb_installation');

// Add the badge count to the Dashboard landing page.
add_action('right_now_content_table_end', 'rhb_add_badge_counts');

// Check for new badges after a post is published.
add_action('publish_post', 'rhb_check_author');

// Check for new badges after a comment is published.
add_action('comment_post', 'rhb_check_current_user');

function rhb_add_badge_counts() {
		
	$num = intval( rhb_count_badges() );
		
        $text = _n( 'Badge', 'Badges', $num );
		
        if ( current_user_can( 'edit_posts' ) ) {
            $num = "<a href='edit.php?page=badges'>$num</a>";
            $text = "<a href='edit.php?page=badges'>$text</a>";
        }
        
        echo '<td class="first b b-badges">' . $num . '</td>';
        echo '<td class="t badges">' . $text . '</td>';

        echo '</tr>';
}

function rhb_count_badges() {

	global $wpdb;
	
	$badge_count= $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'rh_badges', NULL));
	
	return $badge_count;
}

function rhb_get_badges( $filter = '' ) {

	global $wpdb;
	
	if ( empty($filter ) ) { $filter = array(); }
	
	// Select all rows by default
	$sql = 'SELECT badge_id, name, description, type FROM ' . $wpdb->prefix . 'rh_badges b WHERE 1=1 ';
		
	// If a user ID was entered.
	if ( array_key_exists('user_ID', $filter) ) {
			
		$user_ID = $filter['user_ID'];
			
		// Join the rh_user_badges table.
		$sql = 'SELECT b.badge_id, b.name, b.description, b.type
				FROM ' . $wpdb->prefix . 'rh_badges b, 
					' . $wpdb->prefix . 'rh_user_badges ub
				WHERE b.badge_id = ub.badge_id
				AND ub.user_id = ' . $user_ID;

	}

	// If a badge ID was entered.
	if ( array_key_exists('badge_ID', $filter) ) {
			
		$badge_ID = $filter['badge_ID'];
			
		// Append a WHERE clause to the SQL.
		$sql .= " AND b.badge_id = $badge_ID";
	}
    		    		
	$badges = $wpdb->get_results( $sql );
		
	return $badges;
}

function rhb_list_badges( $filter = '' ) {

	if ( empty($filter ) ) { $filter = array(); }

	print '<div id="badge-table">
		<table>
			<tbody>';
			
			foreach (rhb_get_badges( $filter ) as $badge) {
			
			print '<tr>
				<td class="badge-cell">
					<a class="badge">
					<span class="';

					if ( 'gold' == $badge->type )
						echo 'badge3';
					elseif ( 'silver' == $badge->type )
						echo 'badge2';
					elseif ( 'bronze' == $badge->type )
						echo 'badge1';
					
					print '"></span>
						' . $badge->name . '
						</a>
					</td>
					<td>' . $badge->description . '</td>
				</tr>';
			}
		print '</tbody>
	</table>
	</div>';
}

function rhb_list_recent_badges() {

	print '<div id="recent-badges-table">
		<table>
			<tbody>';
	
			foreach (rhb_get_recent_badges() as $user_badge) {
			
			print '<tr>
				<td class="badge-cell">
					<a class="badge">
					<span class="';

					if ( 'gold' == $user_badge->type )
						echo 'badge3';
					elseif ( 'silver' == $user_badge->type )
						echo 'badge2';
					elseif ( 'bronze' == $user_badge->type )
						echo 'badge1';
					
					print '"></span>
						' . $user_badge->name . '
						</a>
					</td>
					<td>' . $user_badge->user_nicename. '</td>
				</tr>';
			}
		print '</tbody>
	</table>
	</div>';
}

function rhb_get_recent_badges() {
	
	global $wpdb;

	if ( empty($filter ) ) { $filter = array(); }
	$limit = ( isset( $filter['limit'] ) ? $filter['limit'] : 10 );

	$sql = 'SELECT u.id user_id, 
		u.user_nicename, 
		b.badge_id, 
		b.name, 
		b.type, 
		b.description
	FROM ' . $wpdb->prefix . 'rh_user_badges ub,
		' . $wpdb->prefix . 'users u,
		' . $wpdb->prefix . 'rh_badges b
	WHERE ub.badge_id = b.badge_id
		AND ub.user_id = u.id
	ORDER BY ub.time DESC
	LIMIT 0, ' . $limit;

	$recent_badges = $wpdb->get_results( $wpdb->prepare( $sql, NULL ) );

	return $recent_badges;
}

function rhb_get_badge_conditions( $filter = '' ) {

	global $wpdb;
	
	if ( empty( $filter ) ) { $filter = array(); }
	
	$sql = 'SELECT badge_condition_id, badge_id, object_type, value, count FROM ' . $wpdb->prefix . 'rh_badge_conditions';
	
	// If a badge ID was entered.
	if ( array_key_exists( 'badge_ID', $filter ) ) {
			
		$badge_ID = $filter['badge_ID'];
			
		// Append a WHERE clause to the SQL.
		$sql .= " WHERE badge_id = $badge_ID";

	}

	$badge_conditions = $wpdb->get_results($sql);
		
	return $badge_conditions;
}

function rhb_add_badge( $args = '' ) { 

	global $wpdb;
			
	$wpdb->insert( $wpdb->prefix . 'rh_badges', 
		array( 'name' => $args['name'],
			'description' => $args['description'],
			'type' => $args['type']), 
		array( '%s', '%s', '%s' ) );
}

function rhb_add_badge_condition( $args = '' ) {

	global $wpdb;
	
	$wpdb->insert( $wpdb->prefix . 'rh_badge_conditions', 
		array('badge_id' => $args['badge_ID'],
			'object_type' => $args['object_type'],
			'value' => $args['value'],
			'count' => $args['count']), 
		array( '%d', '%s', '%s', '%d' ) );
}

function rhb_remove_badge( $args = '' ) {

	global $wpdb;
	
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'rh_user_badges' . ' WHERE badge_id = %d', $args['badge_ID'] ) );

	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'rh_badge_conditions' . ' WHERE badge_id = %d', $args['badge_ID'] ) );

	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'rh_badges' . ' WHERE badge_id = %d', $args['badge_ID'] ) );
}

function rhb_update_badge($args = '') {

	global $wpdb;

	$wpdb->update( $wpdb->prefix . 'rh_badges', 
		array( 'name' => $args['name'], 
			'description' => $args['description'],
			'type' => $args['type'] ),
		array( 'badge_id' => $args['badge_ID'] ),
		array( '%s', '%s', '%s' ),
		array( '%d' )
		);
}

function rhb_remove_badge_condition( $args = '' ) {

	global $wpdb;

	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'rh_badge_conditions' . ' WHERE badge_condition_id = %d', $args['badge_condition_ID'] ) );

}

function rhb_get_user_comment_count( $args = '' ) {

	global $wpdb;

	$comment_count = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) 
		FROM " . $wpdb->prefix . "comments
		WHERE user_id = " . $args['user_ID'] . "
		AND comment_approved = '1'" , NULL) );

	return $comment_count;
}

function rhb_get_user_post_count( $args = '' ) {

	global $wpdb;

	$post_count = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) 
		FROM " . $wpdb->prefix . "posts
		WHERE post_author = " . $args['user_ID'] . "
		AND post_status = 'publish'
		AND post_type = 'post'" , NULL) );

	return $post_count;
}

function rhb_award_badge( $args = '' ) { 

	global $wpdb;
			
	$wpdb->insert( $wpdb->prefix . 'rh_user_badges', 
		array('badge_id' => $args['badge_ID'],
			'user_id' => $args['user_ID']), 
		array( '%d','%d' ) );
}

function rhb_revoke_badge( $args = '' ) { 

	global $wpdb;
	
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'rh_user_badges' . ' WHERE user_id = %d AND badge_id = %d', $args['user_ID'], $args['badge_ID'] ) );
}

function rhb_check_current_user() {

	global $current_user;
	get_currentuserinfo();

	$args = array('user_ID' => $current_user->ID);
	rhb_check_user_badges( $args );
}

function rhb_check_author() { 
	
	global $post;

	$args = array('user_ID' => $post->post_author);
	rhb_check_user_badges( $args );
}

// Check whether an individual user has achieved any badges
function rhb_check_user_badges( $args = '' ) { 
	
	// Loop through each badge.
	foreach ( rhb_get_badges() as $badge ) {

		$award_badge = true;

		$filter = array( 'badge_ID' => $badge->badge_id );

		// Loop through each badge condition.
		foreach( rhb_get_badge_conditions( $filter ) as $badge_condition ) {

			$condition_met = false;

			// Check the condition type
			switch( $badge_condition->object_type ) {

				case 'post_tag':
					
					// Get the user's count for each tag.
					$post_tags = rhb_get_post_tags( $args );
		
					// Loop through each of those tags
					foreach ( $post_tags as $tag ) {

						// If we're comparing the same tag and the user has met the required count
						if ( ( strtolower( $badge_condition->value ) == strtolower( $tag->rh_value ) )	
							&& ( $badge_condition->count <= $tag->rh_count ) ) {
					
							$condition_met = true;

						}
					}
	
					break;

				case 'comment_count':

					if ( $badge_condition->count <= rhb_get_user_comment_count( $args ) ) {
						$condition_met = true;
					}

					break;

				case 'post_count':

					if ( $badge_condition->count <= rhb_get_user_post_count( $args ) ) {
						$condition_met = true;
					}

					break;

			} // end switch

			// Award the badge if the conditions were met.
			$award_badge = $condition_met && $award_badge;
				

		} // end badge condition loop

		$args = array( 'badge_ID' => $badge->badge_id,
				'user_ID' => $args['user_ID'] );

		// If the user has met conditions for badge and doesn't already have the badge
		if ( 'yes' == $award_badge && !rhb_user_has_badge( $args ) ) {

			// award the badge to the user.
			rhb_award_badge( $args );
		}
	}
}

function rhb_user_has_badge( $args = '' ) { 

	global $wpdb;

	$badge_count = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'rh_user_badges WHERE badge_id = %d AND user_id = $d;', $args['badge_ID'], $args['user_ID']));

	return $badge_count;
}

function rhb_get_post_tags( $args = '' ) { 

	global $wpdb;

	// This query selects the number of tags used across all posts by a specific user.
	// Tags which have not been used are not returned.
	$sql = "SELECT 'post_tag' rh_object_type, trm.name rh_value, COUNT( * ) rh_count 
		FROM " . $wpdb->prefix . "posts pst, 
			" . $wpdb->prefix . "users usr, 
			" . $wpdb->prefix . "term_taxonomy tax, 
			" . $wpdb->prefix . "terms trm, 
			" . $wpdb->prefix . "term_relationships rel
		WHERE pst.post_author = usr.ID
			AND usr.ID = %d
			AND pst.post_status =  'publish'
			AND tax.taxonomy =  'post_tag'
			AND tax.term_id = trm.term_id
			AND rel.object_id = pst.ID
			AND rel.term_taxonomy_id = tax.term_taxonomy_id
		GROUP BY trm.name";

	$post_tags = $wpdb->get_results( $wpdb->prepare( $sql, $args['user_ID'] ) );

	return $post_tags;
}

add_action( 'admin_menu', 'rhb_add_pages' );

function rhb_add_pages() {

	add_posts_page( __('Badges','menu-badges'), __('Badges','menu-badges'), 'manage_options', 'badges', 'rhb_badges_page');

	function rhb_edit_page() {
	
		if ( array_key_exists( 'add-badge-condition-posted', $_POST ) ) {
		
			?>
			<div class="updated"><p><strong><?php _e('Condition added successfully.', 'menu-badges' ); ?></strong></p></div>
			<?php
		
			// Get posted values for new badge.
			$args = array( 'object_type' => $_POST['badge-condition-type'], 
				'value' => $_POST['badge-condition-value'], 
				'count' => $_POST['badge-condition-count'],
				'badge_ID' => $_GET['badge_ID']);
				
			// Insert the badge into the database.
			rhb_add_badge_condition( $args );
		}

		if ( array_key_exists( 'update-badge-posted', $_POST) ) {
		
			?>
			<div class="updated"><p><strong><?php _e('Badge updated successfully.', 'menu-badges' ); ?></strong></p></div>
			<?php
		
			// Get posted values for new badge.
			$args = array( 'badge_ID' => $_GET['badge_ID'],
				'name' => $_POST['badge-name'], 
				'description' => $_POST['badge-desc'], 
				'type' => $_POST['badge-type'] );
				
			// Update the badge.
			rhb_update_badge( $args );
		}

		if ( isset( $_GET['action'] ) && $_GET['action'] == 'deletecondition' ) { 

			?>
			<div class="updated"><p><strong><?php _e( 'Condition removed successfully.', 'menu-badges' ); ?></strong></p></div>
			<?php
		
			// Remove the condition.
			$args = array( 'badge_condition_ID' => $_GET['badge_condition_ID'] );
			rhb_remove_badge_condition( $args );
		}
		
		// Fetch the badge details.
		$filter = array( 'badge_ID' => $_GET['badge_ID'] );
		$badges = rhb_get_badges( $filter );
		$badge = $badges[0];
		
		?>
		
		<div class="wrap">
		<h2><?php echo __( 'Badges', 'menu-badges' ); ?></h2>
		<div id="col-container">

		<div id="col-right">
		<div class="col-wrap">
			<div class="form-wrap">
			<form method="post" action="" id="object-filter">
		
			<table class="wp-list-table widefat fixed">
				<thead>
		        	<tr>
					<th class="manage-column column-title" id="criteria-type" scope="col"><span>Object Type</span></th>
		
					<th class="manage-column column-title" id="criteria-value" scope="col"><span>Value</span></th>

					<th class="manage-column column-title" id="criteria-count" scope="col"><span>Count</span></th>
				</tr>
				</thead>
				<tbody>

				<?php 

				$badge_condition_count = 0;
				$filter = array('badge_ID' => $_GET['badge_ID']);
				$badge_conditions = rhb_get_badge_conditions($filter);				

				foreach ($badge_conditions as $badge_condition) { 

					$badge_condition_count++;
				?>
					<tr class="<?php if ( $badge_condition_count%2 == 0 ) { echo 'alternate'; } ?>">
						<td>
							<strong><?php echo $badge_condition->object_type; ?></strong>
							<br />
							<div class="row-actions">
								<span class="delete"><a href="?page=badges&action=deletecondition&badge_ID=<?php echo $_GET['badge_ID']; ?>&badge_condition_ID=<?php echo $badge_condition->badge_condition_id; ?>" class="delete-tag">Delete</a></span>
							</div>
						</td>
						<td><?php echo $badge_condition->value; ?></td>
						<td><?php echo $badge_condition->count; ?></td>
					</tr>
				<?php
				} 
				?>
				</tbody>
			</table>
		
			</form>	
			</div> <!-- /formwrap -->
			
			<div id="poststuff">
			<div class="form-wrap">
			<div class="postbox" id="postcustom">
			<div title="Click to toggle" class="handlediv"><br></div>
			<h3 class="hndle"><span>Add Condition</span></h3>
			<div class="inside">

	
				<form class="validate" action="edit.php?page=badges&action=edit&badge_ID=<?php echo $badge->badge_id; ?>" method="post" id="add-badge-condition">
					<input type="hidden" value="1" name="add-badge-condition-posted" id="add-badge-condition-posted">
					
					<div class="form-field">
						<label for="badge-condition-type" style="display:inline;">Object Type</label>
						<select id="badge-condition-type" name="badge-condition-type">
							<option value="post_tag" selected="selected">Post Tag</option>
							<option value="post_count">Post Count</option>
							<option value="comment_count">Comment Count</option>
						</select>
					</div>
					
					<div class="form-field form-required">
						<label for="badge-condition-value">Value</label>
						<input type="text" size="40" value="" id="badge-condition-value" name="badge-condition-value">
						<p>This should match the name of the object e.g. a tag name.</p>
					</div>
					
					<div class="form-field">
						<label for="badge-condition-count">Count</label>
						<input type="text" size="40" value="" id="badge-condition-count" name="badge-condition-count">
						<p>The quantity of the value required for the condition to be met.</p>
					</div>
									
					<p class="submit"><input type="submit" value="Add Condition" class="button" id="submit" name="submit"></p>
					
				</form>
			
			</div><!-- /inside-->
			</div><!-- /postbox -->
			</div><!-- /form-wrap -->
			</div><!-- /poststuff -->
			
		</div><!-- /col-wrap -->
		</div><!-- /col-right -->
					
		<div id="col-left">
		<div class="col-wrap">
					
			<div class="form-wrap">	
			<form class="validate" action="edit.php?page=badges&action=edit&badge_ID=<?php echo $badge->badge_id; ?>" method="post" id="update-badge">
				<input type="hidden" value="1" name="update-badge-posted" id="add-badge-posted">
				<div class="form-field form-required">
					<label for="badge-name">Name</label>
					<input type="text" size="40" value="<?php echo $badge->name; ?>" id="badge-name" name="badge-name">
					<p>The name is how it appears on your site.</p>
				</div>
				
				<div class="form-field">
					<label for="badge-description">Description</label>
					<textarea cols="40" rows="5" id="badge-desc" name="badge-desc"><?php echo $badge->description; ?></textarea>
					<p>A description of the badge criteria. This also appears on your site.</p>
				</div>
				
				<div class="form-field">
					<label for="badge-type" style="display:inline;">Badge Type</label>
					<select id="badge-type" name="badge-type">
						<option value="gold" <?php if ($badge->type == 'gold') { echo 'selected="selected"'; } ?>>Gold</option>
						<option value="silver" <?php if ($badge->type == 'silver') { echo 'selected="selected"'; } ?>>Silver</option>
						<option value="bronze" <?php if ($badge->type == 'bronze') { echo 'selected="selected"'; } ?>>Bronze</option>
					</select>
				</div>
				
				<p class="submit"><input type="submit" value="Update" class="button" id="submit" name="submit"></p>
				
			</form>
			</div> <!-- /form-wrap -->
			
		</div><!-- /col-wrap -->
		</div><!-- /col-left -->
		</div><!-- /col-container -->
		</div><!-- /wrap -->
		<?php
				
	}

	function rhb_badges_page() {

		// Check that the user can manage categories.
		if ( !current_user_can( 'manage_categories' ) )
		{
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		
		// debug shit
		$args = array('user_ID' => 5);
		rhb_check_user_badges( $args );

		if ( array_key_exists('add-badge-posted', $_POST) ) {
		
			// Get posted values for new badge.
			$args = array( 'name' => $_POST['badge-name'], 
				'description' => $_POST['badge-desc'], 
				'type' => $_POST['badge-type']);
				
			// Insert the badge into the database.
			rhb_add_badge($args);

			?>
			<div class="updated"><p><strong><?php _e('Badge added successfully.', 'menu-badges' ); ?></strong></p></div>
			<?php
		}

		if ( array_key_exists('awardrevoke-posted', $_POST) ) {
		
			// Search the user by the given username.
			$user = get_userdatabylogin($_POST['awardrevoke-username']);
			
			// If a user was returned and badges were checked.
			if ( $user && isset( $_POST['awardrhb_revoke_badges'] ) ) {
			
				// Loop through each selected badge.
				foreach ($_POST['awardrhb_revoke_badges'] as $k => $badge_ID){
					
					// Build the arguments array based on the user ID and current badge ID.
	   				$args = array('user_ID' => $user->ID,
	   					'badge_ID' => $badge_ID);
		
					if ( $_POST['awardrevoke-action-type'] == 'award' ) {
					
						// Award the specified user the badge.
						rhb_award_badge($args);
						
					} elseif ( $_POST['awardrevoke-action-type'] == 'revoke' ) {
					
						// Revoke the badge from the specified user.
						rhb_revoke_badge($args);
						
					}
				}		
				
				?>
				<div class="updated"><p><strong><?php _e('Changes applied successfully.', 'menu-badges' ); ?></strong></p></div>
				<?php

			}
		} 
		
		if ( array_key_exists('action', $_GET) 
			&& $_GET['action'] == 'delete'
			&& isset($_GET['badge_ID']) ) { 
		
			// Get posted values for new badge.
			$args = array( 'badge_ID' => $_GET['badge_ID']);
				
			// Insert the badge into the database.
			rhb_remove_badge($args);

			?>
			<div class="updated"><p><strong><?php _e('Badge removed successfully.', 'menu-badges' ); ?></strong></p></div>
			<?php
		}
		
		// If we are editing the badge or performing an action on the edit page.
		if (isset( $_GET['action'] ) 
			&& ( $_GET['action'] == 'edit' 
				|| $_GET['action'] == 'deletecondition' ) ) {

			// Display the badge editing page.
			rhb_edit_page();

		} else {

			// Otherwise, display the main badges page.
		?>
		
		<div class="wrap">
		<h2><?php echo __('Badges','menu-badges'); ?></h2>
		<div id="col-container">

		<div id="col-right">
		<div class="col-wrap">
			
			<form class="validate" action="edit.php?page=badges" method="post" id="awardrevoke">
				
			<div class="form-wrap">
			<table class="wp-list-table widefat fixed">
				<thead>
		        	<tr>
					<th class="check-column"></th>
					<th class="manage-column column-title" id="badge-name" scope="col"><span>Name</span></th>
		
					<th class="manage-column column-title" id="badge-desc" scope="col"><span>Description</span></th>

					<th class="manage-column column-title" id="badge-class" scope="col"><span>Type</span></th>
				</tr>
				</thead>
				<tbody>
				<?php
				
				// Controls the alternating row style.
				$badge_count = 0;
				
				foreach (rhb_get_badges() as $badge) {
				
					$badge_count++;
					
					?>
					
					<tr class="<?php if ($badge_count%2 == 0) { echo 'alternate'; } ?>">
<th scope="row" class="check-column"><input type="checkbox" name="awardrhb_revoke_badges[]" value="<?php echo $badge->badge_id; ?>"></th>
						<td>
							<strong><a href="?page=badges&action=edit&badge_ID=<?php echo $badge->badge_id; ?>"><?php echo $badge->name; ?></a></strong>
							<br />
							<div class="row-actions">
								<span class="edit"><a href="?page=badges&action=edit&badge_ID=<?php echo $badge->badge_id; ?>">Edit</a> | </span>
								<span class="delete"><a href="?page=badges&action=delete&badge_ID=<?php echo $badge->badge_id; ?>" class="delete-tag">Delete</a></span>
							</div>
						</td>
						<td><?php echo $badge->description; ?></td>
						<td><?php echo $badge->type; ?></td>
					</tr>
					
					<?php
				}
				?>
				</tbody>
			</table>
		
			</div> <!-- /form-wrap -->
			
			<div id="poststuff">
			<div class="form-wrap">
			<div class="postbox" id="postcustom">
			<h3 class="hndle"><span>Award/ Revoke Badges</span></h3>
			<div class="inside">

					<input type="hidden" value="1" name="awardrevoke-posted" id="add-badge-condition-posted">
					
					<div class="form-field">
						<label for="awardrevoke-action-type" style="display: inline;">Action Type</label>
						<select id="awardrevoke-action-type" name="awardrevoke-action-type">
							<option value="award" selected="selected">Award</option>
							<option value="revoke">Revoke</option>
						</select>
					</div>
					
					<div class="form-field form-required">
						<label for="awardrevoke-username">Username</label>
						<input type="text" size="40" value="" id="awardrevoke-username" name="awardrevoke-username">
					</div>
						
                                        <p>Select one or more badges from the table on the right and click the <b>Apply Changes</b> button below.</p>                                        
								
					<p class="submit"><input type="submit" value="Apply Changes" class="button" id="submit" name="submit"></p>
					
			
			</div><!-- /inside-->
			</div><!-- /postbox -->
			</div><!-- /form-wrap -->
			</div><!-- /poststuff -->
		</form>	
		</div><!-- /col-wrap-->
		</div><!-- /col-right -->
					
		<div id="col-left">
		<div class="col-wrap">
			
			<h3>Add New Badge</h3>
			
			<div class="form-wrap">
			<form class="validate" action="edit.php?page=badges" method="post" id="add-badge">
			
				<input type="hidden" value="1" name="add-badge-posted" id="add-badge-posted">
			
				<div class="form-field form-required">
					<label for="badge-name">Name</label>
					<input type="text" size="40" value="" id="badge-name" name="badge-name">
					<p>The name is how it appears on your site.</p>
				</div>
				
				<div class="form-field">
					<label for="badge-description">Description</label>
					<textarea cols="40" rows="5" id="badge-desc" name="badge-desc"></textarea>
					<p>A description of the badge criteria. This also appears on your site.</p>
				</div>
				
				<div class="form-field">
					<label for="badge-type" style="display:inline;">Badge Type</label>
					<select id="badge-type" name="badge-type">
						<option selected="selected" value="gold">Gold</option>
						<option value="silver">Silver</option>
						<option value="bronze">Bronze</option>
					</select>
				</div>
				
				<p class="submit"><input type="submit" value="Add New Badge" class="button" id="submit" name="submit"></p>
			</form>
			</div><!-- /form-wrap -->
		</div><!-- /col-wrap -->
		</div><!-- /col-left -->
		</div><!-- /col-container -->
		</div><!-- /wrap -->
		
		<?php
		} // End Post page
		
	} // End rhb_badges_page function
}

/**
 * LatestBadgesWidget Class
 */
class LatestBadgesWidget extends WP_Widget {
    /** constructor */
    function LatestBadgesWidget() {
        parent::WP_Widget(false, $name = 'Latest Badges');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        	echo $before_title . $title . $after_title; 
			else
				echo $before_title . 'Recent Badges' . $after_title; ?>
		  
		  <?php rhb_list_recent_badges(); ?>
		  
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title = esc_attr($instance['title']);
        ?>
         <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
        <?php 
    }

} // class LatestBadgesWidget

// Link to Pegleg Badges stylesheet and apply some custom styles
function rhb_css() {
	echo "\n".'<link rel="stylesheet" href="'. WP_PLUGIN_URL . '/rockhoist-badges/badges.css" type="text/css" media="screen" />'."\n";
}

add_action('widgets_init', create_function('', 'return register_widget("LatestBadgesWidget");')); // register LatestBadgesWidget widget
add_action('wp_print_styles', 'rhb_css'); // Pegleg Badges stylesheet 
