# LeadFlow Pro - Plugin Documentation

## 1. Plugin Overview
- **Name:** LeadFlow Pro
- **Tagline:** The All-in-One Autonomous Lead Generation & CRM for WordPress
- **Elevator Pitch:** LeadFlow Pro is a production-ready WordPress plugin designed for freelancers and agencies to automate the entire client acquisition process. From lead discovery and automated website auditing to multi-step outreach sequences and a built-in CRM, it turns a WordPress site into a powerful, self-sustaining lead generation machine.
- **Target User:** Web design agencies, freelancers, consultants, and B2B service providers.
- **Core Value Proposition:** Eliminate the manual grind of prospecting and outreach. LeadFlow Pro automates lead discovery, qualifies them with AI-driven insights, and manages the entire sales pipeline inside the familiar WordPress dashboard.

## 2. File Structure
- `leadflow-pro/leadflow-pro.php`: Main plugin bootstrap and metadata.
- `leadflow-pro/includes/class-leadflow-core.php`: Main plugin controller, handles initialization and hooks.
- `leadflow-pro/includes/class-leadflow-loader.php`: Orchestrates all actions and filters.
- `leadflow-pro/includes/class-leadflow-activator.php`: Handles plugin activation logic (e.g., DB creation).
- `leadflow-pro/includes/class-leadflow-security.php`: AES-256 CTR encryption for all API keys.
- `leadflow-pro/includes/class-leadflow-deliverability.php`: SPF/DKIM/DMARC health check engine.
- `leadflow-pro/includes/class-leadflow-logger.php`: Centralized system activity logger.
- `leadflow-pro/includes/class-leadflow-webhooks.php`: Multi-event webhook dispatcher.
- `leadflow-pro/includes/class-leadflow-license.php`: License key validation and Pro-gate logic.
- `leadflow-pro/modules/discovery/`: Lead discovery and enrichment (Hunter/Clearbit) engine.
- `leadflow-pro/modules/scraper/class-leadflow-scraper.php`: Background website auditor and crawler.
- `leadflow-pro/modules/crm/class-leadflow-crm.php`: Lead management logic and custom DB interactions.
- `leadflow-pro/modules/outreach/class-leadflow-outreach.php`: Campaign and sequence automation engine.
- `leadflow-pro/modules/email/class-leadflow-email.php`: SMTP sending, IMAP inboxing, and threading.
- `leadflow-pro/modules/ai/class-leadflow-ai.php`: Unified AI adapter (router).
- `leadflow-pro/modules/ai/class-leadflow-ai-openai.php`: OpenAI (GPT-4o) specific adapter.
- `leadflow-pro/modules/ai/class-leadflow-ai-gemini.php`: Google Gemini 1.5 Pro specific adapter.
- `leadflow-pro/modules/analytics/class-leadflow-analytics.php`: Data aggregation and reporting logic.
- `leadflow-pro/modules/compliance/`: GDPR, opt-out, and scraping ethics logic.
- `leadflow-pro/admin/views/dashboard.php`: Main analytics dashboard with ROI forecasting.
- `leadflow-pro/admin/views/leads.php`: CRM lead list and Kanban view.
- `leadflow-pro/admin/views/tasks.php`: Global team task management board.
- `leadflow-pro/admin/views/setup-wizard.php`: Interactive onboarding experience.
- `leadflow-pro/admin/views/audit-report.php`: Branded sales collateral generator.
- `leadflow-pro/admin/views/campaigns.php`: Outreach campaign management.
- `leadflow-pro/admin/views/inbox.php`: Unified communication inbox.
- `leadflow-pro/admin/views/settings.php`: General and SMTP settings.
- `leadflow-pro/admin/views/settings-ai.php`: AI provider configuration and usage stats.
- `leadflow-pro/admin/js/leadflow-admin.js`: Core JavaScript for interactivity.
- `leadflow-pro/admin/css/leadflow-admin.css`: Plugin styling.
- `leadflow-pro/api/class-leadflow-rest-api.php`: REST API endpoints registration.
- `leadflow-pro/database/class-leadflow-db.php`: Custom DB schema and migrations.
