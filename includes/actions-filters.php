<?php

add_action('wp_dashboard_setup', function () {

	wp_add_dashboard_widget('badgeos_activity_index_widget', __('Activity Index', 'badgeos-activity-index'), function () {
		global $wpdb;
		$meta_key = '_badgeos_achievements';
		$achievement_type = 'site-activity';

		// get all achievements from all users
		$user_achievements = $wpdb->get_col( $wpdb->prepare("
			SELECT meta_value
			FROM $wpdb->usermeta
			WHERE meta_key = %s", $meta_key) );

		$points = array();
		$test_count = 100;

		if ( is_array( $user_achievements) && ! empty( $user_achievements ) ) {
			foreach ( $user_achievements as $achievements ) {
				$achievements = unserialize($achievements);
				// XXX: Why index 1 here?
				if ( is_array( $achievements) && ! empty( $achievements ) )
					$achievements = $achievements[1];

				if ( is_array( $achievements) && ! empty( $achievements ) ) {

					// add some random values
					for($i = 0; $i< $test_count; $i++){
						$achievements[] = json_decode(json_encode(array(
							'post_type' => $achievement_type,
							'date_earned' => rand(time() - 31536000, time()),
							'points' => rand(1, 10),
						)), FALSE);
					}

					foreach ( $achievements as $achievement ) {
						// Add any achievements that match our achievement type
						if ( $achievement_type == $achievement->post_type ) {
							$key = strtotime(date("F Y", $achievement->date_earned));

							if (!isset($points[$key]))
								$points[$key] = 0;
							$points[$key] += intval($achievement->points);
						}
					}
				}
			}
		}

		ksort($points);
		$points = array_reverse($points, true);
		echo "<table><thead><tr><th>Month</th><th>Index</th></thead><tbody>";
		foreach ( $points as $key => $point ) {
			$date = date("F Y", $key);
			echo "<tr><td>$date</td><td>$point</td></tr>";
		}
		echo "</tbody></table>";
	});
});



