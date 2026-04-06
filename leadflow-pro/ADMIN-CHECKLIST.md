# 🏁 LeadFlow Pro: Admin Launch & Management Checklist

This guide ensures you are managing your new LeadFlow Pro installation like a pro. Follow these steps to go from zero to your first automated client.

---

## 1. The 5-Minute Setup (Critical)
- [ ] **License Activation:** Go to Settings -> License and enter your key. This unlocks the Kanban board and bulk actions.
- [ ] **API Connectivity:**
    - Enter your Google Places API key (General tab).
    - Enter your OpenAI or Gemini key (AI Provider tab).
    - Use the "Test Connection" buttons to ensure they work.
- [ ] **Email Setup:** Configure your SMTP settings. Send a test email to your own address to verify deliverability.

## 2. Populating Your Pipeline
- [ ] **Discovery:** Use the Discovery Engine to find 50 leads in your target niche.
- [ ] **Import:** Use the "Import Selected" bulk action to move them into your CRM.
- [ ] **Wait for Audits:** LeadFlow Pro will automatically start auditing these sites in the background. Check back in 10 minutes.

## 3. Launching Your First Campaign
- [ ] **Drafting:** Go to Campaigns -> Create New.
- [ ] **AI Magic:** Use the "AI: Write this for me" button to generate your sequence steps. Ensure you use the `{{audit_flag}}` token for maximum conversion.
- [ ] **Activate:** Set the campaign to 'Active'. The engine will now start contacting leads who match your filter (e.g., 'New').

## 4. Daily Management Tasks
- [ ] **Inbox Review:** Check the Unified Inbox for new replies.
- [ ] **Sentiment Check:** Look for the 'Positive' sentiment tags added by AI to prioritize your responses.
- [ ] **Pipeline Management:** Move leads through the Kanban board as they progress from 'Replied' to 'Qualified.'
- [ ] **Bulk Cleanup:** Periodically use the 'Bulk Delete' feature in the Leads table to remove 'Closed Lost' or invalid leads.

## 5. Scaling Your Growth
- [ ] **Analytics Review:** Once a week, check your Dashboard. If your open rate is low, try a new AI-generated subject line.
- [ ] **Social Media:** Use the provided templates in `SOCIAL-POSTS.md` to drive traffic to your own landing page (use the included `landing-page.html`).

---

**Admin Tip:** If you ever need to reset your demo data, you can re-trigger the seeder by adding `&leadflow_seed=1` to your dashboard URL.
