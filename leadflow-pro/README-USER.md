# LeadFlow Pro — Documentation & Guides

## 1. REST API Reference

All endpoints are prefixed with `/wp-json/leadflow/v1`.

### `GET /leads`
- **Description:** Retrieve a list of leads.
- **Parameters:** `status` (string), `search` (string), `limit` (int), `offset` (int).
- **Response:** Array of lead objects.

### `POST /leads`
- **Description:** Create a new lead.
- **Parameters:** `business_name`, `website_url`, `email`.
- **Response:** `{ "id": 123 }`.

### `POST /ai/complete`
- **Description:** Get a completion from the active AI provider.
- **Parameters:** `prompt` (string), `context` (array).
- **Response:** `{ "result": "The generated text..." }`.

---

## 2. UI Wireframe Descriptions

### Dashboard
- **Layout:** Grid-based KPI cards at the top, followed by interactive charts (Chart.js) for lead status and outreach performance.
- **Interactions:** Sparklines on KPI cards show 7-day trends. Clicking a status in the chart filters the lead list.

### Lead CRM
- **Layout:** Split view. Left sidebar for filters/search, main area for Table or Kanban board.
- **Interactions:** Drag-and-drop leads between columns (Kanban). Inline status editing in Table view. Click lead to open Sidebar with audit notes.

### Campaign Builder
- **Layout:** Multi-step wizard. Step 1: Settings, Step 2: Sequence Builder.
- **Interactions:** "Add Step" adds a new card with subject/body fields. "AI: Write" button triggers a modal for prompt selection.

---

## 3. Step-by-Step Installation & Setup Guide

1. **Upload:** Upload the `leadflow-pro` folder to your `/wp-content/plugins/` directory via FTP or WordPress Admin.
2. **Activate:** Navigate to Plugins -> Installed Plugins and click **Activate** on LeadFlow Pro.
3. **Database:** Upon activation, LeadFlow Pro will automatically create 9 custom database tables.
4. **Settings:** Go to LeadFlow Pro -> Settings.
   - **General:** Add your Google Places API Key for Discovery.
   - **SMTP:** Configure your "From" address and SMTP server for outreach.
   - **AI:** Choose OpenAI or Gemini and add your API Key.
5. **First Lead:** Go to Leads -> Add New Lead. Enter a website URL to trigger the automated auditor.
6. **Automate:** Build your first campaign, set a status filter (e.g., 'New'), and watch the engine take over.

---

## 4. AI Provider Setup Guide

### OpenAI (ChatGPT)
- **Key:** Obtain an API Key from [platform.openai.com](https://platform.openai.com/api-keys).
- **Configuration:** Select "OpenAI" in LeadFlow Settings and paste the key.
- **Dashboard:** Monitor usage at [platform.openai.com/usage](https://platform.openai.com/usage).

### Google Gemini
- **Key:** Obtain an API Key from [Google AI Studio](https://aistudio.google.com/app/apikey).
- **Configuration:** Select "Gemini" in LeadFlow Settings and paste the key.
- **Dashboard:** Monitor usage in the [Google Cloud Console](https://console.cloud.google.com/).

---

## 5. Monetization & Pro Gates

### License Key Flow
- Users enter their key in the **License** tab of Settings.
- The `LeadFlow_License::validate_license()` method calls our remote API to check status.
- Once active, the `leadflow_license_status` option is set to `active`.

### Pro Gate Enforcement
- All Pro-only code is wrapped in `if ( LeadFlow_License::is_pro() )`.
- Limits for Free users (e.g., 50 leads) are checked via `LeadFlow_License::check_limit()`.
- UI elements for Pro features are visually greyed out or shown with a lock icon.

---

## 6. Roadmap

- **v1.1 (Next Month):** Native LinkedIn integration via OAuth. Enhanced email threading.
- **v1.2:** Multi-user team permissions. Advanced lead scoring using custom AI training.
- **v2.0 (Q4):** White-label reporting for agencies. Automated client proposal generation.
