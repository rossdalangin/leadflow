<?php
global $wpdb;
$prefix = $wpdb->prefix . 'leadflow_';
$total_leads = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}leads" );
$active_campaigns = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}campaigns WHERE is_active = 1" );
$total_emails = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}email_log" );
$total_opens = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}email_log WHERE opens_count > 0" );
$open_rate = $total_emails > 0 ? round( ($total_opens / $total_emails) * 100, 1 ) : 0;
$conversions = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}leads WHERE status = 'Qualified'" );
$roi = LeadFlow_Analytics::get_roi_metrics();
?>
<div class="wrap leadflow-dashboard">
	<h1 class="wp-heading-inline">LeadFlow Pro Dashboard</h1>
	<p class="description">Welcome to your lead generation command center. Monitor your funnel health and outreach performance in real-time.</p>
	<div class="dashboard-filters" style="float:right; margin-top:10px;">
		<input type="date" id="statsDateStart"> to <input type="date" id="statsDateEnd">
		<button class="button" id="refreshStats">Refresh</button>
		<a href="<?php echo esc_url( rest_url( 'leadflow/v1/analytics/report' ) ); ?>?_wpnonce=<?php echo wp_create_nonce('wp_rest'); ?>" class="button button-primary">Download ROI Report</a>
	</div>
	<hr class="wp-header-end">

	<div class="leadflow-kpi-grid">
		<!-- KPI Cards: High-level metrics for quick ROI assessment.
		     Pro Tip: Watch the Open Rate; if it drops below 20%, try the AI Subject Line Generator. -->
		<div class="kpi-card">
			<span class="dashicons dashicons-groups" style="font-size:32px; color:var(--leadflow-primary);"></span>
			<h3 title="Total number of leads discovered or imported into your CRM.">Total Leads <span class="dashicons dashicons-editor-help" style="font-size:14px; vertical-align:middle; cursor:help;"></span></h3>
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
		<div class="kpi-card">
			<span class="dashicons dashicons-chart-line" style="font-size:32px; color:var(--leadflow-secondary);"></span>
			<h3 title="Percentage growth in new leads compared to the previous 30-day period.">Lead Velocity <span class="dashicons dashicons-editor-help" style="font-size:14px; vertical-align:middle; cursor:help;"></span></h3>
			<p class="kpi-value" style="color: <?php echo $roi['lead_velocity'] >= 0 ? '#10b981' : '#ef4444'; ?>;">
				<?php echo ( $roi['lead_velocity'] > 0 ? '+' : '' ) . $roi['lead_velocity']; ?>%
			</p>
		</div>
		<div class="kpi-card">
			<span class="dashicons dashicons-money-alt" style="font-size:32px; color:var(--leadflow-accent);"></span>
			<h3>Pipeline Value</h3>
			<p class="kpi-value">$<?php echo number_format($roi['pipeline_value'], 0); ?></p>
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
		<div class="chart-box">
			<h3>Leads by Source</h3>
			<canvas id="leadsSourceChart"></canvas>
		</div>
		<div class="chart-box">
			<h3>Leads by Assignee</h3>
			<canvas id="leadsAssigneeChart"></canvas>
		</div>
		<div class="chart-box">
			<h3>Sentiment Pulse (30d)</h3>
			<canvas id="sentimentPulseChart"></canvas>
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

	<div id="dailyPulse" class="chart-box" style="margin-top:20px; border-left:4px solid var(--leadflow-secondary);">
		<div style="float:right; text-align:right; font-size:0.8rem; color:#64748b;" id="queuePulseStats"></div>
		<h3>Daily Pulse: Leads Needing Attention</h3>
		<table class="wp-list-table widefat fixed striped">
			<thead><tr><th>Lead</th><th>Status</th><th>Reason</th><th>Action</th></tr></thead>
			<tbody id="pulseTableBody">
				<!-- Populated by JS -->
			</tbody>
		</table>
	</div>

	<div style="display:flex; gap:20px; margin-top:20px;">
		<div class="leadflow-recent-activity chart-box" style="flex:2;">
			<div class="ab-testing-insights" style="margin-bottom:30px; border-bottom:1px solid #eee; padding-bottom:20px;">
				<h3>A/B Testing Insights</h3>
				<table class="wp-list-table widefat fixed striped">
					<thead><tr><th>Template</th><th>Sent</th><th>Open %</th><th>Reply %</th></tr></thead>
					<tbody id="abInsightsBody"></tbody>
				</table>
			</div>
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
		<div class="chart-box" style="flex:1;">
			<h3>Growth Forecast</h3>
			<div style="text-align:center; padding:20px;">
				<div style="font-size:2.5rem; font-weight:bold; color:var(--leadflow-primary); margin-bottom:10px;">
					$<?php echo number_format($roi['pipeline_value'] * 1.5, 0); ?>
				</div>
				<p class="description">Estimated next month pipeline based on current velocity.</p>
				<div style="margin-top:20px; text-align:left; background:#f9f9f9; padding:15px; border-radius:8px;">
					<p><strong>Top Strategy:</strong><br>
					<?php echo $roi['lead_velocity'] < 10 ? 'Low velocity. Try a new Discovery keyword.' : 'Velocity is high! Increase AI automation to handle the load.'; ?>
					</p>
					<p><strong>ROI Tip:</strong><br>
					<?php echo $open_rate < 25 ? 'Low open rate detected. Refresh your subject lines with AI.' : 'Strong open rates! Focus on closing techniques.'; ?>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
