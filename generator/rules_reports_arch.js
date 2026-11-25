// generator/rules_reports_arch.js
module.exports = {
  getRulesReportsAndArch: function() {
    return `
    <section id="section-12" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 12</span>
        <h2>12. Core Business Rules & Validations</h2>
      </div>

      <div class="content-card">
        <p>The system enforces strict architectural, domain, and regulatory invariants to preserve data integrity, trust compliance, and legal auditability.</p>

        <div class="rules-grid">
          <div class="rule-box">
            <h4><i class="fas fa-shield-alt"></i> BR-01: Multi-Tenant Data Isolation</h4>
            <p>Every SQL query must include <code>organization_id</code> partition scoping via automated ORM Global Scopes and PostgreSQL Row-Level Security (RLS). No database connection can ever select or update records belonging to another tenant.</p>
          </div>
          <div class="rule-box">
            <h4><i class="fas fa-id-badge"></i> BR-02: Member Number Uniqueness & Scope</h4>
            <p>Membership numbers (e.g. <code>MMP-LIFE-2041</code>) are unique per organization. A membership number cannot be reassigned or reused even if a member is deceased or expelled.</p>
          </div>
          <div class="rule-box">
            <h4><i class="fas fa-users"></i> BR-03: Kutumb Household & Kinship Invariants</h4>
            <p>A family unit must possess exactly one designated Head of Family (Kutumb Pramukh). Biological kinship rules are enforced: an individual member cannot have more than 1 living biological father or mother recorded in the same family tree.</p>
          </div>
          <div class="rule-box">
            <h4><i class="fas fa-balance-scale"></i> BR-04: Double-Entry Mathematical Guard</h4>
            <p>For every accounting voucher entry, the sum of debits must mathematically equal the sum of credits: <code>&Sigma; Debit == &Sigma; Credit</code>. Vouchers cannot be saved out of balance.</p>
          </div>
          <div class="rule-box">
            <h4><i class="fas fa-lock"></i> BR-05: Financial Immutability & Reversals</h4>
            <p>Once a financial voucher is Approved and Posted to the General Ledger, it cannot be silently edited or deleted. Any modification requires posting an explicit, audited Reversal Voucher referencing the original voucher number.</p>
          </div>
          <div class="rule-box">
            <h4><i class="fas fa-file-invoice-dollar"></i> BR-06: Form 10BD & 80G Statutory Integrity</h4>
            <p>Donations claiming 80G tax exemption must mandatorily capture the Donor's valid PAN (Permanent Account Number) and full postal address. The generated 80G certificate serial numbers must be sequential and unalterable.</p>
          </div>
          <div class="rule-box">
            <h4><i class="fas fa-user-check"></i> BR-07: Dual-Control Maker-Checker Separation</h4>
            <p>The user who drafts an accounting voucher, sanctions a welfare grant, or creates a payment requisition (Maker) cannot be the same user who signs off on its approval and release of funds (Checker).</p>
          </div>
          <div class="rule-box">
            <h4><i class="fas fa-vote-yea"></i> BR-08: Cryptographic Secret Ballot Integrity</h4>
            <p>In democratic elections, a voter's identity is cryptographically separated from their cast ballot using blind hashing tokens. The system can verify that an eligible member has voted, but cannot link the voter's identity to their candidate choice.</p>
          </div>
        </div>
      </div>
    </section>

    <section id="section-13" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 13</span>
        <h2>13. Reports, Analytics & Executive Dashboards</h2>
      </div>

      <div class="content-card">
        <p>The platform provides <strong>4 Executive Dashboards</strong> and over <strong>20 Standard Statutory & Operational Reports</strong> with one-click export to Excel, CSV, and formatted PDF.</p>

        <div class="grid-2col mt-4">
          <div class="dash-card">
            <h4><i class="fas fa-tachometer-alt"></i> 13.1 Executive Cockpit Dashboards</h4>
            <ul>
              <li><strong>Trustee Central Cockpit:</strong> Total enrolled members, growth velocity, total corpus vs operational funds, fee collection rate, upcoming AGM schedule.</li>
              <li><strong>Kendra / Branch Dashboard:</strong> Chapter member count, local fee collection tally, pending membership applications, upcoming local events.</li>
              <li><strong>Head Accountant Cockpit:</strong> Daily cash/bank balances, unapproved vouchers queue, uncleared cheques for BRS, monthly expense variance against budget.</li>
              <li><strong>Member Self-Service Dashboard:</strong> Digital ID card, family roster, dues balance card with one-click payment, upcoming event passes, social feed preview.</li>
            </ul>
          </div>
          <div class="dash-card">
            <h4><i class="fas fa-file-alt"></i> 13.2 Standard Operational & Statutory Reports</h4>
            <ul>
              <li><strong>Master Member Directory:</strong> Filterable by blood group, gotra, native village, profession, and age bracket.</li>
              <li><strong>Family Roster (Kutumb Register):</strong> Household listing with family head, member count, and full residential addresses.</li>
              <li><strong>Fee Dues Aging Register:</strong> Defaulter list segmented into 0–30, 31–60, 61–90, and 90+ days overdue.</li>
              <li><strong>Form 10BD Statutory Export:</strong> Official CSV format for Indian Income Tax e-filing of 80G donations.</li>
              <li><strong>General Ledger Statement & Day Book:</strong> Full debit/credit transaction history for any chart of account.</li>
              <li><strong>Trial Balance, P&L & Balance Sheet:</strong> Real-time audited financial statements adhering to Public Trust Act standards.</li>
              <li><strong>Committee Tenure Archive:</strong> Historical roster of past office-bearers and passed resolutions (Tharav).</li>
              <li><strong>Event Attendance & Food Coupon Log:</strong> Checked-in attendees vs total registrations with meal redemption count.</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section id="section-14" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 14</span>
        <h2>14. Technology Stack & Technical Architecture</h2>
      </div>

      <div class="content-card">
        <h3>14.1 Enterprise Technology Selection</h3>
        <p>The system is built on a battle-tested, high-performance technology stack engineered for sub-second query latency, 99.99% uptime, and horizontal scalability.</p>

        <div class="tech-stack-grid">
          <div class="tech-box">
            <span class="tech-layer">Frontend Tier</span>
            <h4>Vue 3 / Nuxt 3 & Vite</h4>
            <p>Single Page Application with Progressive Web App (PWA) capabilities. Uses Tailwind CSS, PrimeVue UI component library, Pinia state management, and KaTeX / Canvas for visual trees.</p>
          </div>
          <div class="tech-box">
            <span class="tech-layer">Backend API Tier</span>
            <h4>Node.js NestJS / PHP Laravel 11</h4>
            <p>Clean Architecture modular monolith with domain-driven design (DDD). Exposes strictly typed RESTful APIs and WebSocket gateways for real-time notifications.</p>
          </div>
          <div class="tech-box">
            <span class="tech-layer">Database Tier</span>
            <h4>PostgreSQL 16 / MySQL 8.0</h4>
            <p>ACID-compliant relational database with Row Level Security (RLS), JSONB storage for dynamic custom fields, full-text search indexes, and read-replica scaling.</p>
          </div>
          <div class="tech-box">
            <span class="tech-layer">Cache & Queues</span>
            <h4>Redis 7 & RabbitMQ</h4>
            <p>In-memory cache for fast session checks and live counters. Asynchronous job queues for WhatsApp/SMS blasts, PDF generation, and daily reminder crons.</p>
          </div>
          <div class="tech-box">
            <span class="tech-layer">Object Storage</span>
            <h4>MinIO / AWS S3</h4>
            <p>Encrypted cloud object repository storing member photo IDs, 80G receipts, signed minutes PDFs, and event promotional banners.</p>
          </div>
          <div class="tech-box">
            <span class="tech-layer">DevOps & Infrastructure</span>
            <h4>Docker, Kubernetes & Nginx</h4>
            <p>Containerized microservices orchestrated via Kubernetes with automated SSL termination, load balancing, and zero-downtime rolling deployments.</p>
          </div>
        </div>

        <h3 class="mt-6">14.2 Technical Architecture Diagram (Mermaid)</h3>
        <div class="mermaid-container">
          <pre class="mermaid">
flowchart TD
    subgraph Clients["Client Tier (Omnichannel)"]
        WEB["Desktop / Tablet Web Portal (Vue 3 / PWA)"]
        MOB["Mobile App (iOS / Android Flutter)"]
        SCAN["Gate Entry QR Scanner Web App"]
    end

    subgraph Gateway["Edge & Gateway Tier"]
        CF["Cloudflare CDN & WAF (DDoS Shield)"]
        NGINX["Nginx Ingress / Reverse Proxy (SSL)"]
    end

    subgraph AppTier["Application Services Tier"]
        AUTH["Auth & RBAC Service (JWT / 2FA)"]
        CORE["Core Business API (NestJS / Laravel)"]
        ASYNC["Background Worker (BullMQ / RabbitMQ)"]
        SOCK["WebSocket Engine (Socket.io)"]
    end

    subgraph DataTier["Data & Persistence Tier"]
        PG["PostgreSQL Primary DB (ACID / RLS)"]
        REDIS["Redis In-Memory Cache & Lock"]
        S3["Object Storage (S3 / MinIO Vault)"]
    end

    subgraph External["Third-Party Integrations"]
        PGW["Payment Gateways (Razorpay / Stripe / UPI)"]
        MSG["SMS & WhatsApp API (ValueFirst / Meta)"]
        SES["Email Delivery (Amazon SES / SendGrid)"]
    end

    Clients --> CF --> NGINX --> AppTier
    AppTier --> PG
    AppTier --> REDIS
    AppTier --> S3
    ASYNC --> External
          </pre>
        </div>
      </div>
    </section>

    <section id="section-15" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 15</span>
        <h2>15. Security Architecture & Data Protection</h2>
      </div>

      <div class="content-card">
        <div class="grid-2col">
          <div class="info-box">
            <h4><i class="fas fa-lock"></i> Encryption & Authentication</h4>
            <ul>
              <li><strong>Data in Transit:</strong> Mandatory TLS 1.3 encryption across all client-to-server and inter-service communications.</li>
              <li><strong>Data at Rest:</strong> AES-256 encryption for database volumes and sensitive document attachments (Aadhaar, PAN).</li>
              <li><strong>Password Hashing:</strong> Salting with Argon2id / Bcrypt (cost factor 12) preventing rainbow table attacks.</li>
              <li><strong>Two-Factor Authentication:</strong> Time-based One-Time Password (TOTP) enforcement for all financial and administrative roles.</li>
            </ul>
          </div>
          <div class="info-box accent-box">
            <h4><i class="fas fa-shield-virus"></i> Compliance & Forensic Defense</h4>
            <ul>
              <li><strong>OWASP Top 10 Defenses:</strong> Automated protection against SQL Injection, Cross-Site Scripting (XSS), and CSRF token verification.</li>
              <li><strong>Tamper-Proof Audit Vault:</strong> Every CRUD transaction writes an append-only JSON snapshot recording User ID, IP, and timestamp.</li>
              <li><strong>DPDP Act & GDPR Compliance:</strong> Right to be Forgotten anonymization pipelines and explicit member consent tracking.</li>
              <li><strong>Automated Backup & Recovery:</strong> Hourly WAL shipping with daily automated full snapshots and automated disaster recovery failover.</li>
            </ul>
          </div>
        </div>
      </div>
    </section>
    `;
  }
};

