// generator/workflows_list.js
const workflowsData = [
  {
    id: "WF-001",
    name: "New Organization Registration & Provisioning",
    actor: "Super Administrator / Organization Onboarding Lead",
    tables: "sys_tenants, org_organizations, acc_financial_years, rbac_users, rbac_user_roles",
    steps: [
      { step: 1, action: "Organization signs SaaS subscription contract. Super Admin enters legal trust name, tenant code, subdomain, and license tier.", role: "Super Admin", validation: "Unique code and subdomain check; plan limits verification." },
      { step: 2, action: "System provisions new tenant partition in database, creates default organization profile, seeds 35+ standard master tables, and generates default Chart of Accounts.", role: "Automated Engine", validation: "Database transaction integrity; seeds verification." },
      { step: 3, action: "System creates initial Primary Trustee / Org Admin user account and sends encrypted invite link via Email & WhatsApp with temporary 2FA token.", role: "Automated Engine", validation: "Password complexity rules (min 12 chars, alphanumeric, symbol)." },
      { step: 4, action: "Org Admin logs in, resets password, binds Google Authenticator TOTP 2FA, and enters Organization Setup Wizard.", role: "Org Admin", validation: "Successful 2FA handshake." }
    ],
    statusTransitions: "TENANT: DRAFT &rarr; PROVISIONING &rarr; ACTIVE"
  },
  {
    id: "WF-002",
    name: "Organization Setup & Hierarchy Initialization",
    actor: "Organization Administrator / General Secretary",
    tables: "org_organizations, org_units, mst_states, mst_districts, mst_cities, org_settings",
    steps: [
      { step: 1, action: "Admin uploads high-resolution vector logo, official digital seal/stamp, and authorized signatory signatures.", role: "Org Admin", validation: "PNG/SVG image size <= 2MB; transparent background." },
      { step: 2, action: "Admin enters Trust Registration No, Societies Act No, PAN, 12A/80G order numbers, and registered office address.", role: "Org Admin", validation: "PAN regex validation; mandatory fields check." },
      { step: 3, action: "Admin establishes administrative hierarchy tree: creates Zonal Vibhags (e.g. South Mumbai, Western Suburbs) and child Kendras/Chapters.", role: "Org Admin", validation: "Circular parent-child reference prevention." },
      { step: 4, action: "Admin configures dynamic custom fields (Gotra, Native Village/Vatan, Flat Number) and activates required module toggles.", role: "Org Admin", validation: "Custom field key immutability check." }
    ],
    statusTransitions: "ORG_PROFILE: INCOMPLETE &rarr; CONFIGURED &rarr; PRODUCTION_READY"
  },
  {
    id: "WF-003",
    name: "New Member Registration (Self-Service / Admin Entry)",
    actor: "Applicant Member or Kendra Clerk",
    tables: "mem_members, mem_addresses, mem_contacts, mem_documents, mbr_applications",
    steps: [
      { step: 1, action: "Applicant opens registration wizard: fills personal demographics (Title, First/Middle/Last Name, Gender, Blood Group, DOB).", role: "Applicant / Clerk", validation: "Mandatory name & DOB; Age >= 18 for primary member." },
      { step: 2, action: "Applicant enters cultural lineage: Native Place (Vatan / Gam), Gotra, Qualification, Occupation, and firm name.", role: "Applicant / Clerk", validation: "Dropdown validation against active master lists." },
      { step: 3, action: "Applicant inputs Residential, Office, and Native addresses; provides primary mobile (verifies via instant WhatsApp OTP) and email.", role: "Applicant / Clerk", validation: "OTP match; PIN code format; mobile uniqueness within tenant." },
      { step: 4, action: "Applicant uploads portrait photograph and identity proofs (Aadhaar/PAN/Passport); enters membership proposer member ID.", role: "Applicant / Clerk", validation: "Proposer must be an active, non-defaulter member." },
      { step: 5, action: "Applicant selects Membership Tier (Life/Annual) and submits application; system generates application tracking reference.", role: "Applicant / Clerk", validation: "Payload cryptographic signature stored." }
    ],
    statusTransitions: "MEMBER: DRAFT &rarr; PENDING_VERIFICATION"
  },
  {
    id: "WF-004",
    name: "Family Unit (Kutumb) Creation & Kinship Linking",
    actor: "Family Head (Kutumb Pramukh) or Kendra Administrator",
    tables: "fam_families, fam_family_members, mem_members, mst_relationships",
    steps: [
      { step: 1, action: "Admin or Member clicks '+ Create Family Unit'; system prompts for Head of Family (Kutumb Pramukh).", role: "Family Head / Admin", validation: "Head must be an enrolled adult member." },
      { step: 2, action: "System creates family record with unique Kutumb code (e.g. 'FAM-MUM-0814') and links ancestral village and family deity.", role: "Automated Engine", validation: "Unique family code generation." },
      { step: 3, action: "User clicks '+ Add Family Relative': selects relationship (Wife, Son, Daughter, Mother, etc.) and searches by member ID or fills new biodata.", role: "Family Head / Admin", validation: "Biological relationship rules: only 1 Father, only 1 Mother, spouse gender consistency." },
      { step: 4, action: "System generates visual genealogical family tree canvas and issues consolidated family ID card with shared family QR code.", role: "Automated Engine", validation: "All family members inherit common family ID card privileges." }
    ],
    statusTransitions: "FAMILY: DRAFT &rarr; ACTIVE"
  },
  {
    id: "WF-005",
    name: "Member Scrutiny & Multi-Tier Approval Workflow",
    actor: "Kendra Secretary & Managing Committee / Trustee Board",
    tables: "mbr_applications, mem_members, rbac_audit_logs",
    steps: [
      { step: 1, action: "Level 1: Kendra Office Clerk verifies uploaded Aadhaar/PAN documents and ensures proposer member endorsement is authentic.", role: "Kendra Clerk", validation: "Document legibility; proposer confirmation." },
      { step: 2, action: "Clerk marks 'Level 1 Scrutiny Cleared' with audit notes; application moves to Local Kendra Secretary queue.", role: "Kendra Clerk", validation: "Mandatory clerk sign-off." },
      { step: 3, action: "Level 2: Kendra Secretary reviews residential jurisdiction and interview notes, then endorses application to Central Committee.", role: "Kendra Secretary", validation: "Jurisdictional boundary confirmation." },
      { step: 4, action: "Level 3: Central Managing Committee / Trustee Board approves enrollment during monthly review meeting.", role: "Managing Trustee", validation: "Resolution reference entry." },
      { step: 5, action: "System activates member record, assigns permanent Membership Number (e.g. 'MMP-LIFE-2041'), issues digital card, and sends WhatsApp welcome kit.", role: "Automated Engine", validation: "Atomic member activation & subscription creation." }
    ],
    statusTransitions: "APPLICATION: SUBMITTED &rarr; SCRUTINIZED &rarr; ENDORSED &rarr; APPROVED &rarr; ACTIVE"
  },
  {
    id: "WF-006",
    name: "Membership Renewal & Expiry Cycle",
    actor: "Automated Scheduler Engine & Member / Cashier",
    tables: "mbr_subscriptions, fee_demands, mem_members, com_logs",
    steps: [
      { step: 1, action: "Automated cron runs daily at 00:00: identifies Annual memberships expiring in T-30, T-15, T-7 days.", role: "Automated Engine", validation: "Subscription tenure_months > 0 and is_lifetime = 0." },
      { step: 2, action: "System creates renewal demand invoice and dispatches personalized WhatsApp message with instant payment link.", role: "Automated Engine", validation: "DLT template check; payment gateway URL tokenization." },
      { step: 3, action: "Member clicks payment link, pays via UPI/Card, or pays cash at Kendra counter.", role: "Member / Cashier", validation: "Payment gateway webhook checksum validation." },
      { step: 4, action: "Upon successful realization, system updates subscription end date (+12 months), increments renewal_count, and resets status to ACTIVE.", role: "Automated Engine", validation: "Atomic ledger balance update." },
      { step: 5, action: "If unpaid past grace period (e.g. 30 days post-expiry), member status shifts to 'LAPSED' and portal privileges are locked.", role: "Automated Engine", validation: "Grace period expiration check." }
    ],
    statusTransitions: "SUBSCRIPTION: ACTIVE &rarr; EXPIRING_SOON &rarr; (RENEWED &rarr; ACTIVE) or (LAPSED &rarr; SUSPENDED)"
  },
  {
    id: "WF-007",
    name: "Membership Fee Demand Generation & Batch Invoicing",
    actor: "Accountant / Head of Billing",
    tables: "fee_schedules, fee_demands, acc_financial_years, mem_members",
    steps: [
      { step: 1, action: "Accountant opens Fee Demand Console: selects active Financial Year, billing cycle (Annual), and applicable membership categories.", role: "Accountant", validation: "Financial Year must be open (not locked)." },
      { step: 2, action: "System displays preview of total eligible members, category breakdowns, waiver exemptions, and projected revenue.", role: "Automated Engine", validation: "Deduplication: ensures no member is billed twice for same period." },
      { step: 3, action: "Accountant clicks 'Execute Batch Demand Run'; system bulk inserts records into `fee_demands` with sequential invoice numbers.", role: "Accountant", validation: "Database bulk transaction with rollback on failure." },
      { step: 4, action: "System dispatches digital demand notices with PDF invoices to all billed members via WhatsApp and registered Email.", role: "Automated Engine", validation: "Queue-backed background broadcast." }
    ],
    statusTransitions: "DEMAND: GENERATED &rarr; DISPATCHED &rarr; UNPAID"
  },
  {
    id: "WF-008",
    name: "Donation Collection, Earmarked Fund Tagging & 80G Certificate",
    actor: "Benefactor / Cashier / Managing Trustee",
    tables: "don_donors, don_donations, mst_donation_categories, acc_vouchers, acc_voucher_details",
    steps: [
      { step: 1, action: "Donor initiates philanthropic contribution online via trust portal or in person at head office.", role: "Donor / Cashier", validation: "Mandatory PAN and full postal address for donations >= Rs. 5,000." },
      { step: 2, action: "Cashier selects donation category: Permanent Corpus, Building Fund, Education Scholarship, Medical Relief, or Memorial.", role: "Cashier", validation: "Corpus donations require explicit donor letter/flag." },
      { step: 3, action: "If dedicated 'In Memory Of', cashier enters deceased honoree name (e.g. 'Late Gopaldasji Somani') for public honour board.", role: "Cashier", validation: "Honoree text validation." },
      { step: 4, action: "Payment realized via Cheque, UPI, or NEFT; system generates unique 80G Tax Exemption Certificate number.", role: "Automated Engine", validation: "Cryptographic hash generation for certificate authenticity." },
      { step: 5, action: "System automatically creates pre-balanced double-entry Receipt Voucher in General Ledger crediting the specific earmarked Trust Fund.", role: "Automated Engine", validation: "Dr Bank Account == Cr Earmarked Donation Fund." }
    ],
    statusTransitions: "DONATION: PENDING &rarr; REALIZED &rarr; 80G_ISSUED &rarr; POSTED_TO_GL"
  },
  {
    id: "WF-009",
    name: "Multi-Channel Automated Receipt Generation & Dispatch",
    actor: "Automated Financial Service Engine",
    tables: "fee_collections, don_donations, org_organizations, com_logs",
    steps: [
      { step: 1, action: "Payment transaction confirmation received (counter collection commit or payment gateway webhook).", role: "Automated Engine", validation: "HMAC webhook signature verification." },
      { step: 2, action: "System compiles receipt metadata: Trust Reg No, 80G Order, Donor PAN, Category, Amount in Words, and Authorized Digital Signature.", role: "Automated Engine", validation: "Amount in words conversion precision." },
      { step: 3, action: "PDF generation service renders official high-resolution receipt containing dynamic QR code verifying authenticity on public validator URL.", role: "Automated Engine", validation: "PDF rendering engine completion." },
      { step: 4, action: "Receipt is saved to secure S3 storage and instantly transmitted to payer's mobile via WhatsApp Business API and Email.", role: "Automated Engine", validation: "Delivery status tracking in communication logs." }
    ],
    statusTransitions: "RECEIPT: GENERATED &rarr; SIGNED &rarr; DISPATCHED"
  },
  {
    id: "WF-010",
    name: "Committee & Board of Trustees Formation",
    actor: "Election Officer / Central General Secretary",
    tables: "cmt_committees, cmt_committee_members, mst_committee_types, mst_committee_designations",
    steps: [
      { step: 1, action: "Following general election or AGM, Secretary navigates to Committee Management: creates new Committee Tenure (e.g. 2024–2027).", role: "General Secretary", validation: "Tenure start date < tenure end date; no overlapping tenures for same committee." },
      { step: 2, action: "Secretary designates constitutional posts: appoints President, Vice President, General Secretary, Treasurer, and Executive Members.", role: "General Secretary", validation: "Appointed members must be active, voting-eligible members; max 1 President/Treasurer." },
      { step: 3, action: "Official appointment orders and resolution references (e.g. 'RES-AGM-2024-04') are attached to each appointed officer.", role: "General Secretary", validation: "Resolution reference check." },
      { step: 4, action: "System elevates appointed members' RBAC role privileges to reflect their executive powers and publishes roster on official portal.", role: "Automated Engine", validation: "Atomic user role sync." }
    ],
    statusTransitions: "COMMITTEE: CONSTITUTED &rarr; ACTIVE"
  },
  {
    id: "WF-011",
    name: "Committee Term Completion & Succession Handover",
    actor: "Outgoing General Secretary & Incoming President",
    tables: "cmt_committees, cmt_committee_members, rbac_user_roles, cmt_meetings",
    steps: [
      { step: 1, action: "System triggers T-60 and T-30 days alerts prior to committee tenure end date to initiate election or nomination proceedings.", role: "Automated Engine", validation: "Tenure date comparison." },
      { step: 2, action: "Final tenure review meeting is conducted; minutes and financial handover balance sheet are officially signed.", role: "Outgoing Secretary", validation: "Full audit sign-off." },
      { step: 3, action: "Admin clicks 'Conclude Tenure'; status of outgoing committee transitions to 'HISTORIC_ARCHIVED'.", role: "Org Admin", validation: "All pending tasks must be marked resolved or reassigned." },
      { step: 4, action: "System revokes administrative signing and approval privileges of outgoing officers and transfers them to incoming executive body.", role: "Automated Engine", validation: "Role privilege de-escalation." }
    ],
    statusTransitions: "COMMITTEE: ACTIVE &rarr; CONCLUDED &rarr; ARCHIVED"
  },
  {
    id: "WF-012",
    name: "Committee Meeting, Agenda, Attendance & Minutes / Resolution",
    actor: "Secretary, President & Committee Members",
    tables: "cmt_meetings, cmt_meeting_agendas, cmt_meeting_attendances, cmt_resolutions",
    steps: [
      { step: 1, action: "Secretary drafts Meeting Notice: sets date, time, venue / video link, and attaches numbered agenda items with background papers.", role: "Secretary", validation: "Notice period requirement (e.g. min 7 days notice for managing committee)." },
      { step: 2, action: "Notice dispatched automatically via WhatsApp and Email; members RSVP attendance in member portal.", role: "Automated Engine", validation: "Delivery tracking." },
      { step: 3, action: "On meeting day, Secretary takes digital roll call: marks members Present, Absent, or Leave of Absence; system verifies Quorum.", role: "Secretary", validation: "Quorum validation: present count >= quorum_required_count." },
      { step: 4, action: "For each agenda item, Secretary records deliberation notes; formal resolutions (Tharav) are proposed, seconded, and voted upon.", role: "Secretary & Members", validation: "Proposer and seconder must be present attendees." },
      { step: 5, action: "President reviews, signs digitally, and minutes are locked into the official digital minutes vault; action items are delegated.", role: "President", validation: "Immutability lock: locked minutes cannot be altered." }
    ],
    statusTransitions: "MEETING: SCHEDULED &rarr; NOTICE_SENT &rarr; IN_PROGRESS &rarr; MINUTES_RECORDED &rarr; SIGNED_LOCKED"
  },
  {
    id: "WF-013",
    name: "Social Timeline Post Creation, Moderation & Flagging",
    actor: "Member, Moderator & General Secretary",
    tables: "soc_posts, soc_post_likes, soc_post_comments, rbac_audit_logs",
    steps: [
      { step: 1, action: "Member creates post: writes text, attaches photos/video link, selects category (General, Condolence, Congrats, Notice), and privacy.", role: "Member", validation: "Automated profanity and spam regex scan; image size <= 10MB." },
      { step: 2, action: "If tenant is set to 'Pre-moderation', post enters Moderator Queue; if 'Post-moderation', post appears immediately on feed.", role: "Moderator / System", validation: "Content policy validation." },
      { step: 3, action: "Members engage: like, react (Celebrate, Care), comment, and share; author receives instant push notifications.", role: "Members", validation: "Comment text profanity check." },
      { step: 4, action: "If post receives 3+ community flags, system automatically hides post from feed and alerts General Secretary for review.", role: "Automated Engine", validation: "Threshold flag count check." },
      { step: 5, action: "Moderator investigates flagged post: can approve, permanently delete, or issue disciplinary warning / mute to author.", role: "Moderator", validation: "Moderator action audit logging." }
    ],
    statusTransitions: "POST: DRAFT &rarr; PENDING_MODERATION &rarr; PUBLISHED &rarr; (FLAGGED_HIDDEN &rarr; REMOVED/RESTORED)"
  },
  {
    id: "WF-014",
    name: "Event Publishing, Online Registration & Gate QR Check-In",
    actor: "Event Secretary, Members & Gate Volunteers",
    tables: "evt_events, evt_ticket_types, evt_registrations, evt_attendees",
    steps: [
      { step: 1, action: "Event Secretary creates event (e.g. 'Diwali Sneh Milan 2026'): sets venue, dates, capacity, banner, and food coupon toggle.", role: "Event Secretary", validation: "Event start date must be in future." },
      { step: 2, action: "Secretary configures ticket tiers: Member Free Pass, Family Pass, and Non-Member Guest Pass.", role: "Event Secretary", validation: "Sum of tier quotas <= total venue capacity." },
      { step: 3, action: "Member registers online: selects passes for self and family members; system issues pass with unique anti-counterfeit QR code.", role: "Member", validation: "Duplicate booking check: prevents hoarding." },
      { step: 4, action: "At event entrance, gate volunteers scan attendee's QR code using mobile browser scanner.", role: "Gate Volunteer", validation: "Real-time verification: pass marked 'Checked-In'; duplicate scan displays audible alarm." },
      { step: 5, action: "Secondary scan at dinner/bhojan counter validates and redeems catering meal coupons.", role: "Catering Staff", validation: "Food coupon single-use redemption lock." }
    ],
    statusTransitions: "REGISTRATION: CONFIRMED &rarr; ISSUED &rarr; CHECKED_IN &rarr; MEAL_REDEEMED"
  },
  {
    id: "WF-015",
    name: "Expense Claim, Voucher Approval & Bank Disbursement",
    actor: "Staff / Vendor, Accountant, Treasurer & Managing Trustee",
    tables: "acc_vouchers, acc_voucher_details, acc_chart_of_accounts",
    steps: [
      { step: 1, action: "Office staff or committee member uploads vendor bill scan and submits Expense Payment Request.", role: "Staff", validation: "Mandatory invoice image attachment and expense category." },
      { step: 2, action: "Accountant verifies bill against purchase order, selects debit Expense Account and credit Bank Account, preparing Payment Voucher.", role: "Accountant", validation: "Debit amount must equal credit amount." },
      { step: 3, action: "Treasurer reviews voucher in approval queue: checks budget availability and signs off on verification.", role: "Treasurer", validation: "Budget check: expense within sanctioned annual budget limit." },
      { step: 4, action: "Managing Trustee grants final digital authorization for release of funds.", role: "Managing Trustee", validation: "Dual-control compliance: Maker != Checker." },
      { step: 5, action: "Accountant executes NEFT batch transfer from trust bank account, inputs bank UTR reference, and posts voucher to General Ledger.", role: "Accountant", validation: "Voucher locked against further edits." }
    ],
    statusTransitions: "EXPENSE: SUBMITTED &rarr; VERIFIED &rarr; SANCTIONED &rarr; DISBURSED &rarr; POSTED_GL"
  },
  {
    id: "WF-016",
    name: "Double-Entry Accounting Voucher Posting & FY Year-End Closing",
    actor: "Head Accountant & Statutory Auditor",
    tables: "acc_vouchers, acc_voucher_details, acc_financial_years, acc_chart_of_accounts",
    steps: [
      { step: 1, action: "Throughout fiscal year, all financial events (dues, donations, expenses, bank charges) generate balanced double-entry vouchers.", role: "Accountant / System", validation: "&Sigma; Debits == &Sigma; Credits strictly enforced on every commit." },
      { step: 2, action: "Monthly Bank Reconciliation (BRS) is performed: bank statement uploaded, cleared cheques reconciled, variances cleared.", role: "Accountant", validation: "Book bank balance matches statement adjusted balance." },
      { step: 3, action: "At fiscal year end (March 31), Auditor generates Trial Balance, Income & Expenditure Statement, and Balance Sheet.", role: "Auditor", validation: "Trial balance net variance == 0.00." },
      { step: 4, action: "Auditor posts closing journal vouchers transferring operational surplus/deficit to General Reserve / Capital Corpus.", role: "Auditor", validation: "Nominal accounts (Incomes/Expenses) netted to zero." },
      { step: 5, action: "Admin clicks 'Close & Lock Financial Year'; opening balances are automatically rolled into the next fiscal calendar year.", role: "Org Admin", validation: "All vouchers in closed year become strictly read-only." }
    ],
    statusTransitions: "FINANCIAL_YEAR: ACTIVE &rarr; AUDITED &rarr; CLOSED & LOCKED"
  },
  {
    id: "WF-017",
    name: "User Provisioning & Granular Role Assignment",
    actor: "Organization Administrator",
    tables: "rbac_users, rbac_roles, rbac_permissions, rbac_user_roles",
    steps: [
      { step: 1, action: "Admin navigates to User Management: clicks '+ Add System User' and enters username, mobile, and official email.", role: "Org Admin", validation: "Username and email uniqueness check within tenant." },
      { step: 2, action: "Admin selects role archetype: Trustee, General Secretary, Treasurer, Kendra Admin, Accountant, or Staff.", role: "Org Admin", validation: "Role must be active." },
      { step: 3, action: "Admin assigns operational jurisdiction scope: Organization-wide (Central) or restricted to specific Zonal Vibhag / Kendra.", role: "Org Admin", validation: "Jurisdictional scoping constraint." },
      { step: 4, action: "System sends secure credential setup link to user; user signs in and configures mandatory Two-Factor Authentication (2FA).", role: "New User", validation: "TOTP authenticator setup completion." }
    ],
    statusTransitions: "USER: INVITED &rarr; ACTIVATED &rarr; 2FA_VERIFIED"
  },
  {
    id: "WF-018",
    name: "Automated Omnichannel Reminders & Milestone Engine",
    actor: "Automated Cron Daemon Engine",
    tables: "mst_notification_templates, com_campaigns, com_logs, mem_members",
    steps: [
      { step: 1, action: "Daily cron daemon wakes at 07:30 AM: scans `mem_members` for today's Birthdays and Wedding Anniversaries.", role: "Automated Engine", validation: "Active members only; death_date is NULL." },
      { step: 2, action: "System compiles personalized greeting cards with member photo and transmits via WhatsApp Business API.", role: "Automated Engine", validation: "Valid WhatsApp number with country code." },
      { step: 3, action: "System publishes automated celebration banner on Community Social Feed allowing peers to click 'Send Wishes'.", role: "Automated Engine", validation: "Post creation in social feed." },
      { step: 4, action: "At 10:00 AM, payment reminder cron runs: identifies overdue fee demands and dispatches reminders with direct payment links.", role: "Automated Engine", validation: "Overdue balance > 0; reminder frequency throttle (max 1 per 7 days)." },
      { step: 5, action: "All outgoing transmissions recorded in `com_logs` with gateway message ID and real-time delivery callbacks.", role: "Automated Engine", validation: "Delivery status update." }
    ],
    statusTransitions: "ALERT: QUEUED &rarr; DISPATCHED &rarr; DELIVERED &rarr; READ"
  }
];

module.exports = {
  getWorkflowsList: function() {
    let html = `
    <section id="section-9" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 09</span>
        <h2>9. Complete System Workflows & Business Logic</h2>
      </div>

      <div class="content-card">
        <p>The platform executes <strong>18 mission-critical operational workflows</strong> governing tenant onboarding, member lifecycle, governance, dual-control accounting, and community engagement. Each workflow details actors, step-by-step validations, approvals, status transitions, and impacted tables.</p>
        
        <div class="workflows-container">
    `;

    workflowsData.forEach((wf, idx) => {
      html += `
        <div class="workflow-card mt-6" id="wf-${wf.id}">
          <div class="workflow-header">
            <div>
              <span class="wf-badge">${wf.id}</span>
              <h3 class="wf-title">${wf.name}</h3>
            </div>
            <div class="wf-status-pill"><code>${wf.statusTransitions}</code></div>
          </div>

          <div class="wf-meta-grid mt-3">
            <div><strong>Primary Actor:</strong> ${wf.actor}</div>
            <div><strong>Database Tables:</strong> <code>${wf.tables}</code></div>
          </div>

          <div class="table-responsive mt-3">
            <table class="data-table wf-steps-table">
              <thead>
                <tr>
                  <th style="width: 70px;">Step</th>
                  <th>Action & Process Description</th>
                  <th style="width: 180px;">Responsible Role</th>
                  <th>Validations, Rules & Approvals</th>
                </tr>
              </thead>
              <tbody>
      `;

      wf.steps.forEach(s => {
        html += `
                <tr>
                  <td><span class="step-num">${s.step}</span></td>
                  <td>${s.action}</td>
                  <td><strong>${s.role}</strong></td>
                  <td><i class="fas fa-check-circle text-success"></i> ${s.validation}</td>
                </tr>
        `;
      });

      html += `
              </tbody>
            </table>
          </div>
        </div>
      `;
    });

    html += `
        </div>
      </div>
    </section>
    `;

    return html;
  }
};

