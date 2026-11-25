// generator/roadmap_testing_future.js
const phasesData = [
  {
    phase: "Phase 1",
    title: "Core Organization & Member Management",
    timeline: "Weeks 1–4",
    modules: "sys_tenants, org_organizations, org_units, mst_masters, mem_members, mem_addresses",
    deliverables: "Multi-tenant tenant isolation engine, organization profile, hierarchy setup, member registration, multi-address support, photo upload, bulk Excel onboarding.",
    testing: "Tenant data leak verification tests, unique constraint tests, Excel validation unit tests."
  },
  {
    phase: "Phase 2",
    title: "Family (Kutumb) & Membership Lifecycle",
    timeline: "Weeks 5–8",
    modules: "fam_families, fam_family_members, mbr_plans, mbr_applications, mbr_subscriptions, mbr_cards",
    deliverables: "Family code generator, family head assignment, visual family tree renderer, membership tiers, multi-stage approval queue, digital QR ID cards.",
    testing: "Kinship tree cyclic loop prevention, family split/merge integrity, QR signature cryptographic verification."
  },
  {
    phase: "Phase 3",
    title: "Committee, Governance & Meeting Management",
    timeline: "Weeks 9–11",
    modules: "cmt_committees, cmt_committee_members, cmt_meetings, cmt_meeting_agendas, cmt_resolutions",
    deliverables: "Board and committee tenures, designation mapping, meeting notice automation, digital roll call attendance, minutes editor, resolution (Tharav) register.",
    testing: "Quorum validation logic, designation uniqueness checks, minutes immutability lock testing."
  },
  {
    phase: "Phase 4",
    title: "Membership Fees & Donation Management",
    timeline: "Weeks 12–15",
    modules: "fee_schedules, fee_demands, fee_collections, don_donors, don_donations",
    deliverables: "Batch fee invoicing, cashier collection desk, online payment gateway integration, donation campaign drives, 80G tax certificate generation, Form 10BD export.",
    testing: "Payment gateway webhook replay protection, 80G PAN validation rules, late fee calculation precision."
  },
  {
    phase: "Phase 5",
    title: "Institutional Double-Entry Accounting",
    timeline: "Weeks 16–20",
    modules: "acc_financial_years, acc_chart_of_accounts, acc_vouchers, acc_voucher_details, acc_bank_reconciliations",
    deliverables: "Hierarchical Chart of Accounts, Payment/Receipt/Journal/Contra vouchers, mathematical balance enforcement (&Sigma; Dr == &Sigma; Cr), dual-control Maker-Checker approvals, BRS, Trial Balance, P&L, Balance Sheet.",
    testing: "Double-entry balance check stress tests, fiscal year closing rollover tests, BRS variance calculation tests."
  },
  {
    phase: "Phase 6",
    title: "Events Management & Omnichannel Communication",
    timeline: "Weeks 21–24",
    modules: "evt_events, evt_ticket_types, evt_registrations, com_campaigns, com_logs, mst_notification_templates",
    deliverables: "Event publishing, tiered registration, mobile camera QR check-in scanning, food pass redemption, DLT-compliant SMS broadcasts, WhatsApp Business API reminders, daily birthday/anniversary cron.",
    testing: "Gate scanner sub-second latency load tests, duplicate ticket scan rejection, SMS queue throughput testing."
  },
  {
    phase: "Phase 7",
    title: "Social Community Timeline & Engagement",
    timeline: "Weeks 25–27",
    modules: "soc_posts, soc_post_likes, soc_post_comments, soc_polls, soc_poll_votes",
    deliverables: "Interactive social feed, rich media posts, likes/reactions, threaded comments, community polls, condolences/Besna announcements, admin moderation queue.",
    testing: "Profanity filter edge cases, websocket real-time post delivery, image resizing and CDN caching."
  },
  {
    phase: "Phase 8",
    title: "Reports, Analytics & Advanced Niche Modules",
    timeline: "Weeks 28–31",
    modules: "Executive Dashboards, sch_welfare_applications, fac_facilities, fac_bookings, elc_elections, grv_tickets",
    deliverables: "4 Executive Cockpits, Welfare Scholarship & Medical Aid workflows, Community Hall/Wadi reservation calendar, Medical Equipment library, Secret Ballot E-Voting, Helpdesk Ticketing.",
    testing: "Blind ballot secret voter dissociation audit, hall double-booking concurrency locking, export formatting validation."
  },
  {
    phase: "Phase 9",
    title: "Security Hardening, UAT & Production Deployment",
    timeline: "Weeks 32–34",
    modules: "rbac_users, rbac_user_roles, sys_audit_logs, Penetration Testing & CI/CD",
    deliverables: "OWASP vulnerability remediation, end-to-end user acceptance testing (UAT) with trust stakeholders, disaster recovery rehearsal, production Kubernetes deployment.",
    testing: "Penetration testing (VAPT), load testing up to 100,000 concurrent requests, disaster recovery failover under 5 minutes."
  }
];

module.exports = {
  getRoadmapTestingFuture: function() {
    let html = `
    <section id="section-16" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 16</span>
        <h2>16. Project Development Roadmap (Phased Execution)</h2>
      </div>

      <div class="content-card">
        <p>The engineering roadmap spans <strong>34 weeks across 9 structured phases</strong>, guaranteeing an orderly progression from core architecture to specialized community modules.</p>

        <div class="timeline-wrapper">
    `;

    phasesData.forEach((ph, idx) => {
      html += `
        <div class="phase-card mt-5">
          <div class="phase-header">
            <div>
              <span class="phase-tag">${ph.phase}</span>
              <h3 class="phase-title">${ph.title}</h3>
            </div>
            <div class="phase-timeline-badge"><i class="far fa-clock"></i> ${ph.timeline}</div>
          </div>
          
          <div class="phase-body mt-3">
            <p><strong>Primary Modules & Tables:</strong> <code>${ph.modules}</code></p>
            <p><strong>Key Deliverables:</strong> ${ph.deliverables}</p>
            <p><strong>Testing & Verification Scope:</strong> <i class="fas fa-vial text-info"></i> ${ph.testing}</p>
          </div>
        </div>
      `;
    });

    html += `
        </div>
      </div>
    </section>

    <section id="section-17" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 17</span>
        <h2>17. Quality Assurance & Verification Plan</h2>
      </div>

      <div class="content-card">
        <div class="grid-2col">
          <div class="qa-box">
            <h4><i class="fas fa-check-double"></i> Test Methodologies</h4>
            <ul>
              <li><strong>Unit Testing:</strong> >85% code coverage on business logic, fee calculations, and double-entry balance enforcement.</li>
              <li><strong>Multi-Tenancy Isolation Testing:</strong> Automated test suites simulating cross-tenant query injection to ensure zero data leakage.</li>
              <li><strong>Concurrency & Lock Testing:</strong> High-volume concurrent seat booking and event check-in simulations using Locust / JMeter.</li>
              <li><strong>Security Vulnerability Scanning:</strong> Dynamic Application Security Testing (DAST) and SAST code scanning with SonarQube.</li>
            </ul>
          </div>
          <div class="qa-box accent-box">
            <h4><i class="fas fa-clipboard-list"></i> Stakeholder Acceptance Gates</h4>
            <ul>
              <li><strong>Financial Trust Audit:</strong> Statutory auditors verify that Trial Balance, Income & Expenditure, and Balance Sheet match standard Tally outputs.</li>
              <li><strong>80G & Form 10BD Verification:</strong> Validation that tax certificate outputs pass Indian Income Tax electronic filing portal validation schemas.</li>
              <li><strong>Community Usability Testing:</strong> Senior citizen members test the mobile PWA for readability, Gujarati/Hindi language clarity, and simple payment workflows.</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section id="section-18" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 18</span>
        <h2>18. Future Innovations & Platform Enhancements</h2>
      </div>

      <div class="content-card">
        <div class="future-grid">
          <div class="future-card">
            <div class="future-icon"><i class="fas fa-brain"></i></div>
            <h4>AI-Powered Matrimonial Matchmaker</h4>
            <p>Automated compatibility scoring based on education, lifestyle, family preferences, and cultural gotra exclusions with privacy-first contact requests.</p>
          </div>
          <div class="future-card">
            <div class="future-icon"><i class="fab fa-whatsapp"></i></div>
            <h4>Interactive WhatsApp Self-Service Bot</h4>
            <p>Conversational WhatsApp bot allowing elderly members to check dues, download receipts, register for AGMs, and view condolence notices via chat.</p>
          </div>
          <div class="future-card">
            <div class="future-icon"><i class="fas fa-fingerprint"></i></div>
            <h4>Biometric Turnstile & Facial Check-In</h4>
            <p>Edge IoT biometric readers for instant check-in at high-density mandal festivals and youth sports tournaments.</p>
          </div>
          <div class="future-card">
            <div class="future-icon"><i class="fas fa-link"></i></div>
            <h4>Blockchain Credential Verification</h4>
            <p>Cryptographically verifiable digital certificates for life membership, committee tenures, and community service awards on Polygon / Ethereum.</p>
          </div>
        </div>
      </div>
    </section>
    `;

    return html;
  }
};

