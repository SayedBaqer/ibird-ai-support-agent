<?php defined( 'ABSPATH' ) || exit;
// Variables set by AIAgent_Admin::page_analytics()
// $stats, $rating_map, $total_ratings, $mode_map, $usage_by_day, $usage_max, $top_cats, $rating_trend
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Analytics', 'ai-support-agent' ); ?></h1>
	<p class="description" style="margin-bottom:20px;"><?php esc_html_e( 'Data reflects all time unless otherwise noted.', 'ai-support-agent' ); ?></p>

	<!-- ── Summary cards ──────────────────────────────────────────────────── -->
	<div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:28px;">
		<?php
		$cards = [
			[ 'label' => __( 'Total Conversations', 'ai-support-agent' ), 'value' => number_format( $stats['total_conversations'] ), 'color' => '#1a6b3c' ],
			[ 'label' => __( 'New Today',            'ai-support-agent' ), 'value' => number_format( $stats['today_conversations'] ), 'color' => '#0277bd' ],
			[ 'label' => __( 'Total Messages',       'ai-support-agent' ), 'value' => number_format( $stats['total_messages'] ),       'color' => '#555' ],
			[ 'label' => __( 'Deflection Rate',      'ai-support-agent' ), 'value' => $stats['deflection_rate'] . '%',                 'color' => $stats['deflection_rate'] >= 70 ? '#2e7d32' : '#e65100' ],
			[ 'label' => __( 'Escalations',          'ai-support-agent' ), 'value' => number_format( $stats['total_escalated'] ),      'color' => '#e65100' ],
			[ 'label' => __( 'Open Tickets',         'ai-support-agent' ), 'value' => number_format( $stats['open_tickets'] ),         'color' => '#1565c0' ],
			[ 'label' => __( 'Taught Examples',      'ai-support-agent' ), 'value' => number_format( $stats['taught_examples'] ),      'color' => '#6a1b9a' ],
			[ 'label' => __( 'Manual Chunks',        'ai-support-agent' ), 'value' => number_format( $stats['manual_chunks'] ),        'color' => '#37474f' ],
		];
		foreach ( $cards as $c ) :
		?>
		<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px 20px;min-width:140px;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.06);">
			<div style="font-size:28px;font-weight:700;color:<?php echo esc_attr( $c['color'] ); ?>;"><?php echo esc_html( $c['value'] ); ?></div>
			<div style="font-size:12px;color:#718096;margin-top:4px;"><?php echo esc_html( $c['label'] ); ?></div>
		</div>
		<?php endforeach; ?>
	</div>

	<div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;">

		<!-- ── Daily usage bar chart ────────────────────────────────────────── -->
		<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;min-width:360px;flex:1;">
			<h3 style="margin:0 0 16px;"><?php esc_html_e( 'LLM Requests — Last 7 Days', 'ai-support-agent' ); ?></h3>
			<div style="display:flex;align-items:flex-end;gap:6px;height:120px;">
				<?php foreach ( $usage_by_day as $day => $count ) :
					$pct = $count > 0 ? max( 4, round( $count / $usage_max * 100 ) ) : 2;
					$label = gmdate( 'D d', strtotime( $day ) );
				?>
				<div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;">
					<div style="font-size:10px;color:#718096;"><?php echo esc_html( $count > 0 ? $count : '' ); ?></div>
					<div style="width:100%;background:#1a6b3c;border-radius:4px 4px 0 0;height:<?php echo esc_attr( $pct ); ?>%;min-height:2px;"></div>
					<div style="font-size:10px;color:#718096;white-space:nowrap;"><?php echo esc_html( $label ); ?></div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- ── Rating breakdown ─────────────────────────────────────────────── -->
		<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;min-width:240px;">
			<h3 style="margin:0 0 16px;"><?php esc_html_e( 'Rating Breakdown', 'ai-support-agent' ); ?></h3>
			<?php if ( $total_ratings === 0 ) : ?>
				<p style="color:#718096;font-size:13px;"><?php esc_html_e( 'No ratings yet.', 'ai-support-agent' ); ?></p>
			<?php else :
				$rating_display = [
					'good'       => [ 'label' => __( 'Good', 'ai-support-agent' ),       'color' => '#2e7d32', 'emoji' => '👍' ],
					'wrong'      => [ 'label' => __( 'Wrong', 'ai-support-agent' ),      'color' => '#c62828', 'emoji' => '❌' ],
					'incomplete' => [ 'label' => __( 'Incomplete', 'ai-support-agent' ), 'color' => '#e65100', 'emoji' => '⚠️' ],
				];
				foreach ( $rating_display as $key => $rd ) :
					$cnt  = $rating_map[ $key ];
					$pct  = round( $cnt / $total_ratings * 100 );
			?>
				<div style="margin-bottom:12px;">
					<div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
						<span><?php echo esc_html( $rd['emoji'] . ' ' . $rd['label'] ); ?></span>
						<span style="color:<?php echo esc_attr( $rd['color'] ); ?>;font-weight:600;"><?php echo esc_html( $cnt . ' (' . $pct . '%)' ); ?></span>
					</div>
					<div style="background:#f0f0f0;border-radius:4px;height:8px;">
						<div style="background:<?php echo esc_attr( $rd['color'] ); ?>;width:<?php echo esc_attr( $pct ); ?>%;height:8px;border-radius:4px;"></div>
					</div>
				</div>
			<?php endforeach; ?>
			<p style="font-size:12px;color:#718096;margin-top:8px;"><?php printf( esc_html__( '%d rated messages total', 'ai-support-agent' ), $total_ratings ); ?></p>
			<?php endif; ?>
		</div>

		<!-- ── Mode distribution ────────────────────────────────────────────── -->
		<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;min-width:200px;">
			<h3 style="margin:0 0 16px;"><?php esc_html_e( 'Conversation Mode', 'ai-support-agent' ); ?></h3>
			<?php
			$total_modes = array_sum( $mode_map );
			$mode_display = [
				'product' => [ 'label' => __( 'Product Q&A', 'ai-support-agent' ), 'color' => '#0277bd' ],
				'support' => [ 'label' => __( 'Support',     'ai-support-agent' ), 'color' => '#c62828' ],
			];
			if ( $total_modes === 0 ) : ?>
				<p style="color:#718096;font-size:13px;"><?php esc_html_e( 'No conversations yet.', 'ai-support-agent' ); ?></p>
			<?php else :
				foreach ( $mode_display as $key => $md ) :
					$cnt = $mode_map[ $key ] ?? 0;
					$pct = $total_modes > 0 ? round( $cnt / $total_modes * 100 ) : 0;
			?>
				<div style="margin-bottom:12px;">
					<div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
						<span><?php echo esc_html( $md['label'] ); ?></span>
						<span style="color:<?php echo esc_attr( $md['color'] ); ?>;font-weight:600;"><?php echo esc_html( $cnt . ' (' . $pct . '%)' ); ?></span>
					</div>
					<div style="background:#f0f0f0;border-radius:4px;height:8px;">
						<div style="background:<?php echo esc_attr( $md['color'] ); ?>;width:<?php echo esc_attr( $pct ); ?>%;height:8px;border-radius:4px;"></div>
					</div>
				</div>
			<?php endforeach; endif; ?>
		</div>

	</div><!-- /row 1 -->

	<div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;margin-top:24px;">

		<!-- ── Top categories ───────────────────────────────────────────────── -->
		<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;min-width:280px;flex:1;">
			<h3 style="margin:0 0 16px;"><?php esc_html_e( 'Top Knowledge Categories', 'ai-support-agent' ); ?></h3>
			<?php if ( empty( $top_cats ) ) : ?>
				<p style="color:#718096;font-size:13px;"><?php esc_html_e( 'No categories yet — teach some examples first.', 'ai-support-agent' ); ?></p>
			<?php else :
				$max_cat = max( array_column( (array) $top_cats, 'cnt' ) );
				foreach ( $top_cats as $cat ) :
					$w = round( $cat->cnt / $max_cat * 100 );
			?>
				<div style="margin-bottom:10px;">
					<div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px;">
						<span><?php echo esc_html( $cat->name_en ); ?><?php if ( $cat->name_ar ) echo ' / ' . esc_html( $cat->name_ar ); ?></span>
						<span style="font-weight:600;"><?php echo esc_html( $cat->cnt ); ?></span>
					</div>
					<div style="background:#f0f0f0;border-radius:4px;height:6px;">
						<div style="background:#6a1b9a;width:<?php echo esc_attr( $w ); ?>%;height:6px;border-radius:4px;"></div>
					</div>
				</div>
			<?php endforeach; endif; ?>
		</div>

		<!-- ── Self-improvement funnel ──────────────────────────────────────── -->
		<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;min-width:260px;">
			<h3 style="margin:0 0 16px;"><?php esc_html_e( 'Self-Improvement Funnel', 'ai-support-agent' ); ?></h3>
			<?php
			global $wpdb;
			$promoted_from_ratings  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_ratings WHERE correction IS NOT NULL AND correction != ''" );
			$promoted_from_tickets  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_tickets WHERE resolution IS NOT NULL AND resolution != '' AND status = 'resolved'" );
			$total_promoted         = $stats['taught_examples'];

			$funnel = [
				[ 'label' => __( 'AI Replies Rated',       'ai-support-agent' ), 'value' => $total_ratings ],
				[ 'label' => __( 'Corrections Written',    'ai-support-agent' ), 'value' => $promoted_from_ratings ],
				[ 'label' => __( 'Tickets Resolved',       'ai-support-agent' ), 'value' => $promoted_from_tickets ],
				[ 'label' => __( 'Total Taught Examples',  'ai-support-agent' ), 'value' => $total_promoted ],
			];
			$funnel_max = max( 1, $total_ratings );
			foreach ( $funnel as $f ) :
				$w = round( $f['value'] / $funnel_max * 100 );
			?>
			<div style="margin-bottom:12px;">
				<div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px;">
					<span><?php echo esc_html( $f['label'] ); ?></span>
					<span style="font-weight:600;"><?php echo esc_html( $f['value'] ); ?></span>
				</div>
				<div style="background:#f0f0f0;border-radius:4px;height:8px;">
					<div style="background:#1a6b3c;width:<?php echo esc_attr( $w ); ?>%;height:8px;border-radius:4px;"></div>
				</div>
			</div>
			<?php endforeach; ?>
			<p style="font-size:12px;color:#718096;margin-top:10px;">
				<?php esc_html_e( 'Each taught example improves future answers without retraining the model.', 'ai-support-agent' ); ?>
			</p>
		</div>

		<!-- ── Rating trend table ────────────────────────────────────────────── -->
		<div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;min-width:280px;flex:1;max-height:320px;overflow-y:auto;">
			<h3 style="margin:0 0 12px;"><?php esc_html_e( 'Rating Trend (Last 30 Days)', 'ai-support-agent' ); ?></h3>
			<?php if ( empty( $rating_trend ) ) : ?>
				<p style="color:#718096;font-size:13px;"><?php esc_html_e( 'No ratings yet.', 'ai-support-agent' ); ?></p>
			<?php else : ?>
			<table class="wp-list-table widefat fixed striped" style="font-size:12px;">
				<thead><tr>
					<th><?php esc_html_e( 'Day', 'ai-support-agent' ); ?></th>
					<th><?php esc_html_e( 'Good', 'ai-support-agent' ); ?></th>
					<th><?php esc_html_e( 'Wrong', 'ai-support-agent' ); ?></th>
					<th><?php esc_html_e( 'Incomplete', 'ai-support-agent' ); ?></th>
				</tr></thead>
				<tbody>
				<?php
				// Group by day.
				$by_day = [];
				foreach ( $rating_trend as $row ) {
					$by_day[ $row->day ][ $row->score ] = (int) $row->cnt;
				}
				foreach ( array_reverse( $by_day, true ) as $day => $scores ) :
					$g = $scores['good']       ?? 0;
					$w = $scores['wrong']      ?? 0;
					$i = $scores['incomplete'] ?? 0;
				?>
				<tr>
					<td><?php echo esc_html( gmdate( 'M d', strtotime( $day ) ) ); ?></td>
					<td style="color:#2e7d32;"><?php echo esc_html( $g ?: '—' ); ?></td>
					<td style="color:#c62828;"><?php echo esc_html( $w ?: '—' ); ?></td>
					<td style="color:#e65100;"><?php echo esc_html( $i ?: '—' ); ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</div>

	</div><!-- /row 2 -->

</div>
