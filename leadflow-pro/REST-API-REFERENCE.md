# LeadFlow Pro REST API Reference

All endpoints are prefixed with `/wp-json/leadflow/v1`. Authentication is required via Nonce for all state-changing operations.

## CRM Module

### `GET /leads`
Fetch leads with filtering.
- **Parameters:**
  - `status`: Filter by status (e.g., 'New', 'Qualified')
  - `search`: Search by business name or email
  - `limit`: Number of records (default 20)
  - `offset`: Pagination offset

### `POST /leads`
Create a new lead. Also triggers background audit if website URL is provided.

### `POST /leads/(?P<id>\d+)`
Update an existing lead record. Automatically updates Lead Score.

### `POST /leads/(?P<id>\d+)/enrich`
Manually trigger Hunter.io/Clearbit enrichment for a lead.

### `GET /leads/(?P<id>\d+)/activity`
Get combined activity log (emails + notes) for a lead.

### `POST /leads/(?P<id>\d+)/activity`
Add a manual note to a lead.

### `GET /leads/(?P<id>\d+)/tasks`
Get all tasks assigned to a specific lead.

### `POST /leads/(?P<id>\d+)/tasks`
Create a new manual task for a lead.

### `POST /tasks/(?P<id>\d+)`
Update task status (pending/completed).

### `DELETE /tasks/(?P<id>\d+)`
Permanently delete a task.

### `GET /tags`
Fetch all available lead tags.

### `POST /leads/(?P<id>\d+)/tags`
Update tags associated with a lead.

## Outreach & Templates Module
## Outreach & Templates Module

### `GET /campaigns`
List all outreach campaigns.

### `POST /campaigns`
Create or update a campaign with sequence steps.
- **Body:**
  - `name`: string
  - `status_filter`: string
  - `start_hour`: int (0-23)
  - `end_hour`: int (0-23)
  - `skip_weekends`: int (0,1)
  - `steps`: array of step objects (type: email, linkedin, call, etc.)

### `GET /outreach/queue`
Fetch the current background sending queue.

### `GET /templates`
Fetch all reusable email templates.

### `POST /templates`
Create a new email template.

## Discovery Module

### `GET /discovery/search`
Run a Google Places search for leads.
- **Parameters:** `keyword`, `location`

### `GET /discovery/social`
Run a LinkedIn/Facebook search for leads.
- **Parameters:** `keyword`, `source`

### `POST /discovery/import-csv`
Bulk import leads from a CSV file.

### `GET /discovery/saved-searches`
Fetch all persisted search parameters.

## Inbox Module

### `GET /inbox`
Fetch all leads who have replied, sorted by latest activity.

### `POST /inbox/reply`
Send an outbound reply to a lead and log it.

## Settings & License Module

### `POST /settings/test-email`
Send a test email to verify SMTP/Gmail config.

### `POST /settings/revoke-gmail`
Disconnect Gmail API account.

### `GET /settings/domain-health`
Perform DNS checks for SPF/DKIM/DMARC.

### `GET /settings/logs`
Fetch the latest system background logs.

## Analytics Module

### `GET /analytics/overview`
Get high-level KPIs and chart data.

### `GET /analytics/report`
Download a full CSV ROI report.

## AI Module

### `POST /ai/complete`
Route prompt to active AI provider (OpenAI or Gemini).
- **Body:**
  - `prompt`: string
  - `context`: object (feature, lead_id, etc.)

## Analytics Module

### `GET /analytics/overview`
Get high-level KPIs and chart data.

### `GET /analytics/activity`
Get recent activity feed.

---
*Generated for LeadFlow Pro v1.0.0*
