# Incident Response Templates

**Version:** 1.0
**Last Updated:** 2025-01-24
**SOC2 Control:** CC8.1 - Incident Response
**Owner:** Security Team

---

## Purpose

This document provides standardized templates for incident response activities, ensuring consistent documentation and communication throughout the incident lifecycle.

---

## Table of Contents

1. [Initial Incident Report](#initial-incident-report)
2. [Incident Status Updates](#incident-status-updates)
3. [Customer Notification Templates](#customer-notification-templates)
4. [Post-Incident Review Template](#post-incident-review-template)
5. [GitHub Security Issue Template](#github-security-issue-template)
6. [Slack Notification Templates](#slack-notification-templates)

---

## Initial Incident Report

### Template: Initial Detection

**Incident ID:** [Auto-generated or YYYYMMDD-NNNN]
**Date/Time Detected:** [YYYY-MM-DD HH:MM UTC]
**Detected By:** [Name/System]
**Classification:** [P0/P1/P2/P3]
**Status:** OPEN

**Incident Summary:**
[Brief description of what was detected]

**Affected Systems:**
- [ ] WordPress Plugin (core)
- [ ] OAuth Integration
- [ ] License Server
- [ ] Customer Database
- [ ] WooCommerce Integration
- [ ] HubSpot API
- [ ] Other: ___________

**Initial Assessment:**
- Number of customers potentially affected: [Number or "Unknown"]
- Data types potentially exposed: [List or "Under investigation"]
- Geographic regions: [Regions or "All"]
- Active Exploitation: [Yes/Suspected/No/Unknown]
- Data Accessed: [Yes/Suspected/No/Under investigation]

**Immediate Actions Taken:**
1. [Action 1]
2. [Action 2]
3. [Action 3]

**Next Steps:**
1. [Next step 1]
2. [Next step 2]
3. [Next step 3]

**Notifications Sent:**
- [x] Security Lead - [Time]
- [ ] Incident Commander - [Time]
- [ ] CTO - [Time]
- [ ] CEO - [Time]
- [ ] Legal - [Time]

**Evidence Preserved:**
- [ ] Application logs
- [ ] Database logs
- [ ] Access logs
- [ ] Screenshots
- [ ] Network captures
- [ ] Other: ___________

**Reporter:** [Name]
**Report Time:** [YYYY-MM-DD HH:MM UTC]

---

## Incident Status Updates

### Template: Hourly Status Update (P0/P1)

**Incident ID:** [ID]
**Update Time:** [YYYY-MM-DD HH:MM UTC]
**Phase:** [Detection/Containment/Investigation/Eradication/Recovery]
**Updated By:** [Name]

**Current Status:**
[Brief summary of current state]

**Progress Since Last Update:**
- [Accomplishment 1]
- [Accomplishment 2]
- [Accomplishment 3]

**New Findings:**
- [Finding 1]
- [Finding 2]

**Current Actions:**
- [Who] is [doing what]
- [Who] is [doing what]

**Blockers:**
- [Blocker 1 - if any]
- [Blocker 2 - if any]

**Next Update:** Expected in [N] hours at [HH:MM UTC]

### Template: Daily Status Update (P2/P3)

**Incident ID:** [ID]
**Status:** [Open/In Progress/Resolved]
**Days Open:** [N]

**Summary:**
[Brief summary of incident and current state]

**Progress Today:**
- [Progress item 1]
- [Progress item 2]

**Plan for Tomorrow:**
- [Planned action 1]
- [Planned action 2]

**Issues/Blockers:**
- [Issue 1 - if any]

**ETA for Resolution:** [Date] or "Under assessment"

---

## Customer Notification Templates

### Template: Critical Incident Notification (P0/P1)

**Subject:** Important Security Notice - [Brief Description]

Dear [Customer Name],

We are writing to inform you of a security incident that may affect your Vibe Kanban installation.

WHAT HAPPENED:
On [Date], we discovered [brief description of incident]. [If known: This incident occurred between [start date] and [end date]].

WHAT INFORMATION WAS INVOLVED:
[Specific data types - be clear and honest]
- [Data type 1]
- [Data type 2]

WHAT WE ARE DOING:
We have taken immediate action to address this issue:
- [Action 1]
- [Action 2]
- [Action 3]

The vulnerability has been [fixed/contained/addressed] as of [date/time].

WHAT YOU SHOULD DO:
We recommend you take the following actions:
1. [Action 1 - e.g., Update to version X.X.X immediately]
2. [Action 2 - e.g., Review your HubSpot connection logs]
3. [Action 3 - e.g., Reset OAuth tokens if you notice suspicious activity]

[If applicable]
If you believe your data has been accessed without authorization, we recommend:
- Notifying affected individuals
- Reviewing your local privacy regulations
- Contacting our support team for additional information

MORE INFORMATION:
For more information or assistance, please:
- Email: security@vibekanban.com
- Support: [support URL]
- Reference: Incident #[ID]

We take the security of your data very seriously and deeply regret this incident. We have implemented additional safeguards to prevent similar incidents in the future.

We will provide updates as we learn more. Expected next update: [date/time].

Sincerely,
[Name]
[Title]
Vibe Kanban Security Team

### Template: Non-Critical Security Update (P2/P3)

**Subject:** Security Update Available - [Brief Description]

Dear Vibe Kanban User,

We are writing to inform you of a security update for the Vibe Kanban plugin.

WHAT WAS DISCOVERED:
We identified [brief description] that could [potential impact].

WHAT IS THE RISK:
[Honest assessment of risk level and likelihood]

WHAT WE HAVE DONE:
We have released version [X.X.X] which addresses this issue. This update includes:
- [Fix 1]
- [Fix 2]

WHAT YOU SHOULD DO:
1. Update to version [X.X.X] at your earliest convenience
2. [Any additional recommended actions]

TIMELINE:
- Issue discovered: [Date]
- Fix developed and tested: [Date]
- Update released: [Date]

Thank you for using Vibe Kanban. If you have any questions, please contact our support team.

Best regards,
Vibe Kanban Security Team

### Template: Incident Resolution Notification

**Subject:** Security Incident Resolved - [Brief Description]

Dear [Customer Name],

This is a follow-up to our previous communication regarding [incident description].

RESOLUTION:
We are pleased to inform you that this security incident has been fully resolved as of [date/time].

WHAT WE DID:
- [Action 1]
- [Action 2]
- [Action 3]

VERIFICATION:
We have conducted thorough testing and monitoring to confirm:
- The vulnerability has been eliminated
- No ongoing unauthorized access
- Systems are operating securely
- [Additional verifications]

PREVENTIVE MEASURES:
To prevent similar incidents in the future, we have:
- [Preventive measure 1]
- [Preventive measure 2]
- [Preventive measure 3]

WHAT YOU SHOULD VERIFY:
Please confirm:
1. [Verification step 1]
2. [Verification step 2]

If you have any concerns or questions, please contact our security team at security@vibekanban.com.

Thank you for your patience and understanding during this incident.

Sincerely,
[Name]
[Title]
Vibe Kanban Security Team

---

## Post-Incident Review Template

**Incident ID:** [ID]
**Date of Incident:** [YYYY-MM-DD]
**Date of Review:** [YYYY-MM-DD]
**Review Attendees:** [Names and roles]
**Incident Commander:** [Name]

### Executive Summary

[2-3 paragraph summary suitable for executive leadership and auditors]

**Key Metrics:**
- Time to detection: [X hours/minutes]
- Time to containment: [X hours/minutes]
- Time to resolution: [X hours/days]
- Customers affected: [Number]
- Data exposed: [Yes/No - what types]
- Regulatory notifications required: [Yes/No - which]

### Incident Overview

**What Happened:**
[Detailed description of the incident]

**Root Cause:**
[Detailed root cause analysis]

**Contributing Factors:**
1. [Factor 1]
2. [Factor 2]
3. [Factor 3]

### Timeline of Events

| Date/Time (UTC) | Phase | Event | Actor |
|-----------------|-------|-------|-------|
| YYYY-MM-DD HH:MM | Pre-incident | [Event] | [Who/What] |
| YYYY-MM-DD HH:MM | Initial compromise | [Event] | [Who/What] |
| YYYY-MM-DD HH:MM | Detection | [Event] | [Who/What] |
| YYYY-MM-DD HH:MM | Containment | [Event] | [Who/What] |
| YYYY-MM-DD HH:MM | Investigation | [Event] | [Who/What] |
| YYYY-MM-DD HH:MM | Eradication | [Event] | [Who/What] |
| YYYY-MM-DD HH:MM | Recovery | [Event] | [Who/What] |
| YYYY-MM-DD HH:MM | Resolution | [Event] | [Who/What] |

### Impact Assessment

**Customers Affected:**
- Total customers: [Number]
- Confirmed data access: [Number]
- Notified: [Number]
- Support tickets: [Number]

**Data Involved:**
- [ ] Contact information
- [ ] HubSpot API tokens
- [ ] License keys
- [ ] WordPress credentials
- [ ] Other: ___________

**Volume:** [Approximate number of records]

**Business Impact:**
- Service downtime: [Duration]
- Customer churn: [Number or %]
- Revenue impact: [$Amount]
- Brand reputation: [Assessment]
- Regulatory penalties: [$Amount or None]

### Response Effectiveness

**What Went Well:**
1. [Success 1]
2. [Success 2]
3. [Success 3]

**What Didn't Go Well:**
1. [Challenge 1]
2. [Challenge 2]
3. [Challenge 3]

**Gaps Identified:**
1. [Gap 1]
2. [Gap 2]
3. [Gap 3]

### Lessons Learned

**Technical Lessons:**
1. [Lesson 1]
2. [Lesson 2]

**Process Lessons:**
1. [Lesson 1]
2. [Lesson 2]

**Communication Lessons:**
1. [Lesson 1]
2. [Lesson 2]

### Action Items

| # | Action | Owner | Priority | Due Date | Status |
|---|--------|-------|----------|----------|--------|
| 1 | [Action 1] | [Name] | P0/P1/P2/P3 | YYYY-MM-DD | Open |
| 2 | [Action 2] | [Name] | P0/P1/P2/P3 | YYYY-MM-DD | Open |
| 3 | [Action 3] | [Name] | P0/P1/P2/P3 | YYYY-MM-DD | Open |

**Preventive Measures (Long-term):**
1. [Measure 1]
2. [Measure 2]
3. [Measure 3]

**Detection Improvements:**
1. [Improvement 1]
2. [Improvement 2]

**Response Improvements:**
1. [Improvement 1]
2. [Improvement 2]

### Compliance Review

**SOC2 Requirements Met:**
- [x] Incident documented within 24 hours
- [x] Classification assigned appropriately
- [x] Response timeline followed
- [x] Customer notification (if required) within 72 hours
- [x] Evidence preserved
- [x] Post-incident review completed within 7 days

**Regulatory Notifications:**
- **GDPR:** [Required/Not Required - if required, date notified]
- **State Laws:** [Required/Not Required - details]
- **Other:** [Details]

**Audit Trail:**
- Incident reports: [Location/link]
- Communication records: [Location/link]
- Evidence: [Location/link]
- Remediation documentation: [Location/link]

### Recommendations

**Immediate (Next 30 Days):**
1. [Recommendation 1]
2. [Recommendation 2]

**Short-term (Next Quarter):**
1. [Recommendation 1]
2. [Recommendation 2]

**Long-term (Next Year):**
1. [Recommendation 1]
2. [Recommendation 2]

### Sign-off

**Incident Commander:**
- Name: ___________
- Signature: ___________
- Date: ___________

**Security Lead:**
- Name: ___________
- Signature: ___________
- Date: ___________

**CTO:**
- Name: ___________
- Signature: ___________
- Date: ___________

**Document Status:** Final
**Retention Period:** 7 years
**Distribution:** Security team, Management, Compliance, Auditors

---

## GitHub Security Issue Template

**Name:** Security Incident
**About:** Track a security incident or vulnerability
**Title:** [SECURITY] [P0/P1/P2/P3] Brief description
**Labels:** security-incident, priority-high
**Assignees:** @security-lead

**Incident Classification:**
- **Priority:** P0 / P1 / P2 / P3
- **Status:** 🔴 Open / 🟡 In Progress / 🟢 Resolved
- **Incident ID:** [YYYYMMDD-NNNN]
- **Detected:** [YYYY-MM-DD HH:MM UTC]

**Description:**
[Clear description of the security incident or vulnerability]

**Affected Systems:**
- [ ] WordPress Plugin Core
- [ ] OAuth Integration
- [ ] License Server
- [ ] HubSpot API
- [ ] WooCommerce Integration
- [ ] Other: ___________

**Impact Assessment:**
- **Customers Affected:** [Number or "TBD"]
- **Data at Risk:** [Description]
- **Active Exploitation:** Yes / No / Unknown

**Immediate Actions Taken:**
- [ ] [Action 1]
- [ ] [Action 2]
- [ ] [Action 3]

**Investigation Plan:**
- [ ] Review logs
- [ ] Identify root cause
- [ ] Assess full scope
- [ ] Determine timeline

**Remediation Plan:**
- [ ] Develop fix
- [ ] Test in staging
- [ ] Deploy to production
- [ ] Verify effectiveness

**Communication:**
- [ ] Security Lead notified
- [ ] Incident Commander assigned
- [ ] Customers notified (if required)
- [ ] Post-incident review scheduled

**Related Issues/PRs:**
- Related to #[issue number]
- Fixed by #[PR number]

**Evidence:**
[Links to logs, screenshots, or other evidence]

**Updates:**
[YYYY-MM-DD HH:MM UTC]: [Status update]

**CONFIDENTIAL** - Restricted access until resolved and disclosed

---

## Slack Notification Templates

### Template: Critical Incident Alert

🚨 **SECURITY INCIDENT - P0** 🚨

**Incident ID:** INC-20250124-0001
**Time:** 2025-01-24 14:30 UTC
**Detected by:** [Name/System]

**Summary:** [Brief one-line description]

**Impact:**
• Customers affected: [Number]
• Systems: [List]
• Data at risk: [Type]

**Status:** Containment in progress

**Incident Commander:** @[username]
**War room:** #security-incident-0001

**Actions:**
✅ Security team notified
✅ Systems isolated
⏳ Investigation ongoing

Next update in 1 hour.

cc: @security-team @management

### Template: Incident Status Update

📊 **Incident Update #3** - INC-20250124-0001

**Time:** 2025-01-24 16:30 UTC
**Phase:** Investigation
**Updated by:** @[username]

**Progress:**
✅ Root cause identified
✅ Scope assessment complete
✅ Fix developed and tested

**Findings:**
• [Key finding 1]
• [Key finding 2]

**Next Steps:**
⏳ Deploy fix to production (ETA: 30 min)
⏳ Begin customer notifications

**ETA to Resolution:** 18:00 UTC

Next update in 1 hour or when deployed.

### Template: Incident Resolution

✅ **INCIDENT RESOLVED** - INC-20250124-0001

**Resolution Time:** 2025-01-24 18:00 UTC
**Duration:** 3.5 hours
**Resolved by:** @[username]

**Summary:**
[Brief description of incident and resolution]

**Actions Completed:**
✅ Vulnerability patched
✅ Systems secured
✅ Customers notified
✅ Monitoring enhanced

**Metrics:**
• Time to detection: 15 minutes
• Time to containment: 45 minutes
• Time to resolution: 3.5 hours
• Customers affected: [Number]

**Next Steps:**
• Post-incident review: [Date/Time]
• Action items tracking: [Link]

Thank you to everyone involved!

Incident channel will remain open for 24 hours for any follow-up questions.

---

## Usage Guidelines

### When to Use Each Template

1. **Initial Incident Report** - Immediately upon detection
2. **Status Updates** - Hourly for P0/P1, daily for P2/P3
3. **Customer Notifications** - Within 4 hours for P0/P1, within 24 hours for P2
4. **Post-Incident Review** - Within 7 days of resolution
5. **GitHub Issue** - For tracking and transparency (private repo only)
6. **Slack Notifications** - Real-time team coordination

### Customization

- Replace [bracketed] placeholders with actual information
- Add sections as needed for specific incident types
- Remove non-applicable sections
- Maintain professional, clear, honest tone
- Focus on facts, not speculation

### Distribution

**Internal:**
- Incident reports: Security team, management
- Status updates: All relevant teams
- Post-incident reviews: All teams, filed for audit

**External:**
- Customer notifications: Affected customers only
- Public disclosure: Only after resolution and coordinated

---

## Document Control

**Review Frequency:** After each significant incident or quarterly

**Approval Required From:**
- Security Lead
- Customer Success Lead
- Legal/Compliance

**Version History:**
- v1.0 - 2025-01-24 - Initial templates for SOC2 compliance

---

## Related Documentation

- [Incident Response Procedures](./INCIDENT_RESPONSE.md)
- [Security Contacts](./SECURITY_CONTACTS.md)
- [Security Hardening](./SECURITY_HARDENING_COMPLETE.md)

---

**Document Status:** Production Ready
**Next Review Date:** 2025-04-24
