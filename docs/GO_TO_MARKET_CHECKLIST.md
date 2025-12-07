# Go-to-Market Checklist for HubSpot Ecommerce Plugin

**Last Updated:** 2025-01-24
**Current Status:** 🟡 Ready for License Server Setup & Launch

---

## Critical Path to Launch (Estimated: 3-5 Days)

### 🔴 CRITICAL - Must Complete Before ANY Launch

#### 1. License Server Setup (4-6 hours)

**Status:** ⏳ Not Started | **Owner:** Todd | **Blocker:** Yes

- [ ] **Install License Manager on baursoftware.com**
  - [ ] Install WooCommerce plugin
  - [ ] Install "License Manager for WooCommerce" plugin
  - [ ] Configure basic WooCommerce settings

- [ ] **Create License Products**
  - [ ] Create "HubSpot Ecommerce Pro" product ($39/month subscription)
  - [ ] Create "HubSpot Ecommerce Enterprise" product ($99/month subscription)
  - [ ] Configure subscription billing

- [ ] **Configure License Generator**
  - [ ] Create generator with pattern: `BSHS-{RANDOM:4}-{RANDOM:4}-{RANDOM:4}`
  - [ ] Set max activations: 1 per license
  - [ ] Set expiration: When subscription cancelled
  - [ ] Link generator to products

- [ ] **Generate REST API Credentials**
  - [ ] Go to License Manager → Settings → REST API
  - [ ] Generate new API key pair
  - [ ] Copy Consumer Key (ck_...)
  - [ ] Copy Consumer Secret (cs_...)
  - [ ] **Add to production wp-config.php** (See: PRODUCTION_SETUP.md)

- [ ] **Test License Flow End-to-End**
  - [ ] Purchase test subscription
  - [ ] Receive license key via email
  - [ ] Activate license in WordPress plugin
  - [ ] Verify Pro tier unlocks
  - [ ] Test subscription cancellation
  - [ ] Verify license expires

**Documentation:** `LICENSE_SERVER_WOOCOMMERCE.md`, `PRODUCTION_SETUP.md`

---

#### 2. Security Verification (1 hour)

**Status:** ✅ Complete | **Owner:** Todd | **Blocker:** No

- [x] Remove hardcoded credentials from code
- [x] Move OAuth credentials to environment variables
- [x] Move license credentials to environment variables
- [x] Verify `.gitignore` excludes sensitive files
- [x] Companion app security verified (no commits yet, config files ignored)
- [ ] **Before GitHub Push:** Review all files for accidental secrets

**Files Changed:**

- `includes/class-oauth-client.php` - OAuth credentials secured
- `includes/class-license-manager.php` - License credentials secured
- `PRODUCTION_SETUP.md` - New deployment guide
- `wp-config-example.php` - Configuration template
- `SECURITY_HARDENING_COMPLETE.md` - Security summary

---

#### 3. Companion App UI (24-40 hours) - OPTIONAL FOR MARKETPLACE

**Status:** ⏳ Not Started | **Owner:** TBD | **Blocker:** Only for HubSpot Marketplace

**NOTE:** This is ONLY required if submitting to HubSpot Marketplace. The WordPress plugin works standalone without the companion app.

- [ ] **Create Settings Card Component (React)**
  - [ ] Display OAuth connection status
  - [ ] Show granted scopes
  - [ ] Display last sync timestamp
  - [ ] Allow disconnect action
  - [ ] Show sync statistics

- [ ] **Set Up Build Process**
  - [ ] Configure webpack/vite
  - [ ] Add React dependencies
  - [ ] Create production build script

- [ ] **Deploy to HubSpot**
  - [ ] Test locally with `hs project upload`
  - [ ] Deploy to HubSpot app account
  - [ ] Verify UI renders correctly

**Can Skip For Now If:**

- Only launching on WordPress.org
- Using WordPress plugin standalone
- Not pursuing HubSpot Marketplace certification

**Repository:** `../hubspot-ecommerce-app/`

---

### 🟡 HIGH PRIORITY - Should Complete for Launch

#### 4. Testing & QA (8-12 hours)

**Status:** 🟡 Partial | **Owner:** Todd

##### WordPress Plugin Tests

- [x] 46 Playwright E2E tests (passing without WordPress)
- [ ] Run full test suite with WordPress + demo mode
- [ ] Test OAuth connection on fresh install
- [ ] Test Free tier functionality
- [ ] Test Pro tier with valid license
- [ ] Test license activation/deactivation
- [ ] Test subscription features
- [ ] Test payment webhook
- [ ] Load test with 1000+ products
- [ ] Security audit (SQL injection, IDOR, XSS)

**Command:** `npm test` (requires WordPress running)

##### Manual Testing Checklist

- [ ] Fresh WordPress install on Local
- [ ] Activate plugin
- [ ] Connect via OAuth
- [ ] Sync products from HubSpot
- [ ] Add products to cart
- [ ] Complete checkout (Free tier - custom payment hook)
- [ ] Activate Pro license
- [ ] Complete checkout (Pro tier - HubSpot Payments)
- [ ] Test subscription management
- [ ] Test email preferences sync

---

#### 5. Documentation (6-8 hours)

**Status:** 🟡 Partial | **Owner:** Todd

- [x] README.md (exists, may need updates)
- [x] TESTING_GUIDE.md (exists)
- [x] SUBSCRIPTIONS.md (exists)
- [x] PRODUCTION_SETUP.md (new - comprehensive)
- [x] wp-config-example.php (new - ready to use)
- [ ] **User-facing documentation site** (baursoftware.com/docs)
  - [ ] Getting Started guide
  - [ ] Free vs Pro comparison
  - [ ] Setup wizard walkthrough
  - [ ] Private App setup guide
  - [ ] Custom payment hook examples (Stripe, PayPal, Square)
  - [ ] Troubleshooting guide
  - [ ] FAQ
- [ ] **Video tutorials** (YouTube)
  - [ ] Quick start (5 min)
  - [ ] OAuth connection (3 min)
  - [ ] License activation (2 min)
  - [ ] Setting up Private App (8 min)

---

#### 6. Marketing & Sales Pages (4-6 hours)

**Status:** ⏳ Not Started | **Owner:** Todd

- [ ] **Landing page** (baursoftware.com/hubspot-ecommerce)
  - [ ] Hero section with value proposition
  - [ ] Feature comparison table (Free vs Pro vs Enterprise)
  - [ ] Pricing section
  - [ ] Screenshots/demo video
  - [ ] Testimonials (collect after beta)
  - [ ] CTA buttons (Buy Now, Try Free)

- [ ] **Pricing page** (baursoftware.com/pricing)
  - [ ] Free tier features
  - [ ] Pro tier ($39/month) features
  - [ ] Enterprise tier ($99/month) features
  - [ ] FAQ section

- [ ] **WordPress.org Assets**
  - [ ] Plugin banner (1544x500, 772x250)
  - [ ] Plugin icon (256x256, 128x128)
  - [ ] Screenshots (up to 10)
  - [ ] Demo video link

---

### 🟢 NICE TO HAVE - Can Launch Without

#### 7. HubSpot Marketplace Submission (Optional)

**Status:** ⏳ Not Started | **Blocker:** Requires companion app UI

**Pre-Certification Requirements (6 months):**

- [ ] 60+ active installs
- [ ] Listed for 6 months
- [ ] OAuth implementation complete ✅
- [ ] Companion app UI complete ❌
- [ ] Privacy policy published
- [ ] Terms of service published

**Can Proceed Without This:**

- Launch on WordPress.org first
- Build user base organically
- Submit to HubSpot Marketplace after hitting 60 installs

---

#### 8. WordPress.org Submission (Recommended)

**Status:** ⏳ Not Started | **Owner:** Todd

- [ ] **Prepare Plugin for Submission**
  - [ ] Review code for WordPress coding standards
  - [ ] Run Plugin Check tool (`wp plugin check hubspot-ecommerce`)
  - [ ] Ensure GPL v2+ compatibility
  - [ ] Remove any "powered by" links (or make opt-in)
  - [ ] Create comprehensive readme.txt
  - [ ] Add Tested up to: 6.7 (or latest)
  - [ ] Add Requires PHP: 8.1

- [ ] **Submit to WordPress.org**
  - [ ] Create SVN repository
  - [ ] Upload plugin files
  - [ ] Upload assets (banner, icon, screenshots)
  - [ ] Submit for review
  - [ ] Wait for approval (typically 1-3 weeks)

**Benefits:**

- Wider audience reach
- Built-in update mechanism
- Credibility and trust
- Free hosting and CDN

**Can Skip If:**

- Selling exclusively on baursoftware.com
- Want faster launch (no review wait time)
- Plan to do manual distribution

---

## Launch Strategies (Pick One)

### Option A: Soft Launch (Recommended - 3 Days)

**Best for:** Testing with real users before public launch

1. ✅ Complete license server setup (Day 1)
2. ✅ Test end-to-end with real licenses (Day 1)
3. ✅ Launch on baursoftware.com only (Day 2)
4. ✅ Beta test with 10-20 users (Weeks 1-2)
5. ✅ Gather feedback and iterate (Weeks 1-2)
6. ✅ Submit to WordPress.org after beta (Week 3)
7. ⏳ Work on HubSpot Marketplace during beta (Month 2-6)

**Timeline:** Launch in 3 days, public release in 3-4 weeks

---

### Option B: WordPress.org First (5-6 Weeks)

**Best for:** Maximum reach and credibility

1. ✅ Complete license server setup (Week 1)
2. ✅ Prepare plugin for WordPress.org (Week 1-2)
3. ✅ Submit to WordPress.org (Week 2)
4. ⏳ Wait for approval (Week 3-5)
5. ✅ Launch marketing campaign (Week 6)
6. ⏳ HubSpot Marketplace later (Month 6+)

**Timeline:** 5-6 weeks to public launch

---

### Option C: All Channels Simultaneously (6+ Months)

**Best for:** Maximum coverage, requires most resources

1. ✅ Complete license server setup (Month 1)
2. ✅ Build companion app UI (Month 1-2)
3. ✅ WordPress.org submission (Month 2)
4. ✅ HubSpot Marketplace submission (unlisted) (Month 2)
5. ⏳ Build to 60 installs (Month 3-6)
6. ✅ Apply for HubSpot certification (Month 6)
7. ✅ Full public launch all channels (Month 6-7)

**Timeline:** 6-7 months to full launch

---

## Recommended Launch Plan: Soft Launch (3 Days)

### Day 1: License Server Setup

- [ ] 9 AM: Install WooCommerce + License Manager on baursoftware.com
- [ ] 10 AM: Create Pro & Enterprise products
- [ ] 11 AM: Configure license generator
- [ ] 12 PM: Generate REST API credentials
- [ ] 1 PM: Add credentials to wp-config.php
- [ ] 2 PM: Purchase test subscription
- [ ] 3 PM: Test license activation in plugin
- [ ] 4 PM: Verify Pro features unlock
- [ ] 5 PM: Test subscription cancellation flow

### Day 2: Final Testing & Soft Launch

- [ ] 9 AM: Run full Playwright test suite
- [ ] 10 AM: Manual QA on baursoftware.com Local site
- [ ] 12 PM: Fix any critical bugs found
- [ ] 2 PM: Create landing page on baursoftware.com
- [ ] 3 PM: Publish plugin page with download link
- [ ] 4 PM: Soft launch announcement (email to personal network)
- [ ] 5 PM: Monitor for support requests

### Day 3: Beta User Onboarding

- [ ] 9 AM: Send personalized onboarding emails to beta users
- [ ] 10 AM: Create support channel (email/Slack/Discord)
- [ ] 12 PM: Monitor installations and usage
- [ ] 2 PM: Address any support issues
- [ ] 4 PM: Collect initial feedback
- [ ] 5 PM: Plan iteration based on feedback

### Weeks 1-2: Beta Period

- [ ] Daily: Monitor support requests
- [ ] Daily: Fix bugs as reported
- [ ] Weekly: Collect user feedback surveys
- [ ] Week 2: Iterate on features based on feedback

### Week 3: Prepare for Public Launch

- [ ] Finalize documentation based on beta learnings
- [ ] Create WordPress.org assets (screenshots, videos)
- [ ] Prepare readme.txt for WordPress.org
- [ ] Submit to WordPress.org
- [ ] Plan marketing campaign

### Week 4-6: WordPress.org Review

- [ ] Wait for approval (1-3 weeks typical)
- [ ] Address any review feedback
- [ ] Prepare launch announcement
- [ ] Create content (blog posts, social media)

### Week 6+: Public Launch

- [ ] WordPress.org approval received
- [ ] Public announcement
- [ ] Marketing push (social, email, communities)
- [ ] Monitor installations and reviews
- [ ] Continue support and iteration

---

## Revenue Projections

### Soft Launch Beta (Month 1)

- Free users: 10-20
- Pro subscribers: 2-3 @ $39/mo = **$78-117 MRR**

### Month 3 (Post WordPress.org)

- Free users: 50-100
- Pro subscribers: 10-15 @ $39/mo = **$390-585 MRR**
- Enterprise: 1 @ $99/mo = **$99 MRR**
- **Total: ~$500 MRR**

### Month 6 (Mature Product)

- Free users: 200-500
- Pro subscribers: 40-50 @ $39/mo = **$1,560-1,950 MRR**
- Enterprise: 3-5 @ $99/mo = **$297-495 MRR**
- **Total: ~$2,000 MRR**

### Year 1 Goal

- Free users: 1,000+
- Pro subscribers: 100+ @ $39/mo = **$3,900 MRR**
- Enterprise: 10+ @ $99/mo = **$990 MRR**
- **Total: ~$5,000 MRR = $60,000 ARR**

---

## Immediate Next Steps (This Week)

### Today (Priority 1)

1. ✅ Review security changes (COMPLETE)
2. ✅ Commit security improvements to git
3. ⏳ **Set up license server on baursoftware.com** (4-6 hours)
4. ⏳ **Test license flow end-to-end** (2 hours)

### This Week (Priority 2)

5. ⏳ Create landing page on baursoftware.com
6. ⏳ Create pricing page
7. ⏳ Write user documentation
8. ⏳ Soft launch to 5-10 beta users

### Next Week (Priority 3)

9. ⏳ Gather beta feedback
10. ⏳ Fix critical bugs
11. ⏳ Prepare WordPress.org submission
12. ⏳ Create marketing content

---

## Support Resources Needed

### Pre-Launch

- [ ] Support email: <support@baursoftware.com> (set up forwarder)
- [ ] Documentation site: baursoftware.com/docs
- [ ] FAQ page
- [ ] Troubleshooting guide

### Post-Launch

- [ ] Help desk software (Help Scout, Zendesk) - ~$50/mo
- [ ] Community forum or Slack/Discord
- [ ] Video tutorials channel (YouTube)

---

## Success Metrics to Track

### Technical KPIs

- OAuth success rate (target: >95%)
- License verification time (target: <2s)
- Plugin activation rate (target: >80%)
- API error rate (target: <1%)

### Business KPIs

- Free tier installations
- Free → Pro conversion rate (target: 15-20%)
- Pro → Enterprise conversion rate (target: 5-10%)
- Monthly churn rate (target: <5%)
- Support ticket volume
- User satisfaction (NPS score)

---

## Risk Mitigation

### Technical Risks

- **License server downtime** → Set up monitoring, backup server
- **API rate limits** → Implement caching, retry logic
- **WordPress compatibility** → Test with popular themes/plugins

### Business Risks

- **Low conversion rate** → Improve value proposition, add features
- **High churn** → Improve onboarding, add customer success
- **Support overwhelm** → Build self-service docs, hire support

---

## Decision Point: Launch Strategy

**Recommendation:** Soft Launch (Option A)

**Reasoning:**

1. ✅ Fastest time to revenue (3 days)
2. ✅ Learn from real users before public launch
3. ✅ Iterate based on feedback
4. ✅ Lower risk of negative reviews
5. ✅ Build case studies and testimonials
6. ✅ Can submit to WordPress.org after validation

**Next Action:** Complete license server setup TODAY and soft launch by end of week.

---

**Last Updated:** 2025-01-24
**Ready to Launch:** After license server setup (4-6 hours remaining)
