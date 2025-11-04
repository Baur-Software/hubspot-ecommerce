# Security Contacts and Escalation Paths

**Version:** 1.0
**Last Updated:** 2025-01-24
**SOC2 Control:** CC8.1 - Incident Response
**Owner:** Security Team
**Confidentiality:** INTERNAL USE ONLY

---

## Purpose

This document provides contact information and escalation procedures for security incidents. This information is critical for rapid response to security events and must be kept up-to-date.

---

## Emergency Contact Summary

### Quick Reference - Critical Incidents (P0)

**Primary Contact:** Security Lead
- **Name:** [TO BE CONFIGURED]
- **Phone:** [TO BE CONFIGURED]
- **Email:** security@vibekanban.com
- **Slack:** @security-lead
- **Availability:** 24/7 for P0 incidents

**Secondary Contact:** CTO
- **Name:** [TO BE CONFIGURED]
- **Phone:** [TO BE CONFIGURED]
- **Email:** [TO BE CONFIGURED]
- **Slack:** @cto
- **Availability:** 24/7 for P0 incidents

**Tertiary Contact:** CEO
- **Name:** [TO BE CONFIGURED]
- **Phone:** [TO BE CONFIGURED]
- **Email:** [TO BE CONFIGURED]
- **Slack:** @ceo
- **Availability:** 24/7 for P0 incidents

---

## Escalation Matrix

### By Incident Priority

| Priority | Initial Contact | Response Time | Escalate To | Escalation Time |
|----------|----------------|---------------|-------------|-----------------|
| **P0** | Security Lead | 0-15 min | CTO + CEO | 15 min |
| **P1** | Security Lead | 15-60 min | CTO | 2 hours |
| **P2** | Security Team | 1-4 hours | Security Lead | 24 hours |
| **P3** | Security Team | 4-24 hours | Security Lead | 72 hours |

### By Impact Type

| Impact Type | Immediate Notification | Additional Contacts |
|-------------|----------------------|---------------------|
| Data Breach | Security Lead, CTO, CEO, Legal | PR/Communications |
| OAuth Compromise | Security Lead, CTO | HubSpot Support |
| License Server | Security Lead, DevOps | WooCommerce Support |
| Customer Data Access | Security Lead, CTO, Customer Success | Legal |
| Plugin Distribution | Security Lead, CTO | WordPress.org Team |

---

## Internal Security Team

### Security Lead
**Primary security incident response owner**

- **Name:** [TO BE CONFIGURED]
- **Title:** Security Lead
- **Email:** [TO BE CONFIGURED]
- **Phone:** [TO BE CONFIGURED]
- **Slack:** @security-lead
- **GitHub:** @[username]
- **Availability:** 24/7 for P0/P1
- **Backup:** CTO

**Responsibilities:**
- Initial incident assessment
- Incident classification
- Technical investigation leadership
- Containment strategy
- Coordination with development team
- Post-incident review ownership

---

### Chief Technology Officer (CTO)
**Technical leadership and architecture decisions**

- **Name:** [TO BE CONFIGURED]
- **Title:** CTO
- **Email:** [TO BE CONFIGURED]
- **Phone:** [TO BE CONFIGURED]
- **Slack:** @cto
- **GitHub:** @[username]
- **Availability:** 24/7 for P0, business hours for P1+
- **Backup:** CEO

**Responsibilities:**
- Major incident escalation point
- Architecture and infrastructure decisions
- Resource allocation
- External technical communication
- Vendor coordination

---

### Development Team Lead
**Code review and remediation**

- **Name:** [TO BE CONFIGURED]
- **Title:** Lead Developer
- **Email:** [TO BE CONFIGURED]
- **Phone:** [TO BE CONFIGURED]
- **Slack:** @dev-lead
- **GitHub:** @[username]
- **Availability:** Business hours + on-call rotation
- **Backup:** Senior Developer

**Responsibilities:**
- Code vulnerability analysis
- Patch development
- Security testing
- Deployment coordination
- Technical documentation

---

### DevOps/Infrastructure Lead
**Infrastructure and hosting management**

- **Name:** [TO BE CONFIGURED]
- **Title:** DevOps Lead
- **Email:** [TO BE CONFIGURED]
- **Phone:** [TO BE CONFIGURED]
- **Slack:** @devops-lead
- **Availability:** Business hours + on-call rotation
- **Backup:** CTO

**Responsibilities:**
- Server and hosting security
- Access control management
- Backup and recovery
- Monitoring and alerting
- Infrastructure containment

---

## Management and Executive Team

### Chief Executive Officer (CEO)
**Final decision authority and customer communication**

- **Name:** [TO BE CONFIGURED]
- **Title:** CEO
- **Email:** [TO BE CONFIGURED]
- **Phone:** [TO BE CONFIGURED]
- **Slack:** @ceo
- **Availability:** 24/7 for P0, business hours for P1

**Responsibilities:**
- Final incident response decisions
- Customer communication (major incidents)
- Regulatory notification decisions
- Press/media communication
- Board notification

---

### Customer Success Lead
**Customer communication and support coordination**

- **Name:** [TO BE CONFIGURED]
- **Title:** Customer Success Lead
- **Email:** support@vibekanban.com
- **Phone:** [TO BE CONFIGURED]
- **Slack:** @cs-lead
- **Availability:** Business hours + on-call for P0/P1

**Responsibilities:**
- Customer notification execution
- Support ticket management
- Customer communication strategy
- Impact tracking
- Customer feedback collection

---

## Legal and Compliance

### Legal Counsel
**Legal guidance and regulatory compliance**

- **Name:** [TO BE CONFIGURED]
- **Firm:** [TO BE CONFIGURED]
- **Email:** [TO BE CONFIGURED]
- **Phone:** [TO BE CONFIGURED]
- **Availability:** Business hours + emergency contact for P0

**Responsibilities:**
- Legal implications assessment
- Regulatory notification requirements
- Customer notification review
- Law enforcement liaison
- Contract review

---

### Privacy Officer
**Data protection and privacy compliance**

- **Name:** [TO BE CONFIGURED]
- **Title:** Privacy Officer / DPO
- **Email:** privacy@vibekanban.com
- **Phone:** [TO BE CONFIGURED]
- **Availability:** Business hours + contact for P0/P1

**Responsibilities:**
- GDPR compliance
- Data breach notifications
- Privacy impact assessment
- Customer privacy inquiries
- Regulatory authority liaison

---

## External Contacts

### Hosting Provider - WP Engine

**Support Contact:**
- **Support Portal:** https://my.wpengine.com/support
- **Phone:** +1-877-973-6446 (24/7)
- **Email:** support@wpengine.com
- **Emergency:** Premium support line (if applicable)

**Account Manager:**
- **Name:** [TO BE CONFIGURED]
- **Email:** [TO BE CONFIGURED]
- **Phone:** [TO BE CONFIGURED]

**Security Team:**
- **Email:** security@wpengine.com
- **Use for:** Infrastructure security incidents, DDoS, server compromise

---

### OAuth Provider - HubSpot

**Developer Support:**
- **Portal:** https://developers.hubspot.com/community
- **Email:** developers@hubspot.com
- **Documentation:** https://developers.hubspot.com/docs/api/oauth

**Security Team:**
- **Email:** security@hubspot.com
- **Use for:** OAuth compromise, API abuse, token leakage

**Account Manager:**
- **Name:** [TO BE CONFIGURED]
- **Email:** [TO BE CONFIGURED]
- **Phone:** [TO BE CONFIGURED]

---

### WordPress.org Security Team

**Security Issues:**
- **Email:** plugins@wordpress.org
- **Security Team:** security@wordpress.org
- **Use for:** Plugin vulnerabilities, malicious updates, distribution issues

**Plugin Review Team:**
- **Email:** plugins@wordpress.org
- **Forum:** https://wordpress.org/support/plugin/vibe-kanban/

---

### Payment Processor - WooCommerce/Stripe

**WooCommerce Support:**
- **Email:** support@woocommerce.com
- **Portal:** https://woocommerce.com/my-account/

**Stripe Security:**
- **Email:** security@stripe.com
- **Phone:** [Check Stripe dashboard]
- **Use for:** Payment data concerns, fraud alerts

---

### Security Researchers

**Responsible Disclosure:**
- **Email:** security@vibekanban.com
- **PGP Key:** [TO BE CONFIGURED]
- **Bug Bounty:** [TO BE CONFIGURED if applicable]
- **Response SLA:** 48 hours acknowledgment

**Coordinated Disclosure:**
- Follow 90-day disclosure policy
- CVE coordination if applicable
- Public acknowledgment (with permission)

---

## Government and Regulatory Authorities

### Data Protection Authorities

**EU - GDPR:**
- **Authority:** Relevant EU Member State DPA
- **Portal:** [Varies by country]
- **Notification Required:** Within 72 hours of awareness
- **Contact:** [Based on company location]

**US - State Authorities:**
- **California AG:** https://oag.ca.gov/privacy/databreach/reporting
- **Other States:** [As required by customer location]
- **Notification Required:** [Varies by state law]

### Law Enforcement

**FBI Cyber Division:**
- **IC3:** https://www.ic3.gov/ (Internet Crime Complaint Center)
- **Phone:** [Local FBI field office]
- **Use for:** Criminal activity, data theft, extortion

**Local Law Enforcement:**
- **Department:** [TO BE CONFIGURED]
- **Contact:** [TO BE CONFIGURED]
- **Use for:** Physical threats, criminal activity

---

## Communication Channels

### Slack Channels

**#security-incident**
- **Purpose:** Active incident coordination
- **Members:** Security team, management, relevant developers
- **Privacy:** Private channel
- **Retention:** 90 days (then archived)

**#security-general**
- **Purpose:** Security discussions, vulnerability reports
- **Members:** All engineering
- **Privacy:** Private channel

**#customer-success**
- **Purpose:** Customer communication coordination
- **Members:** CS team, management
- **Used for:** Customer notification planning

### Email Distribution Lists

**security@vibekanban.com**
- **Members:** Security team, CTO, CEO
- **Purpose:** Incoming security reports, notifications
- **Monitored:** 24/7
- **Response SLA:** 24 hours (48 hours for non-critical)

**security-team@vibekanban.com**
- **Members:** Security team members
- **Purpose:** Internal security team communication
- **Monitored:** Business hours

**incident-response@vibekanban.com**
- **Members:** Security team, CTO, CEO, Legal
- **Purpose:** Active incident communication
- **Activated:** When P0/P1 incident declared

### Phone/SMS

**Emergency Phone Tree (P0):**
1. Call Security Lead
2. If no response in 5 minutes → Call CTO
3. If no response in 5 minutes → Call CEO
4. Document all attempts

**Text/SMS Alerts:**
- Used for: P0 incidents, off-hours emergencies
- Must acknowledge receipt within 15 minutes

---

## On-Call Rotation

### Security Team Rotation

**Current Week (Week of [Date]):**
- **Primary:** [Name] - [Phone]
- **Secondary:** [Name] - [Phone]

**Next Week (Week of [Date]):**
- **Primary:** [Name] - [Phone]
- **Secondary:** [Name] - [Phone]

**Rotation Schedule:**
- Duration: 1 week per person
- Hours: 24/7 for P0, business hours for P1+
- Handoff: Monday 9:00 AM local time
- Backup: CTO

### Development Team Rotation

**Current Week (Week of [Date]):**
- **Primary:** [Name] - [Phone]
- **Secondary:** [Name] - [Phone]

**Rotation Schedule:**
- Duration: 1 week per person
- Hours: Response for code fixes
- Handoff: Monday 9:00 AM local time

---

## Incident Commander Assignment

### When to Assign Incident Commander

- **Required for:** P0 and P1 incidents
- **Optional for:** P2 incidents (at Security Lead discretion)
- **Not required:** P3 incidents

### Incident Commander Qualifications

- Technical understanding of system architecture
- Experience with incident response
- Authority to make decisions and allocate resources
- Strong communication skills

### Default Incident Commander Assignment

**P0 Incidents:**
- **Primary:** CTO
- **Backup:** Security Lead
- **If unavailable:** CEO

**P1 Incidents:**
- **Primary:** Security Lead
- **Backup:** CTO
- **If unavailable:** Development Lead

---

## Vendor Security Contacts

### Critical Infrastructure Vendors

| Vendor | Service | Security Contact | Account Manager | Support |
|--------|---------|-----------------|-----------------|---------|
| WP Engine | Hosting | security@wpengine.com | [Name] | 24/7 phone |
| HubSpot | OAuth/API | security@hubspot.com | [Name] | Email |
| GitHub | Code hosting | https://github.com/security | N/A | Email |
| CloudFlare | CDN | [If used] | [If used] | [If used] |

### Non-Critical Vendors

| Vendor | Service | Contact | Use Case |
|--------|---------|---------|----------|
| [Vendor] | [Service] | [Contact] | [When to contact] |

---

## External Security Resources

### Security Advisories

**WordPress:**
- **Blog:** https://wordpress.org/news/category/security/
- **RSS:** Subscribe for automatic updates
- **Email:** Subscribe to security mailing list

**PHP:**
- **Security:** https://www.php.net/security/
- **RSS:** Subscribe for updates

**Dependencies:**
- **GitHub Security Alerts:** Enabled on repository
- **Composer Security:** Use `composer audit`

### Security Communities

**WordPress Security:**
- **Wordfence Blog:** https://www.wordfence.com/blog/
- **Patchstack:** https://patchstack.com/
- **WPScan:** https://wpscan.com/

**General Security:**
- **OWASP:** https://owasp.org/
- **NIST:** https://www.nist.gov/cybersecurity
- **SANS:** https://www.sans.org/

---

## Communication Protocols

### Incident Notification Protocol

**P0 - Critical (Immediate):**
1. Call primary contact (phone)
2. Send Slack message to #security-incident
3. Send email to security@vibekanban.com
4. If no response in 5 min, escalate to secondary

**P1 - High (Within 15 min):**
1. Send Slack message to #security-incident
2. Send email to security@vibekanban.com
3. Follow up with phone if no response in 30 min

**P2 - Medium (Within 4 hours):**
1. Send email to security@vibekanban.com
2. Post in #security-general
3. Document in GitHub issue

**P3 - Low (Within 24 hours):**
1. Send email to security@vibekanban.com
2. Document in GitHub issue
3. Add to security backlog

### Status Update Protocol

**P0/P1 Active Incident:**
- Update #security-incident every 2 hours
- Email incident-response@vibekanban.com every 4 hours
- Management briefing every 4 hours (or as requested)

**P2/P3 Active Incident:**
- Daily update in #security-general
- Weekly email to security@vibekanban.com

---

## After-Hours Contact Procedures

### Determining Urgency

**Immediate Contact Required (Phone):**
- Active data breach
- System compromise in progress
- Customer data being accessed
- OAuth credentials leaked
- Critical vulnerability actively exploited

**Can Wait Until Business Hours (Email):**
- Vulnerability discovered (not actively exploited)
- Security scan findings
- Configuration issues
- Minor security concerns

### Making After-Hours Contact

1. **Assess true urgency** - Is immediate response required?
2. **Try primary contact first** - Don't skip levels
3. **Document contact attempts** - Log all calls/messages
4. **Leave clear message** - Priority, brief description, your contact info
5. **Wait 5 minutes between attempts** - Allow time for response
6. **Escalate appropriately** - Follow phone tree

---

## Contact Information Maintenance

### Update Requirements

**Contact information must be updated within 24 hours when:**
- Personnel changes (new hire, departure, role change)
- Phone number changes
- Email address changes
- On-call rotation changes
- Vendor contact changes

### Update Process

1. Update this document (SECURITY_CONTACTS.md)
2. Notify security@vibekanban.com
3. Test new contact information
4. Announce in #security-general
5. Archive old information (don't delete)

### Review Schedule

**Monthly:**
- Verify on-call rotation current
- Test emergency phone tree
- Confirm email addresses active

**Quarterly:**
- Full contact information review
- Update vendor contacts
- Verify external contact information
- Test escalation procedures

**Annually:**
- Full security contact audit
- Update legal/compliance contacts
- Review and update protocols
- Conduct escalation drill

---

## Contact Information Testing

### Regular Testing

**Monthly Phone Tree Test:**
- Security Lead calls all primary contacts
- Verify response time
- Confirm phone numbers work
- Update as needed

**Quarterly Escalation Drill:**
- Simulate P0 incident
- Execute full escalation
- Measure response times
- Document lessons learned

---

## Document Control

**Review Frequency:** Monthly for contacts, quarterly for procedures

**Approval Required From:**
- Security Lead
- CTO
- CEO

**Version History:**
- v1.0 - 2025-01-24 - Initial security contacts for SOC2 compliance

**Access Control:**
- This document contains sensitive contact information
- Distribute only to authorized personnel
- Do not commit actual phone numbers/emails to public repositories
- Maintain separate secure version with actual contact details

---

## Related Documentation

- [Incident Response Procedures](./INCIDENT_RESPONSE.md)
- [Incident Response Templates](./INCIDENT_TEMPLATES.md)
- [Security Hardening](./SECURITY_HARDENING_COMPLETE.md)

---

**Document Status:** Production Ready - Requires Contact Information
**Next Review Date:** 2025-02-24

**NOTE:** This template contains placeholder contact information. Before going into production, replace all [TO BE CONFIGURED] fields with actual contact information and store the completed version in a secure location accessible to incident responders.
