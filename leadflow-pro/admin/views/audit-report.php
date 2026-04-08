<?php
/**
 * Audit Report View - A clean, printable report for leads.
 *
 * DESIGN NOTES:
 * This view is meant to be shown in a new tab or iframe for easy PDF export.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$lead_id = isset( $_GET['lead_id'] ) ? absint( $_GET['lead_id'] ) : 0;
global $wpdb;
$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}leadflow_leads WHERE id = %d", $lead_id ) );

if ( ! $lead ) wp_die( 'Lead not found.' );

$audit = json_decode( $lead->audit_data, true ) ?: array();
$custom_name = get_option( 'leadflow_custom_name', 'LeadFlow Pro' );
$custom_color = get_option( 'leadflow_custom_color', '#6366f1' );
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Website Performance Audit - <?php echo esc_html( $lead->business_name ); ?></title>
	<style>
		:root { --primary: <?php echo $custom_color; ?>; }
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 40px auto; padding: 20px; background: #f4f7f6; }
		.report-card { background: #fff; padding: 50px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
		header { border-bottom: 2px solid #eee; padding-bottom: 30px; margin-bottom: 40px; display: flex; justify-content: space-between; align-items: center; }
		h1 { color: var(--primary); margin: 0; font-size: 24px; }
		.status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 40px; }
		.status-item { padding: 20px; border-radius: 8px; background: #f9f9f9; border-left: 4px solid #ddd; }
		.status-item.success { border-left-color: #10b981; }
		.status-item.danger { border-left-color: #ef4444; }
		.status-item h4 { margin: 0 0 5px; text-transform: uppercase; font-size: 12px; color: #666; }
		.status-item p { margin: 0; font-weight: bold; font-size: 18px; }
		.cta-box { margin-top: 50px; padding: 30px; background: var(--primary); color: #fff; border-radius: 8px; text-align: center; }
		@media print { .no-print { display: none; } body { background: #fff; margin: 0; } .report-card { box-shadow: none; } }
	</style>
</head>
<body>
	<div class="no-print" style="margin-bottom: 20px; text-align: right;">
		<button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">🖨️ Print to PDF</button>
	</div>

	<div class="report-card">
		<header>
			<div>
				<h4>WEBSITE AUDIT REPORT</h4>
				<h1><?php echo esc_html( $lead->business_name ); ?></h1>
			</div>
			<div style="text-align: right; font-weight: bold; color: var(--primary);">
				<?php echo esc_html( $custom_name ); ?>
			</div>
		</header>

		<div class="status-grid">
			<div class="status-item <?php echo $audit['has_ssl'] ? 'success' : 'danger'; ?>">
				<h4>SSL Security</h4>
				<p><?php echo $audit['has_ssl'] ? '✅ Secure (SSL Found)' : '❌ Unsecured Connection'; ?></p>
			</div>
			<div class="status-item <?php echo $audit['is_mobile_responsive'] ? 'success' : 'danger'; ?>">
				<h4>Mobile Friendly</h4>
				<p><?php echo $audit['is_mobile_responsive'] ? '✅ Mobile Ready' : '❌ Issues Detected'; ?></p>
			</div>
			<div class="status-item <?php echo (isset($audit['outdated_design']) && $audit['outdated_design']) ? 'danger' : 'success'; ?>">
				<h4>Design Signals</h4>
				<p><?php echo (isset($audit['outdated_design']) && $audit['outdated_design']) ? '❌ Outdated Signals' : '✅ Modern Design'; ?></p>
			</div>
			<div class="status-item">
				<h4>Load Time</h4>
				<p>⏱️ <?php echo $audit['load_time']; ?> Seconds</p>
			</div>
		</div>

		<div class="analysis">
			<h3>Technical Analysis</h3>
			<p>We performed a deep crawl of <strong><?php echo esc_url( $lead->website_url ); ?></strong> to identify growth opportunities. Here are our findings:</p>
			<ul>
				<li><strong>CMS:</strong> <?php echo esc_html( $audit['cms'] ); ?></li>
				<li><strong>Contact Form:</strong> <?php echo $audit['has_contact_form'] ? 'Detected' : 'Not Found'; ?></li>
				<?php if ( ! empty( $audit['page_builder'] ) ) : ?>
					<li><strong>Page Builder:</strong> <?php echo esc_html( $audit['page_builder'] ); ?></li>
				<?php endif; ?>
			</ul>
		</div>

		<div class="cta-box">
			<h2>Want to fix these issues?</h2>
			<p>We help businesses like yours improve their online presence and conversion rates.</p>
			<div style="font-weight: bold; margin-top: 15px;">Contact us today to schedule a free strategy call.</div>
		</div>
	</div>
</body>
</html>
