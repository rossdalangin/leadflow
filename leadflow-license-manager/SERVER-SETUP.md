# LeadFlow License Server Setup Guide

This guide is for the SaaS owner/operator. Follow these steps to set up your central licensing authority.

## 1. Installation
1. Zip the `leadflow-license-manager` folder.
2. Upload and Activate it on your primary business WordPress site (e.g., `hub.youragency.com`).
3. Upon activation, the plugin will create the `lfm_licenses` and `lfm_logs` database tables.

## 2. Configuration
1. Navigate to **LF Licenses** in your WordPress admin.
2. Note the **REST API Endpoint** displayed in the "API Connectivity Details" box.
3. This is the URL your customers will need to enter in their LeadFlow Pro settings.

## 3. Generating Licenses
1. Use the "Generate New License" form.
2. Enter the **Customer Name**.
3. Select the **License Type** (Pro or Agency).
4. Set an **Expiration Date** if you are selling a subscription.
5. Click **Generate & Save**.
6. Copy the resulting key (e.g., `LF-ABCD-EFGH-IJKL`) and send it to your customer.

## 4. Managing Customers
- **Disable:** If a customer cancels, click "Disable" to instantly revoke their access to Pro features.
- **Reset Domain:** If a customer wants to move their license to a new site, you can delete their record and generate a new one, or manually clear the `domain` field in the database (Feature coming in v1.1).
- **Audit Logs:** Check the "Activity Logs" tab to see activation attempts and validation requests in real-time.

## 5. Security Note
The licensing API is open by design to allow remote activation. Ensure your server has basic security (SSL is mandatory) to protect customer metadata.
