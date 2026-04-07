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
Create a new lead.
- **Body:**
  - `business_name` (required): string
  - `email`: string
  - `website_url`: string
  - `first_name`: string

### `POST /leads/(?P<id>\d+)`
Update an existing lead.

## Outreach Module

### `GET /campaigns`
List all outreach campaigns.

### `POST /campaigns`
Create a campaign with sequence steps.
- **Body:**
  - `name`: string
  - `status_filter`: string
  - `steps`: array of step objects

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
