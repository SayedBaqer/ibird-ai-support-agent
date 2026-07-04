<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap aiagent-categories">
	<h1><?php esc_html_e( 'Knowledge Categories', 'ai-support-agent' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Categories proposed by the AI are listed as "Pending". Approve them to include in future auto-categorisation. Unapproved categories are still used but excluded from the suggestion list.', 'ai-support-agent' ); ?>
	</p>

	<?php if ( isset( $_GET['approved'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Category approved.', 'ai-support-agent' ); ?></p></div>
	<?php endif; ?>

	<!-- ── Add category form ── -->
	<div class="card" style="max-width:520px;padding:16px;margin-bottom:20px;">
		<h3 style="margin-top:0"><?php esc_html_e( 'Add Category', 'ai-support-agent' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'aiagent_add_category' ); ?>
			<input type="hidden" name="action" value="aiagent_add_category">
			<table class="form-table" style="margin-top:0">
				<tr>
					<th><label for="cat-en"><?php esc_html_e( 'Name (EN)', 'ai-support-agent' ); ?></label></th>
					<td><input type="text" id="cat-en" name="name_en" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="cat-ar"><?php esc_html_e( 'Name (AR)', 'ai-support-agent' ); ?></label></th>
					<td><input type="text" id="cat-ar" name="name_ar" class="regular-text" dir="rtl"></td>
				</tr>
			</table>
			<?php submit_button( __( 'Add (pre-approved)', 'ai-support-agent' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>

	<!-- ── Categories table ── -->
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width:5%">ID</th>
				<th style="width:30%"><?php esc_html_e( 'English Name', 'ai-support-agent' ); ?></th>
				<th style="width:30%"><?php esc_html_e( 'Arabic Name', 'ai-support-agent' ); ?></th>
				<th><?php esc_html_e( 'Status', 'ai-support-agent' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'ai-support-agent' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			global $wpdb;
			$cats = $wpdb->get_results(
				"SELECT * FROM {$wpdb->prefix}aiagent_categories ORDER BY approved DESC, name_en ASC"
			);
			if ( empty( $cats ) ) {
				echo '<tr><td colspan="5">' . esc_html__( 'No categories yet.', 'ai-support-agent' ) . '</td></tr>';
			}
			foreach ( $cats as $cat ) {
				$approve_url = wp_nonce_url(
					admin_url( 'admin-post.php?action=aiagent_approve_category&id=' . $cat->id ),
					'aiagent_approve_category_' . $cat->id
				);
				echo '<tr>';
				echo '<td>' . (int) $cat->id . '</td>';
				echo '<td>' . esc_html( $cat->name_en ) . '</td>';
				echo '<td dir="rtl">' . esc_html( $cat->name_ar ) . '</td>';
				echo '<td>';
				if ( $cat->approved ) {
					echo '<span class="aiagent-badge aiagent-badge--active">' . esc_html__( 'Approved', 'ai-support-agent' ) . '</span>';
				} else {
					echo '<span class="aiagent-badge aiagent-badge--escalated">' . esc_html__( 'Pending', 'ai-support-agent' ) . '</span>';
				}
				echo '</td>';
				echo '<td>';
				if ( ! $cat->approved ) {
					echo '<a href="' . esc_url( $approve_url ) . '" class="button button-small button-primary">' . esc_html__( 'Approve', 'ai-support-agent' ) . '</a>';
				}
				echo '</td>';
				echo '</tr>';
			}
			?>
		</tbody>
	</table>
</div>
