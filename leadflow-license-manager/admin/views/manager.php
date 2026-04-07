<div class="wrap">
	<h1>LeadFlow License Manager</h1>
	<p>Generate and manage your customer licenses from here.</p>

	<?php
	global $wpdb;
	$total_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lfm_licenses" );
	$active_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lfm_licenses WHERE status = 'active'" );
	$activated_domains = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lfm_licenses WHERE domain IS NOT NULL" );
	?>
	<div class="lfm-stats-grid" style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
		<div class="card"><h3>Total Licenses</h3><p style="font-size:2rem; font-weight:bold;"><?php echo $total_count; ?></p></div>
		<div class="card"><h3>Active Subscriptions</h3><p style="font-size:2rem; font-weight:bold; color:green;"><?php echo $active_count; ?></p></div>
		<div class="card"><h3>Activated Domains</h3><p style="font-size:2rem; font-weight:bold; color:blue;"><?php echo $activated_domains; ?></p></div>
	</div>

	<div class="leadflow-tabs" style="display:flex; gap:10px; margin-bottom:20px;">
		<button class="button tab-btn active" data-target="licenseSection">Licenses</button>
		<button class="button tab-btn" data-target="logSection">Activity Logs</button>
	</div>

	<div id="licenseSection" class="tab-content">
	<div class="card" style="max-width: 400px; margin-bottom: 30px;">
		<h2>Generate New License</h2>
		<form method="post" action="">
			<?php wp_nonce_field( 'lfm_generate' ); ?>
			<p><label>Customer Name</label><br><input type="text" name="customer_name" required class="regular-text"></p>
			<p><label>License Type</label><br>
				<select name="license_type">
					<option value="pro">Pro</option>
					<option value="agency">Agency (Multi-site)</option>
				</select>
			</p>
			<p><label>Expiration Date (Optional)</label><br><input type="date" name="expires_at" class="regular-text"></p>
			<p><button type="submit" name="lfm_generate_btn" class="button button-primary">Generate & Save</button></p>
		</form>
	</div>

	<?php
	global $wpdb;
	if ( isset( $_POST['lfm_generate_btn'] ) && check_admin_referer( 'lfm_generate' ) ) {
		$key = 'LF-' . strtoupper( wp_generate_password( 4, false ) ) . '-' . strtoupper( wp_generate_password( 4, false ) ) . '-' . strtoupper( wp_generate_password( 4, false ) );
		$wpdb->insert( $wpdb->prefix . 'lfm_licenses', array(
			'license_key' => $key,
			'license_type' => sanitize_text_field( $_POST['license_type'] ),
			'customer_name' => sanitize_text_field( $_POST['customer_name'] ),
			'expires_at' => ! empty( $_POST['expires_at'] ) ? sanitize_text_field( $_POST['expires_at'] ) : null,
			'created_at' => current_time( 'mysql' ),
			'status' => 'active'
		) );
		echo '<div class="notice notice-success"><p>Generated: <code>' . $key . '</code></p></div>';
	}

	if ( isset( $_GET['toggle_status'] ) && check_admin_referer( 'toggle_license_' . $_GET['toggle_status'] ) ) {
		$wpdb->update( $wpdb->prefix . 'lfm_licenses',
			array( 'status' => $_GET['status'] === 'active' ? 'inactive' : 'active' ),
			array( 'id' => $_GET['toggle_status'] )
		);
	}

	if ( isset( $_GET['delete_license'] ) && check_admin_referer( 'delete_license_' . $_GET['delete_license'] ) ) {
		$wpdb->delete( $wpdb->prefix . 'lfm_licenses', array( 'id' => $_GET['delete_license'] ) );
	}

	$licenses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lfm_licenses ORDER BY created_at DESC" );
	?>

	<h2>Active Licenses</h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th>Key</th>
				<th>Customer</th>
				<th>Type</th>
				<th>Domain</th>
				<th>Status</th>
				<th>Expires</th>
				<th>Activated</th>
				<th>Actions</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $licenses as $l ) : ?>
				<tr>
					<td><code><?php echo esc_html( $l->license_key ); ?></code></td>
					<td><?php echo esc_html( $l->customer_name ); ?></td>
					<td><span class="status-badge"><?php echo strtoupper( $l->license_type ); ?></span></td>
					<td><?php echo esc_html( $l->domain ?: 'Not yet' ); ?></td>
					<td>
						<span class="status-badge <?php echo $l->status; ?>" style="background: <?php echo $l->status === 'active' ? '#dcfce7' : '#fee2e2'; ?>; color: <?php echo $l->status === 'active' ? '#166534' : '#991b1b'; ?>; padding: 4px 8px; border-radius: 4px;">
							<?php echo ucfirst( $l->status ); ?>
						</span>
					</td>
					<td><?php echo esc_html( $l->expires_at ?: 'Never' ); ?></td>
					<td><?php echo esc_html( $l->activated_at ?: '-' ); ?></td>
					<td>
						<a href="<?php echo wp_nonce_url( "?page=lf-licenses&toggle_status={$l->id}&status={$l->status}", 'toggle_license_' . $l->id ); ?>" class="button button-small">
							<?php echo $l->status === 'active' ? 'Disable' : 'Enable'; ?>
						</a>
						<a href="<?php echo wp_nonce_url( "?page=lf-licenses&delete_license={$l->id}", 'delete_license_' . $l->id ); ?>" class="button button-small" onclick="return confirm('Delete this license?');" style="color:#d63638;">Delete</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>

	<div id="logSection" class="tab-content" style="display:none;">
		<h2>System Logs</h2>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>Key</th><th>Action</th><th>Domain</th><th>Result</th><th>Time</th></tr></thead>
			<tbody>
				<?php
				$logs = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}lfm_logs ORDER BY created_at DESC LIMIT 50" );
				foreach ($logs as $log) {
					echo "<tr><td><code>" . esc_html($log->license_key) . "</code></td><td>" . esc_html(strtoupper($log->action)) . "</td><td>" . esc_html($log->domain) . "</td><td>" . esc_html($log->result) . "</td><td>" . esc_html($log->created_at) . "</td></tr>";
				}
				?>
			</tbody>
		</table>
	</div>

	<script>
	jQuery(document).ready(function($) {
		$('.tab-btn').on('click', function() {
			$('.tab-btn').removeClass('active button-primary');
			$(this).addClass('active button-primary');
			$('.tab-content').hide();
			$('#' + $(this).data('target')).show();
		});
	});
	</script>
</div>
