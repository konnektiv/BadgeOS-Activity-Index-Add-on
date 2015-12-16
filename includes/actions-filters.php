<?php

add_action('wp_dashboard_setup', function () {

	wp_add_dashboard_widget('badgeos_activity_index_widget', __('Activity Index', 'badgeos-activity-index'), function () {
		global $wpdb;
		$meta_key = '_badgeos_achievements';

		$widget_options = get_option( '_bai_dashboard_widget_options' );
		$achievement_type = $widget_options['achievement_type'];

		// get all achievements from all users
		$user_achievements = $wpdb->get_col( $wpdb->prepare("
			SELECT meta_value
			FROM $wpdb->usermeta
			WHERE meta_key = %s", $meta_key) );

		$points = array();
		$test_count = 0;

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
						if ( !$achievement_type || $achievement_type == $achievement->post_type ) {
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
	}, function() {
		$option_name =  '_bai_dashboard_widget_options';

		// Update widget options
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['bai_dashboard_widget_post']) ) {
			update_option( $option_name, $_POST['bai_dashboard_widget_options'] );
		}

		// Get widget options
		if ( !$widget_options = get_option( $option_name ) )
			$widget_options = array();

		$achievement_type = $widget_options['achievement_type'];
		$achievement_types =  badgeos_get_achievement_types();
		?>

		<p>
			<label for="bai_achievement_type"><?php _e('Choose the achievement type:', 'badgeos-activity-index'); ?></label>
			<select class="widefat" id="bai_achievement_type" name="bai_dashboard_widget_options[achievement_type]">
				<option value="" <?php selected(null, $achievement_type) ?>><?php _e('All types', 'badgeos-activity-index') ?></option>
				<?php foreach($achievement_types as $slug => $type){ ?>
					<option value="<?php echo($slug) ?>" <?php selected($slug, $achievement_type) ?>><?php echo($type['single_name']) ?></option>
				<?php } ?>
			</select>
			<input name="bai_dashboard_widget_post" type="hidden" value="1" />
		</p>

		<?php
	});
});
