<div class="wrap leadflow-inbox">
	<h1 class="wp-heading-inline">Unified Inbox</h1>
	<p class="description">Centralize your lead communications. AI automatically analyzes the sentiment of incoming replies to help you prioritize your follow-ups.</p>
	<hr class="wp-header-end">

	<div class="leadflow-inbox-container">
		<div class="inbox-list" id="inboxList">
			<div class="inbox-search">
				<input type="text" id="inboxSearchInput" placeholder="Search conversations...">
			</div>
			<div class="inbox-items" id="inboxItems">
				<!-- Inbox items populated by JS -->
				<div class="inbox-item" data-id="1" data-lead-id="12">
					<div class="inbox-item-header">
						<span class="inbox-item-lead">Acme Corp</span>
						<span class="inbox-item-time">2 mins ago</span>
					</div>
					<div class="inbox-item-excerpt">
						"Thanks for reaching out! We'd love to chat more about..."
					</div>
					<div class="inbox-item-sentiment positive">Positive</div>
				</div>
				<div class="inbox-item" data-id="2" data-lead-id="15">
					<div class="inbox-item-header">
						<span class="inbox-item-lead">John Doe</span>
						<span class="inbox-item-time">15 mins ago</span>
					</div>
					<div class="inbox-item-excerpt">
						"Not interested at this time. Please remove me..."
					</div>
					<div class="inbox-item-sentiment unsubscribe">Unsubscribe</div>
				</div>
			</div>
		</div>

		<div class="inbox-view" id="inboxView">
			<div class="inbox-view-header" id="inboxViewHeader">
				<div class="lead-info">
					<h3 id="viewLeadName">Select a conversation</h3>
					<p id="viewLeadWebsite"></p>
				</div>
				<div class="lead-actions">
					<button class="button button-small" id="updateLeadStatusBtn">Update Status</button>
					<button class="button button-small" id="viewLeadBtn">View Lead</button>
				</div>
			</div>

			<div class="inbox-thread" id="inboxThread">
				<!-- Message thread populated by JS -->
				<div class="inbox-placeholder">Select a conversation from the list to view messages.</div>
			</div>

			<div class="inbox-reply" id="inboxReply" style="display:none;">
				<div class="reply-toolbar">
					<button class="button button-small ai-reply-btn" title="AI: Suggest Reply">✨ AI: Suggest Reply</button>
					<button class="button button-small ai-write-personalized-btn" title="AI: Write Personalized Email">✨ AI: Personalized Email</button>
				</div>
				<textarea id="replyText" rows="4" placeholder="Write your reply..."></textarea>
				<div class="reply-actions">
					<button class="button button-primary" id="sendReplyBtn">Send Reply</button>
				</div>
			</div>
		</div>

		<div class="inbox-sidebar" id="inboxSidebar">
			<h3>Lead Overview</h3>
			<div id="leadSidebarContent">
				<p>Select a lead to see audit results and insights. Our smart auditor automatically extracts emails, social links, and technical hooks from the website.</p>
			</div>
			<div id="aiLeadTools" style="display:none; margin-top:20px;">
				<button class="button button-small ai-score-btn">✨ AI: Score Lead</button>
				<button class="button button-small ai-summarize-btn" style="margin-top:10px;">✨ AI: Summarize Audit</button>
			</div>
			<div class="tag-tools" style="margin-top:20px; border-top:1px solid #eee; padding-top:15px;">
				<h4>Lead Tags</h4>
				<div id="leadTagsList"></div>
				<select id="addTagSelect">
					<option value="">+ Add Tag</option>
				</select>
			</div>
			<div class="gdpr-tools" style="margin-top:30px; border-top:1px solid #eee; padding-top:15px;">
				<h4>GDPR Compliance</h4>
				<button class="button button-small export-data-btn">Export Data</button>
				<button class="button button-small delete-lead-btn" style="color:#d63638;">Delete Lead</button>
			</div>
		</div>
	</div>
</div>
