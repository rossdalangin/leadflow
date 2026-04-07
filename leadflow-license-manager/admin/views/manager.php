<div class="wrap">
	<h1>LeadFlow License Manager</h1>
	<p>Generate and manage your customer licenses from here.</p>

	<div class="card" style="max-width: 400px; margin-bottom: 30px;">
		<h2>Generate New License</h2>
		<form method="post" action="">
			<?php wp_nonce_field( 'lfm_generate' ); ?>
			<p><label>Customer Name</label><br><input type="text" name="customer_name" required class="regular-text"></p>
			<p><button type="submit" name="lfm_generate_btn" class="button button-primary">Generate & Save</button></p>
		</form>
	</div>

	<?php
	global $wpdb;
	if ( isset( $_POST['lfm_generate_btn'] ) && check_admin_referer( 'lfm_generate' ) ) {
		$key = 'LF-' . strtoupper( wp_generate_password( 4, false ) ) . '-' . strtoupper( wp_generate_password( 4, false ) ) . '-' . strtoupper( wp_generate_password( 4, false ) );
		$wpdb->insert( $wpdb->prefix . 'lfm_licenses', array(
			'license_key' => $key,
			'customer_name' => sanitize_text_field( $_POST['customer_name'] ),
			'created_at' => current_time( 'mysql' ),
			'status' => 'active'
		) );
		echo '<div class="notice notice-success"><p>Generated: <code>' . $key . '</code></p></div>';
	}

	if ( isset( $_GET['toggle_status'] ) ) {
		$wpdb->update( $wpdb->prefix . 'lfm_licenses',
			array( 'status' => $_GET['status'] === 'active' ? 'inactive' : 'active' ),
			array( 'id' => $_GET['toggle_status'] )
		);
	}

	$licenses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lfm_licenses ORDER BY created_at DESC" );
	?>

	<h2>Active Licenses</h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Key</th>
				<th>Customer</th>
				<th>Domain</th>
				<th>Status</th>
				<th>Activated</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $licenses as $l ) : ?>
				<tr>
					<td><code><?php echo esc_html( $l->license_key ); ?></code></td>
					<td><?php echo esc_html( $l->customer_name ); ?></td>
					<td><?php echo esc_html( $l->domain ?: 'Not yet' ); ?></td>
					<td>
						<span class="status-badge <?php echo $l->status; ?>" style="background: <?php echo $l->status === 'active' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $l->status === 'active' ? '#166534' : '#991b1b'; ?>; padding: 4px 8px; border-radius: 4px;">
							<?php echo ucfirst( $l->status ); ?>
						</span>
					</td>
					<td><?php echo esc_html( $l->activated_at ?: '-' ); ?></td>
					<td>
						<a href="?page=lf-licenses&toggle_status=<?php echo $l->id; ?>&status=<?php echo $l->status; ?>" class="button button-small">
							<?php echo $l->status === 'active' ? 'Disable' : 'Enable'; ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
