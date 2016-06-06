<?php

add_action('wp_dashboard_setup', function () {

	wp_add_dashboard_widget('badgeos_activity_index_widget', __('Activity Index', 'badgeos-activity-index'), function () {
		global $wpdb;
		$meta_key = '_badgeos_achievements';

		$widget_options = get_option( '_bai_dashboard_widget_options' );
		$achievement_type = $widget_options['achievement_type'];
		$intervals = array(
			'monthly' => array('format' => 'om', 'label' => 'F o', 'header' => __('Month', 'badgeos-activity-index')),
			'weekly'  => array('format' => 'oW', 'label' => 'W/o', 'header' => __('Week', 'badgeos-activity-index')),
			'daily'   => array('format' => 'omd', 'label' => 'j F o', 'header' => __('Day', 'badgeos-activity-index')),
		);
		$interval = isset($widget_options['interval'])?$widget_options['interval']:'monthly';
		$interval = $intervals[$interval];

		// get all achievements from all users
		$user_achievements = $wpdb->get_col( $wpdb->prepare("
			SELECT meta_value
			FROM $wpdb->usermeta
			WHERE meta_key = %s", $meta_key) );

		$points = array();
		$test_count = $widget_options['preview']?40:0;

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
							$key = date($interval['format'], $achievement->date_earned);
							$points[$key]['label'] = date($interval['label'], $achievement->date_earned);

							if (!isset($points[$key]['points']))
								$points[$key]['points'] = 0;
							$points[$key]['points'] += intval($achievement->points);
						}
					}
				}
			}
		}

		ksort($points);
		$table_points = array_reverse($points, true);
		$header = $interval['header'];
		$cur_display = isset($widget_options['display'])?$widget_options['display']:'chart';

		if ($widget_options['preview']) { ?>
			<p>This data is only a preview! To see the real data, uncheck 'Show preview of activity data' in the <a href="<?php echo admin_url('/index.php?edit=badgeos_activity_index_widget#badgeos_activity_index_widget') ?>">Configure</a> screen.</p>
		<?php } ?>
		<p><a class="activity-index-select-table <?php echo ($cur_display =='table'?'active':'') ?>" href="">Table</a>|
			<a class="activity-index-select-chart <?php echo ($cur_display =='chart'?'active':'') ?>" href="">Chart</a></p>

		<div class="activity_index_table" <?php if ($cur_display =='chart') echo 'style="display: none;"'; ?>>

		<?php
		echo "<table><thead><tr><th>$header</th><th>Index</th></thead><tbody>";
		foreach ( $table_points as $key => $point ) {
			$label = $point['label'];
			$value = $point['points'];
			echo "<tr><td>$label</td><td>$value</td></tr>";
		}
		echo "</tbody></table>"; ?>
		</div><div class="activity_index_chart" <?php if ($cur_display =='table') echo 'style="display: none;"'; ?>>

		<?php
		$chart_data = array(
			'data'   => array(),
			'header' => $header
		);
		$max_labels = 2;
		$steps = intval(count($points) / $max_labels);
		$i = 0;
		foreach ( $points as $key => $point ) {
			$chart_data['data'][] = array(
				'tooltip' => $point['label'] . ': ' . $point['points'],
				'label'   => !($i % $steps)||$i==(count($points)-1)?$point['label']:'',
				'points'  => $point['points']
			);
			$i++;
		}
		wp_localize_script( 'activity_index_main', 'activity_index_data', $chart_data);
		wp_enqueue_script( 'activity_index_main' );
		?>
		<div id="actvity_chart_container" style="width:100%;height:250px;"></div>
		</div>
		<?php

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
		$cur_interval = isset($widget_options['interval'])?$widget_options['interval']:'monthly';
		$cur_display = isset($widget_options['display'])?$widget_options['display']:'chart';
		$intervals = array('monthly', 'weekly', 'daily');
		$displays = array('chart', 'table');
		$preview = $widget_options['preview'];
		?>

		<p>
			<label for="bai_achievement_type"><?php _e('Choose the achievement type:', 'badgeos-activity-index'); ?></label>
			<select class="widefat" id="bai_achievement_type" name="bai_dashboard_widget_options[achievement_type]">
				<option value="" <?php selected(null, $achievement_type) ?>><?php _e('All types', 'badgeos-activity-index') ?></option>
				<?php foreach($achievement_types as $slug => $type){ ?>
					<option value="<?php echo $slug ?>" <?php selected($slug, $achievement_type) ?>><?php echo $type['single_name'] ?></option>
				<?php } ?>
			</select>
		</p>
        <p>
			<label for="bai_interval"><?php _e('Choose the activity index interval:', 'badgeos-activity-index'); ?></label>
			<select class="widefat" id="bai_interval" name="bai_dashboard_widget_options[interval]">
				<?php foreach($intervals as $interval){ ?>
					<option value="<?php echo $interval ?>" <?php selected($interval, $cur_interval) ?>><?php echo $interval ?></option>
				<?php } ?>
			</select>
		</p>
		<p>
			<label for="bai_display"><?php _e('Choose default display type:', 'badgeos-activity-index'); ?></label>
			<select class="widefat" id="bai_display" name="bai_dashboard_widget_options[display]">
				<?php foreach($displays as $display){ ?>
					<option value="<?php echo $display ?>" <?php selected($display, $cur_display) ?>><?php echo $display ?></option>
				<?php } ?>
			</select>
		</p>
		<p>
			<input type="checkbox" id="bai_preview" <?php checked(true, $preview) ?> name="bai_dashboard_widget_options[preview]" value="1">
			<label for="bai_preview"><?php _e('Show preview of activity data', 'badgeos-activity-index'); ?></label>
		</p>
		<input name="bai_dashboard_widget_post" type="hidden" value="1" />

		<?php
	});
});
