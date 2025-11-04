# Data Retention and Deletion Policy

**Version:** 1.0
**Last Updated:** 2025-11-04
**Compliance:** SOC2 (CC7.3, CC8.3), GDPR Articles 5, 17, 20

## Overview

This document outlines the data retention and deletion policies for the HubSpot Ecommerce plugin. These policies ensure compliance with SOC2 and GDPR requirements while maintaining data integrity for business operations.

## SOC2 Controls Addressed

- **CC7.3** - System Operations: Defines and operates systematic retention and destruction of data
- **CC8.3** - Change Management: Identifies and manages changes to data lifecycle

## Data Categories and Retention Periods

### 1. Cart Sessions

**Storage Location:** `wp_hubspot_cart_items` table
**Retention Period:** 30 days
**Legal Basis:** Legitimate interest (facilitating purchases)

- **Description:** Session-based shopping cart data
- **Data Points:** Session ID, product IDs, quantities, prices, timestamps
- **Cleanup Trigger:** Automated daily cleanup of sessions older than 30 days
- **Rationale:** Cart abandonment typically occurs within 24-48 hours; 30 days provides ample recovery time

### 2. Orders

**Storage Location:** `hs_order` post type + HubSpot deals
**Retention Period:** 7 years
**Legal Basis:** Legal obligation (tax and financial record requirements)

- **Description:** Completed customer orders and transactions
- **Data Points:** Order details, billing information, line items, payment status
- **Cleanup Trigger:** Manual review after 7 years (automated notification)
- **Rationale:** IRS and most jurisdictions require 7 years of financial record retention

### 3. Audit Logs

**Storage Location:** `wp_options` table (transient logs) + Archive storage
**Retention Period:** 90 days active, 1 year archived
**Legal Basis:** Legitimate interest (security and compliance monitoring)

- **Description:** System activity logs, API calls, security events
- **Data Points:** Timestamps, user IDs, action types, IP addresses, results
- **Cleanup Trigger:**
  - Daily: Move logs older than 90 days to archive
  - Monthly: Delete archived logs older than 1 year
- **Rationale:** SOC2 requires monitoring and incident response capabilities

### 4. OAuth Tokens

**Storage Location:** `wp_options` table (encrypted)
**Retention Period:** Until revoked
**Legal Basis:** Contractual necessity (HubSpot integration)

- **Description:** HubSpot OAuth access and refresh tokens
- **Data Points:** Encrypted tokens, expiration timestamps, scopes
- **Cleanup Trigger:** Manual revocation or automatic on token expiration
- **Rationale:** Required for ongoing HubSpot API integration

### 5. Error Logs

**Storage Location:** WordPress debug.log + `wp_options` transients
**Retention Period:** 30 days
**Legal Basis:** Legitimate interest (system maintenance)

- **Description:** Application errors, warnings, debugging information
- **Data Points:** Error messages, stack traces, timestamps
- **Cleanup Trigger:** Daily cleanup of logs older than 30 days
- **Rationale:** Recent errors are useful for debugging; older logs have diminishing value

### 6. Customer Contact Information

**Storage Location:** WordPress users + HubSpot contacts
**Retention Period:** Duration of customer relationship + 30 days
**Legal Basis:** Contractual necessity, consent (for marketing)

- **Description:** Customer profiles, contact details, preferences
- **Data Points:** Name, email, address, phone, metadata
- **Cleanup Trigger:** 30 days after account deletion request
- **Rationale:** GDPR right to erasure with grace period for accidental deletions

## GDPR Rights Implementation

### Right to Access (Article 15)

**Endpoint:** `GET /wp-json/hubspot-ecommerce/v1/gdpr/export`
**Authentication:** WordPress user authentication
**Response Format:** JSON or CSV

Provides customers with:
- All personal data stored in WordPress
- Order history
- Cart session data
- Subscription preferences
- Audit log entries containing their data

### Right to Erasure (Article 17)

**Endpoint:** `POST /wp-json/hubspot-ecommerce/v1/gdpr/delete`
**Authentication:** WordPress user authentication + confirmation token
**Process:**

1. User requests deletion via account dashboard
2. System sends confirmation email with unique token
3. User confirms deletion within 7 days
4. System performs deletion:
   - WordPress user account (anonymized if has orders)
   - Cart sessions
   - Non-financial metadata
   - HubSpot contact (optional, configurable)
5. Financial records (orders) are retained for 7 years with anonymized customer data

**Exceptions:**
- Financial records required by law (anonymized instead of deleted)
- Active legal disputes
- Fraud prevention (minimal data retention)

### Right to Data Portability (Article 20)

**Endpoint:** `GET /wp-json/hubspot-ecommerce/v1/gdpr/export`
**Format:** Machine-readable JSON or CSV
**Contents:**
- Customer profile
- Complete order history
- Email subscription preferences
- Account metadata

## Automated Cleanup Processes

### Daily Cleanup (WP-Cron)

**Schedule:** Once daily at 3:00 AM server time
**Hook:** `hubspot_ecommerce_daily_cleanup`
**Actions:**
1. Delete cart sessions older than 30 days
2. Delete error logs older than 30 days
3. Archive audit logs older than 90 days
4. Clean up expired transients

### Monthly Cleanup (WP-Cron)

**Schedule:** First day of each month at 3:00 AM
**Hook:** `hubspot_ecommerce_monthly_cleanup`
**Actions:**
1. Delete archived audit logs older than 1 year
2. Generate retention compliance report
3. Send notifications for orders approaching 7-year mark

### Manual Cleanup (Admin Interface)

**Location:** HubSpot Shop → Privacy Tools
**Capabilities:**
- View retention statistics
- Manually trigger cleanup jobs
- Export compliance reports
- Review pending deletion requests

## Privacy Policy Integration

### WordPress Privacy Tools Integration

The plugin registers with WordPress privacy features:

1. **Privacy Policy Text:** Pre-written policy text available in Settings → Privacy
2. **Personal Data Exporter:** Integrated with WordPress data export tool
3. **Personal Data Eraser:** Integrated with WordPress data erasure tool
4. **Suggestion Text:** Automatically suggests privacy policy content

### Required Privacy Policy Disclosures

Your privacy policy must include:

1. **Data Collection:**
   - What data is collected (orders, carts, contact info)
   - How it's collected (forms, cookies, HubSpot)
   - Why it's collected (order processing, marketing)

2. **Third-Party Sharing:**
   - Data synced to HubSpot CRM
   - HubSpot's privacy policy reference
   - Payment processor information

3. **Data Retention:**
   - Retention periods for each data type
   - Legal basis for retention
   - User rights (access, deletion, portability)

4. **User Rights:**
   - How to request data export
   - How to request data deletion
   - Contact information for privacy requests

## Technical Implementation

### Database Schema

```sql
-- Cart items with automatic timestamps
CREATE TABLE wp_hubspot_cart_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    hubspot_product_id VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY session_id (session_id),
    KEY product_id (product_id),
    KEY created_at (created_at)
);

-- Audit log table
CREATE TABLE wp_hubspot_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    object_type VARCHAR(50),
    object_id BIGINT UNSIGNED,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY user_id (user_id),
    KEY action (action),
    KEY created_at (created_at)
);
```

### Cleanup Functions

See implementation in:
- `includes/class-data-cleanup.php` - Automated cleanup logic
- `includes/class-gdpr-handler.php` - GDPR endpoint handlers
- `includes/admin/class-privacy-tools.php` - Admin interface

## Compliance Verification

### SOC2 Evidence

The following evidence demonstrates compliance:

1. **Documented Policy:** This retention policy document
2. **Automated Controls:** WP-Cron scheduled cleanup jobs
3. **Access Logs:** Audit trail of data access and modifications
4. **Deletion Verification:** Logs proving data was deleted per policy
5. **Admin Interface:** Screenshots of privacy tools dashboard

### GDPR Compliance Checklist

- [x] Data inventory documented
- [x] Retention periods defined with legal basis
- [x] Automated deletion processes implemented
- [x] User data export functionality
- [x] User data deletion functionality
- [x] Data portability (machine-readable format)
- [x] Privacy policy integration
- [x] Consent management (for marketing subscriptions)
- [x] Breach notification procedures (via audit logs)

## Monitoring and Reporting

### Metrics Tracked

1. **Cleanup Operations:**
   - Records deleted per cleanup run
   - Errors encountered
   - Execution time

2. **GDPR Requests:**
   - Number of export requests
   - Number of deletion requests
   - Response time (must be < 30 days)

3. **Data Volume:**
   - Active cart sessions
   - Total orders
   - Audit log size
   - Records approaching retention limit

### Reports Available

1. **Daily Cleanup Report:** Email to admin after each cleanup
2. **Monthly Compliance Report:** PDF summary of retention compliance
3. **GDPR Request Log:** Searchable log of all privacy requests
4. **Retention Dashboard:** Real-time statistics in admin panel

## Emergency Procedures

### Data Breach Response

1. **Detection:** Audit logs identify unauthorized access
2. **Containment:** Revoke OAuth tokens, reset sessions
3. **Assessment:** Identify compromised data categories
4. **Notification:** Email affected users within 72 hours (GDPR requirement)
5. **Remediation:** Apply security patches, review access controls
6. **Documentation:** Record incident in audit log

### Bulk Deletion Request

If user requests immediate deletion of all data:

1. Verify identity via email confirmation
2. Export data for user download
3. Execute deletion scripts
4. Anonymize financial records
5. Confirm deletion to user
6. Document in audit log

## Updates and Revisions

This policy will be reviewed and updated:

- Annually as part of compliance audit
- When regulations change (GDPR amendments, new privacy laws)
- After data breaches or security incidents
- When new data categories are added to the plugin

**Revision History:**

| Version | Date       | Changes                          | Author     |
|---------|------------|----------------------------------|------------|
| 1.0     | 2025-11-04 | Initial policy creation          | Todd Baur  |

## Contact Information

For privacy-related questions or requests:

**Data Protection Contact:** privacy@baursoftware.com
**Technical Support:** support@baursoftware.com
**Website:** https://baursoftware.com

## References

- [SOC2 Framework - AICPA](https://www.aicpa.org/interestareas/frc/assuranceadvisoryservices/aicpasoc2report.html)
- [GDPR Official Text](https://gdpr-info.eu/)
- [WordPress Privacy Features](https://wordpress.org/support/article/privacy/)
- [HubSpot Privacy Policy](https://legal.hubspot.com/privacy-policy)
