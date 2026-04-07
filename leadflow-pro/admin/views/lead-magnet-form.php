<div class="leadflow-audit-form-container" style="background:#fff; padding:30px; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
	<h3 style="margin-top:0; color:#1e293b;">Get Your Free Website Audit</h3>
	<p style="color:#64748b;">Enter your details below and our AI will analyze your site and send you a detailed performance report within minutes.</p>
	<form id="leadflowAuditForm">
		<div style="margin-bottom:15px;">
			<label style="display:block; margin-bottom:5px; font-weight:600;">Your Name</label>
			<input type="text" name="first_name" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
		</div>
		<div style="margin-bottom:15px;">
			<label style="display:block; margin-bottom:5px; font-weight:600;">Business Name</label>
			<input type="text" name="business_name" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
		</div>
		<div style="margin-bottom:15px;">
			<label style="display:block; margin-bottom:5px; font-weight:600;">Website URL</label>
			<input type="url" name="website_url" placeholder="https://example.com" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
		</div>
		<div style="margin-bottom:20px;">
			<label style="display:block; margin-bottom:5px; font-weight:600;">Email Address</label>
			<input type="email" name="email" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
		</div>
		<button type="submit" style="width:100%; padding:12px; background:#6366f1; color:#fff; border:none; border-radius:6px; font-weight:700; cursor:pointer;">Generate My Free Audit</button>
	</form>
	<div id="leadflowFormSuccess" style="display:none; margin-top:20px; padding:15px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; color:#166534; text-align:center;">
		<?php echo esc_html( get_option( 'leadflow_magnet_success', 'Success! Your audit is being generated and will be emailed to you shortly.' ) ); ?>
	</div>
</div>
