<div class="wrap leadflow-templates">
	<h1 class="wp-heading-inline">Email Templates</h1>
	<a href="#" class="page-title-action" id="addNewTemplateBtn">Add New Template</a>
	<hr class="wp-header-end">

	<div class="leadflow-template-list">
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th>Template Name</th>
					<th>Subject Line</th>
					<th>Created</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody id="templateListBody">
				<!-- Populated by JS -->
			</tbody>
		</table>
	</div>

	<!-- Template Modal -->
	<div id="templateModal" class="leadflow-modal" style="display:none;">
		<div class="modal-content">
			<h2 id="templateModalTitle">Add New Template</h2>
			<form id="templateForm">
				<input type="hidden" name="id" id="templateId">
				<p><label>Template Name</label><br><input type="text" name="name" id="templateName" required class="regular-text"></p>
				<p><label>Default Subject</label><br><input type="text" name="subject" id="templateSubject" required class="regular-text"></p>
				<p><label>Email Body</label><br><textarea name="body" id="templateBody" rows="10" style="width:100%;"></textarea></p>
				<p class="description">Available tokens: {{first_name}}, {{business_name}}, {{website}}, {{city}}, {{audit_flag}}</p>
				<p><button type="submit" class="button button-primary">Save Template</button> <button type="button" class="button close-modal">Cancel</button></p>
			</form>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		const apiUrl = leadflowData.apiUrl;
		const nonce = leadflowData.nonce;

		function fetchTemplates() {
			$.ajax({
				url: apiUrl + '/templates',
				method: 'GET',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
				success: function(data) {
					const tbody = $('#templateListBody');
					tbody.empty();
					data.forEach(t => {
						tbody.append(`
							<tr>
								<td><strong>${t.name}</strong></td>
								<td>${t.subject}</td>
								<td>${t.created_at}</td>
								<td>
									<button class="button button-small edit-template" data-id="${t.id}">Edit</button>
									<button class="button button-small delete-template" data-id="${t.id}" style="color:#d63638;">Delete</button>
								</td>
							</tr>
						`);
					});
				}
			});
		}

		fetchTemplates();

		$('#addNewTemplateBtn').on('click', function(e) {
			e.preventDefault();
			$('#templateId').val('');
			$('#templateForm')[0].reset();
			$('#templateModalTitle').text('Add New Template');
			$('#templateModal').fadeIn();
		});

		$(document).on('click', '.edit-template', function() {
			const id = $(this).data('id');
			$.ajax({
				url: apiUrl + '/templates',
				method: 'GET',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
				success: function(data) {
					const t = data.find(x => x.id == id);
					if (t) {
						$('#templateId').val(t.id);
						$('#templateName').val(t.name);
						$('#templateSubject').val(t.subject);
						$('#templateBody').val(t.body);
						$('#templateModalTitle').text('Edit Template');
						$('#templateModal').fadeIn();
					}
				}
			});
		});

		$('#templateForm').on('submit', function(e) {
			e.preventDefault();
			const id = $('#templateId').val();
			const data = {
				name: $('#templateName').val(),
				subject: $('#templateSubject').val(),
				body: $('#templateBody').val()
			};

			const url = id ? apiUrl + '/templates/' + id : apiUrl + '/templates';
			$.ajax({
				url: url,
				method: 'POST',
				data: data,
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
				success: function() {
					$('#templateModal').fadeOut();
					fetchTemplates();
				}
			});
		});

		$(document).on('click', '.delete-template', function() {
			if (!confirm('Delete this template?')) return;
			const id = $(this).data('id');
			$.ajax({
				url: apiUrl + '/templates/' + id,
				method: 'DELETE',
				beforeSend: function(xhr) { xhr.setRequestHeader('X-WP-Nonce', nonce); },
				success: function() { fetchTemplates(); }
			});
		});
	});
	</script>
</div>
