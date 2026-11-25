module.exports = {
  getOverviewAndScope: function() {
    return `
    <section id="section-1" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 01</span>
        <h2>1. Project Overview</h2>
      </div>
      
      <div class="content-card">
        <h3>1.1 Executive Summary</h3>
        <p>The <strong>Universal Membership & Community Management System (UMCMS)</strong> is an enterprise-grade, multi-tenant SaaS application engineered to streamline, digitize, and unify the operations of any community organization, non-profit, mandal, trust, professional association, club, or residential society.</p>
        <p>Historically, organizations like <em>Maheshwari Mandal Mumbai (MMP)</em> and <em>Shri Navnat Stree Mandal (SNS)</em>, along with thousands of housing societies, trusts, and alumni networks, have struggled with fragmented tools: paper registers, isolated spreadsheets, disconnected WhatsApp groups, independent accounting desktop software (like Tally), and non-interactive static websites. UMCMS consolidates these functions into a unified, secure, real-time cloud platform.</p>
        
        <div class="grid-2col mt-4">
          <div class="info-box">
            <h4><i class="fas fa-bullseye"></i> The Core Problem</h4>
            <ul>
              <li><strong>Data Fragmentation:</strong> Member records scattered in Excel, ledger books, and WhatsApp lists.</li>
              <li><strong>Lack of Real-time Financial Integrity:</strong> Fee collection receipts detached from formal double-entry accounting.</li>
              <li><strong>Kinship Disconnect:</strong> Traditional systems record single members without genealogical family tree or household mapping.</li>
              <li><strong>Governance Gaps:</strong> Difficulty in tracking committee tenures, resolutions, meeting minutes, and election history.</li>
              <li><strong>Weak Engagement:</strong> Members receive passive circulars instead of an interactive social community timeline.</li>
            </ul>
          </div>
          <div class="info-box accent-box">
            <h4><i class="fas fa-check-circle"></i> The UMCMS Solution</h4>
            <ul>
              <li><strong>Zero-Code Configurability:</strong> Single codebase that adapts to Mandals, Charitable Trusts, Housing Societies, Clubs, and Educational Bodies.</li>
              <li><strong>Multi-Tenancy with Strict Isolation:</strong> Cryptographically scoped database partitioning guaranteeing zero data leakage.</li>
              <li><strong>Complete Family Unit Architecture:</strong> Kutumb (family) head, hierarchical kinship trees, and dependent tracking.</li>
              <li><strong>Integrated Double-Entry Accounting:</strong> Compliant with Bombay Public Trusts Act & Societies Registration Act standards.</li>
              <li><strong>Interactive Social Community:</strong> Moderated member-only social feed, polls, notices, event tickets, and photo galleries.</li>
            </ul>
          </div>
        </div>

        <h3 class="mt-6">1.2 Supported Organization Typologies</h3>
        <p>The architecture provides pre-configured domain templates for various organizational structures:</p>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Organization Type</th>
                <th>Typical Real-World Examples</th>
                <th>Key Tailored Configurations</th>
                <th>Enabled Core Modules</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Social Mandals & Samajes</strong></td>
                <td>Maheshwari Mandal Mumbai (MMP), Navnat Mandal (SNS), Jain Social Groups, Kshatriya Mandals</td>
                <td>Family Trees (Kutumb), Gotra, Native Place (Vatan/Gam), Matrimonial Directory, Death Condolences (Besna)</td>
                <td>Family, Member, Social Feed, Halls/Bhavan, Welfare Relief, Events</td>
              </tr>
              <tr>
                <td><strong>Charitable Trusts & NGOs</strong></td>
                <td>Educational Aid Trusts, Healthcare Foundations, Gaushalas, Relief Funds</td>
                <td>80G Tax Exemption Receipts, Donor Campaigns, Corpus vs General Funds, Beneficiary Schemes</td>
                <td>Donations, Double-Entry Accounting, Welfare/Scholarships, Document Vault</td>
              </tr>
              <tr>
                <td><strong>Clubs & Gymkhanas</strong></td>
                <td>Rotary Clubs, Lions Clubs, Golf & Sports Gymkhanas, Cultural Clubs</td>
                <td>Monthly/Quarterly Dues, F&B Subsidies, Facility Bookings (Tennis, Pool, Banquet), Committee Terms</td>
                <td>Membership Plans, Facilities, Fees, Committees, Events</td>
              </tr>
              <tr>
                <td><strong>Professional Associations</strong></td>
                <td>Bar Associations, Medical Associations (IMA), CA Chapters (ICAI study circles), IT Forums</td>
                <td>CPE Credits, Membership ID Cards with Barcode/QR, Digital Certificates, Election Ballots</td>
                <td>Member Directory, QR Cards, Elections, Meetings, Invoicing</td>
              </tr>
              <tr>
                <td><strong>Housing Societies (CHSL)</strong></td>
                <td>Co-operative Housing Societies, Resident Welfare Associations (RWA), Villa Communities</td>
                <td>Flat/Unit Master, Maintenance Billing, Visitor Logs, Parking Slips, AMC Vendor Registers</td>
                <td>Unit Management, Accounting, Grievance/Helpdesk, Notice Board</td>
              </tr>
              <tr>
                <td><strong>Religious Bodies & Temples</strong></td>
                <td>Temple Devasthanam, Churches, Gurudwara Prabandhak Committees, Wakf Trusts</td>
                <td>Pooja/Seva Booking, Hundi/Donation Tracking, Darshan Passes, Annakshetra Inventory</td>
                <td>Donations, Facilities, Accounting, Volunteers, Festivals</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section id="section-2" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 02</span>
        <h2>2. Product Vision & Scope</h2>
      </div>

      <div class="content-card">
        <h3>2.1 Product Vision Statement</h3>
        <p class="lead-quote">"To create the world's most adaptable, transparent, and comprehensive community management operating system—enabling organizations to preserve heritage, automate compliance, strengthen kinship, and empower democratic governance on a single unified platform."</p>
        
        <h3>2.2 Multi-Tier Hierarchical Governance Model</h3>
        <p>The platform supports unlimited jurisdictional depth, catering to international federations down to neighborhood wards:</p>
        
        <div class="hierarchy-tree-visual">
          <div class="tree-node level-1">
            <strong>Level 1: Apex Federation / National Central Body</strong>
            <span>(e.g., Akhil Bharatiya Mandal / All India Trust Federation)</span>
          </div>
          <div class="tree-branch">
            <div class="tree-node level-2">
              <strong>Level 2: State / Provincial Directorate</strong>
              <span>(e.g., Maharashtra State Mandal / Gujarat Chapter)</span>
            </div>
            <div class="tree-subbranch">
              <div class="tree-node level-3">
                <strong>Level 3: Zonal / Regional Board (Vibhag)</strong>
                <span>(e.g., Mumbai Metropolitan Region, Western Suburbs Zone)</span>
              </div>
              <div class="tree-leaf-group">
                <div class="tree-node level-4">
                  <strong>Level 4: City / District Kendra</strong>
                  <span>(e.g., South Mumbai Kendra, Andheri Local Chapter)</span>
                </div>
                <div class="tree-node level-5">
                  <strong>Level 5: Local Ward / Mohalla / Unit</strong>
                  <span>(e.g., Girgaon Unit, Bhuleshwar Local Samiti)</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <h3 class="mt-6">2.3 Multi-Tenant SaaS Architecture Pattern</h3>
        <div class="architecture-pills">
          <div class="arch-card">
            <div class="arch-icon"><i class="fas fa-layer-group"></i></div>
            <h4>Shared Schema with Strict Tenant Isolation</h4>
            <p>Every query is scoped by <code>organization_id</code> using PostgreSQL Row Level Security (RLS) and ORM Global Scopes. No database connection can ever select, insert, or update data across organizations.</p>
          </div>
          <div class="arch-card">
            <div class="arch-icon"><i class="fas fa-sliders-h"></i></div>
            <h4>Dynamic Metadata & EAV Custom Fields</h4>
            <p>Organizations can configure custom fields (e.g. Gotra, Native Village, Sub-caste, Flat Number, Bar Registration No) without modifying schema tables or restarting application containers.</p>
          </div>
          <div class="arch-card">
            <div class="arch-icon"><i class="fas fa-paint-brush"></i></div>
            <h4>White-Label & Subdomain Routing</h4>
            <p>Custom domains (e.g. <code>portal.mmpmumbai.org</code>) or subdomains (<code>mmp.umcms.com</code>) with dedicated branding, logos, color palettes, and header styling.</p>
          </div>
        </div>
      </div>
    </section>

    <section id="section-3" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 03</span>
        <h2>3. Complete Module Overview</h2>
      </div>

      <div class="content-card">
        <p>The system is divided into <strong>18 fully-integrated modules</strong>, covering operational, social, financial, and governance needs:</p>
        
        <div class="module-grid">
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-sitemap"></i></div>
            <h4>A. Organization Management</h4>
            <p>Multi-tier hierarchy, branches/vibhags, branding, fiscal year settings, and metadata engine.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-users"></i></div>
            <h4>B. Member Management</h4>
            <p>Complete member profiles, native place, gotra, blood group, documents, import/export, and audit logs.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-house-user"></i></div>
            <h4>C. Family Management</h4>
            <p>Family codes (Kutumb), family head assignment, relationship mapping, and visual genealogical tree.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-id-card"></i></div>
            <h4>D. Membership Lifecycle</h4>
            <p>Plans (Annual/Life/Patron), online application, approval workflow, renewal, and QR digital ID cards.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-comments"></i></div>
            <h4>E. Social Community Feed</h4>
            <p>Interactive timeline, posts, likes, comments, condolences, congratulations, polls, and admin moderation.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-user-tie"></i></div>
            <h4>F. Committee & Governance</h4>
            <p>Trustees, managing committees, tenures, meetings, agendas, minutes, resolutions, and task delegation.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <h4>G. Membership Fees</h4>
            <p>Fee schedules, demand invoicing, online payment gateways, offline cash/cheque collection, and receipts.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-hand-holding-heart"></i></div>
            <h4>H. Donations & 80G Tax</h4>
            <p>Campaigns, donor CRM, 80G receipts, in-memory memorial donations, and fund utilization tracking.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-calculator"></i></div>
            <h4>I. Double-Entry Accounting</h4>
            <p>Chart of Accounts, Vouchers (Payment, Receipt, Journal, Contra), General Ledger, Trial Balance, P&L, Balance Sheet.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-calendar-alt"></i></div>
            <h4>J. Events & Ticketing</h4>
            <p>Festival/AGM management, multi-tier ticketing, QR check-in scanning, guest registration, and feedback.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-envelope-open-text"></i></div>
            <h4>K. Communication & Alerts</h4>
            <p>SMS, WhatsApp Business API, Email blasts, push notifications, birthday/anniversary automation, and reminders.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-shield-alt"></i></div>
            <h4>L. RBAC & Access Control</h4>
            <p>Multi-role authorization, granular module permissions, unit-scoped data boundaries, and audit logging.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-folder-open"></i></div>
            <h4>M. Document Repository</h4>
            <p>Digital vault for trust deeds, audit reports, circulars, resolutions, ID proofs, and expiry alerts.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-chart-pie"></i></div>
            <h4>N. Reports & Analytics</h4>
            <p>Executive dashboards, demographic heatmaps, collection reports, ledger statements, and Excel/PDF exports.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-graduation-cap"></i></div>
            <h4>O. Welfare & Relief Schemes</h4>
            <p>Higher education scholarships, medical assistance funds, widow support, and multi-stage sanction workflows.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-building"></i></div>
            <h4>P. Halls, Wadi & Assets</h4>
            <p>Community hall/wadi bookings, time slot pricing, security deposits, and medical equipment lending library.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-vote-yea"></i></div>
            <h4>Q. Democratic Elections</h4>
            <p>Election notifications, nomination filing, scrutiny, secret ballot e-voting, and instant tally generation.</p>
          </div>
          <div class="module-item">
            <div class="module-icon"><i class="fas fa-headset"></i></div>
            <h4>R. Grievance Redressal</h4>
            <p>Member helpdesk tickets, categorised routing, SLA tracking, resolution notes, and feedback loops.</p>
          </div>
        </div>
      </div>
    </section>
    `;
  }
};

