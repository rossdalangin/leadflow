<?php
global $wpdb;
$prefix = $wpdb->prefix . 'leadflow_';
$total_leads = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}leads" );
$active_campaigns = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}campaigns WHERE is_active = 1" );
$total_emails = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}email_log" );
$total_opens = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}email_log WHERE opens_count > 0" );
$open_rate = $total_emails > 0 ? round( ($total_opens / $total_emails) * 100, 1 ) : 0;
$conversions = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}leads WHERE status = 'Qualified'" );
?>
<div class="wrap leadflow-dashboard">
	<h1 class="wp-heading-inline">LeadFlow Pro Dashboard</h1>
	<p class="description">Welcome to your lead generation command center. Monitor your funnel health and outreach performance in real-time.</p>
	<div class="dashboard-filters" style="float:right; margin-top:10px;">
		<input type="date" id="statsDateStart"> to <input type="date" id="statsDateEnd">
		<button class="button" id="refreshStats">Refresh</button>
	</div>
	<hr class="wp-header-end">

	<div class="leadflow-kpi-grid">
		<!-- KPI Cards: High-level metrics for quick ROI assessment.
		     Pro Tip: Watch the Open Rate; if it drops below 20%, try the AI Subject Line Generator. -->
		<div class="kpi-card">
			<span class="dashicons dashicons-groups" style="font-size:32px; color:var(--leadflow-primary);"></span>
			<h3>Total Leads</h3>
			<p class="kpi-value"><?php echo esc_html( $total_leads ); ?></p>
		</div>
		<div class="kpi-card">
			<span class="dashicons dashicons-megaphone" style="font-size:32px; color:var(--leadflow-secondary);"></span>
			<h3>Active Campaigns</h3>
			<p class="kpi-value"><?php echo esc_html( $active_campaigns ); ?></p>
		</div>
		<div class="kpi-card">
			<span class="dashicons dashicons-visibility" style="font-size:32px; color:var(--leadflow-accent);"></span>
			<h3>Email Open Rate</h3>
			<p class="kpi-value"><?php echo esc_html( $open_rate ); ?>%</p>
		</div>
		<div class="kpi-card">
			<span class="dashicons dashicons-yes-alt" style="font-size:32px; color:var(--leadflow-success);"></span>
			<h3>Conversions</h3>
			<p class="kpi-value"><?php echo esc_html( $conversions ); ?></p>
		</div>
	</div>

	<div class="leadflow-charts-container">
		<div class="chart-box">
			<h3>Leads by Status</h3>
			<canvas id="leadsStatusChart"></canvas>
		</div>
		<div class="chart-box">
			<h3>Outreach Performance</h3>
			<canvas id="outreachChart"></canvas>
		</div>
	</div>

	<div class="leadflow-campaign-stats chart-box">
		<h3>Campaign Performance</h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Campaign</th>
					<th>Sent</th>
					<th>Opens</th>
					<th>Clicks</th>
					<th>Replies</th>
				</tr>
			</thead>
			<tbody id="campaignStatsBody">
				<!-- Populated by JS -->
			</tbody>
		</table>
	</div>

	<div id="dashboardAiUsage" class="chart-box" style="margin-top: 20px;">
		<h3>Monthly AI Token Usage</h3>
		<div id="aiUsageBars">
			<!-- Populated by JS -->
		</div>
	</div>

	<div class="leadflow-recent-activity">
		<h3>Recent Activity</h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Activity</th>
					<th>Lead</th>
					<th>Time</th>
				</tr>
			</thead>
			<tbody id="recentActivityBody">
				<!-- Populated by JS -->
			</tbody>
		</table>
	</div>
</div>
