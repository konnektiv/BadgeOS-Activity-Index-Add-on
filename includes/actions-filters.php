<?php

add_action('wp_dashboard_setup', function () {

	wp_add_dashboard_widget('badgeos_activity_index_widget', __('Activity Index', 'badgeos-activity-index'), function () {
		global $wpdb;
		$meta_key = '_badgeos_achievements';
		$since = 0;
		$achievement_type = 'site-activity';


		// get all achievements from all users
		$user_achievements = $wpdb->get_col( $wpdb->prepare("
			SELECT meta_value
			FROM $wpdb->usermeta
			WHERE meta_key = %s", $meta_key) );

		$all_achievements = array();

		if ( is_array( $user_achievements) && ! empty( $user_achievements ) ) {
			foreach ( $user_achievements as $ukey => $achievements ) {
				$achievements = unserialize($achievements);
				// XXX: Why index 1 here?
				if ( is_array( $achievements) && ! empty( $achievements ) )
					$achievements = $achievements[1];

				if ( is_array( $achievements) && ! empty( $achievements ) ) {
					foreach ( $achievements as $key => $achievement ) {
						// Add any achievements earned after our since timestamp
						// and that match our achievement type
						if ( $since < $achievement->date_earned &&
						   	 $achievement_type == $achievement->post_type )
							$all_achievements[] = $achievement;
					}
				}
			}
		}

		// sum activity index
		$points = array_reduce($all_achievements, function($carry, $achievement){
			return $carry + intval($achievement->points);
		}, 0);

		echo "Current Activity Index: $points";
	});
});



