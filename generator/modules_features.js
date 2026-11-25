module.exports = {
  getModulesFeatures: function() {
    return `
    <section id="section-4" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 04</span>
        <h2>4. Module-wise Features & Subfeatures</h2>
      </div>

      <!-- Module A -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">A</span>
          <div>
            <h3>Organization Management Module</h3>
            <p class="subtitle">Multi-tier organizational entity configuration, hierarchy management, and tenant branding engine.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-id-card-alt"></i> 1. Organization Profile & Registration</h4>
            <ul>
              <li><strong>Legal Identity:</strong> Trust Registration Number, Societies Act Registration, 12A/80G details, FCRA registration, PAN, GSTIN, TAN.</li>
              <li><strong>Contact & Head Office:</strong> Registered office address, headquarters phone numbers, official support email, website URL, GPS coordinates.</li>
              <li><strong>Branding Engine:</strong> High-resolution vector logo, favicon, official letterhead template, authorized signature stamps, brand primary & secondary hex codes.</li>
              <li><strong>Configurable Organization Typology:</strong> Social Mandal, Charitable Trust, Residential Society, Sports Gymkhana, Religious Trust, Alumni Network.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-network-wired"></i> 2. Hierarchy & Branch/Unit Management</h4>
            <ul>
              <li><strong>N-Tier Jurisdiction Engine:</strong> Parent-child recursive hierarchy representing Apex Board &rarr; State Directorate &rarr; Zonal Vibhag &rarr; City Kendra &rarr; Local Ward/Unit.</li>
              <li><strong>Autonomous Operational Scopes:</strong> Each branch/unit has independent committee chairs, treasurers, bank accounts, and member registries while rolling up to central consolidated financials.</li>
              <li><strong>Geographic Boundary Mapping:</strong> Mapping units to Countries, States, Districts, Talukas, Pin Codes, and Municipal Wards.</li>
              <li><strong>Unit Status Lifecycle:</strong> Active, Suspended, Dissolved, Re-merged with audit trail of jurisdiction transfers.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-cog"></i> 3. Settings & Financial Year Engine</h4>
            <ul>
              <li><strong>Financial Year Definition:</strong> Multi-year fiscal calendar support (e.g., April 1 to March 31 or January 1 to December 31) with automated ledger rollover and locking.</li>
              <li><strong>Dynamic Custom Field Builder:</strong> Define organizational custom fields on entities (e.g. Gotra, Native Village / Vatan, Samaj Shreni, Flat / Wing Number) with data types (Text, Number, Date, Dropdown, Multi-select, Document upload).</li>
              <li><strong>System Currency & Localization:</strong> Default currency (INR, USD, EUR, etc.), date formats (DD/MM/YYYY), localized terminology overrides (e.g., changing "Member" to "Sabhyapad", "Family" to "Kutumb").</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module B -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">B</span>
          <div>
            <h3>Member Management Module</h3>
            <p class="subtitle">Comprehensive member registry, biodata lifecycle, Indian community cultural attributes, and multi-address mapping.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-user-plus"></i> 1. Member Profile & Biodata</h4>
            <ul>
              <li><strong>Unique Member Identifier:</strong> Automated customizable prefix numbering (e.g., <code>MMP-MUM-2026-00421</code>) with barcode and QR encoding.</li>
              <li><strong>Personal Demographics:</strong> Title, First Name, Middle/Father/Husband Name, Last Name (Surname), Maiden Name, Gender, Blood Group, Date of Birth with automated age calculation.</li>
              <li><strong>Cultural & Native Identity:</strong> Native Place (Vatan / Gam / Gaon), State of Origin, Gotra / Shakha, Sub-caste / Kuldevta, Marital Status, Marriage Anniversary Date.</li>
              <li><strong>Professional & Educational Profile:</strong> Highest Degree, Institution, Profession/Occupation (Business, CA, Doctor, Engineer, Retired, Homemaker), Industry, Company Name, Designation.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-map-marked-alt"></i> 2. Multi-Address & Contact Matrix</h4>
            <ul>
              <li><strong>Multiple Address Types:</strong> Residential (Current), Permanent, Office / Business, Native Village (Vatan) address with GPS mapping and Google Maps integration.</li>
              <li><strong>Communication Channels:</strong> Primary mobile number (OTP verified), alternate phone, WhatsApp number, official email, emergency contact person and emergency phone.</li>
              <li><strong>Document Vault:</strong> Upload of Identity Proofs (Aadhaar, Passport, PAN, Voter ID), Passport-size photograph, signature specimen, and verification badge status.</li>
              <li><strong>Member Status Workflow:</strong> Active, Inactive, Expired, Suspended (disciplinary), Deceased (with death date, condolences / Besna announcement trigger).</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-file-import"></i> 3. Bulk Import, Export & Search Engine</h4>
            <ul>
              <li><strong>Smart Excel/CSV Onboarding:</strong> Multi-column drag-and-drop importer with validation preview, error highlighting, and deduplication detection (by mobile/email/Aadhaar).</li>
              <li><strong>Granular Multi-Parametric Filter:</strong> Search by Blood Group, Gotra, Native Village, Age Bracket, Profession, Zone/Kendra, Committee tenure, or Payment Status.</li>
              <li><strong>Secure Export:</strong> Role-gated export to Excel, CSV, or formatted PDF member directory with watermark protection and export audit logging.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module C -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">C</span>
          <div>
            <h3>Family Management Module (Kutumb Ecosystem)</h3>
            <p class="subtitle">Generational kinship mapping, household management, family trees, and composite household directories.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-house-chimney"></i> 1. Family Unit & Household Head</h4>
            <ul>
              <li><strong>Unique Family Code (Kutumb ID):</strong> Automated family grouping code (e.g., <code>FAM-2026-0814</code>) grouping all co-habiting or lineage members.</li>
              <li><strong>Family Head (Kutumb Pramukh):</strong> Explicit designated Head of Family who receives composite communications, primary invoices, and holds voting primacy.</li>
              <li><strong>Household Lineage Demographics:</strong> Ancestral town, family deity (Kuldevta), ancestral firm/business name, family pride notes.</li>
              <li><strong>Family Split & Merge Operations:</strong> Support for adult children marrying and creating independent family units, or merging elderly parents into son/daughter households with historic link retention.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-sitemap"></i> 2. Kinship Hierarchy & Visual Family Tree</h4>
            <ul>
              <li><strong>Strict Relationship Mapping:</strong> Explicit relationship tags relative to Family Head (Self, Wife, Husband, Father, Mother, Son, Daughter, Brother, Sister, Daughter-in-Law, Grandson, Granddaughter).</li>
              <li><strong>Interactive Visual Tree:</strong> Canvas-based interactive generational pedigree chart displaying avatars, age, marital status, and membership tiers.</li>
              <li><strong>Dependent & Minor Tracking:</strong> Flags for minors, students, senior citizens, and differently-abled dependents for targeted welfare or educational subsidies.</li>
              <li><strong>Family Directory & Composite Cards:</strong> Consolidated family ID card printing with all members' details and collective family QR code.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module D -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">D</span>
          <div>
            <h3>Membership Lifecycle & Plans</h3>
            <p class="subtitle">Configurable subscription tiers, digital onboarding approval funnels, automated renewals, and cryptographic QR ID cards.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-layer-group"></i> 1. Plans, Tiers & Eligibility Rules</h4>
            <ul>
              <li><strong>Configurable Membership Tiers:</strong> Patron (Sanrakshak), Donor Member (Data Sabhya), Life Member (Ajeevan), Ordinary / Annual Member, Associate / Youth Member, Honorary Dignitary.</li>
              <li><strong>Financial Rules:</strong> One-time admission fee, recurring periodic fee (Annual/Biannual/Monthly), security deposit, and lifetime lump sum.</li>
              <li><strong>Grace Period & Expiry Handling:</strong> Automated grace period before moving to "Lapsed" or "Inactive" status, automated late fee surcharge computation.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-clipboard-check"></i> 2. Application & Multi-Stage Approval Funnel</h4>
            <ul>
              <li><strong>Public Digital Application Portal:</strong> Online application with document upload, self-declaration, and proposer/seconder member endorsement.</li>
              <li><strong>Multi-Level Scrutiny:</strong> Level 1 (Office Clerk Document Verification) &rarr; Level 2 (Local Kendra / Unit Secretary Scrutiny) &rarr; Level 3 (Managing Committee / Trustee Final Approval).</li>
              <li><strong>Proposer / Seconder Validation:</strong> Built-in cross-check ensuring proposer and seconder are active, non-defaulter members of the organization.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-qrcode"></i> 3. Digital QR Membership Cards & Transfers</h4>
            <ul>
              <li><strong>Smart Digital Card:</strong> Apple Wallet and Google Wallet compatible pass generation with encrypted dynamic QR code for fraud prevention.</li>
              <li><strong>Physical Card Print Management:</strong> Batch generation of CMYK high-resolution print-ready PVC cards with NFC/Barcode.</li>
              <li><strong>Inter-Unit Transfers & Succession:</strong> Seamless transfer of member registry upon relocation, or legal succession of life membership to legal heir upon demise.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module E -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">E</span>
          <div>
            <h3>Social Community Feed & Member Timeline</h3>
            <p class="subtitle">Private, secure Facebook/LinkedIn-style community stream for members, youth wings, announcements, and polls.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-stream"></i> 1. Community Post Creation & Media Sharing</h4>
            <ul>
              <li><strong>Rich Media Posts:</strong> Text with markdown, high-definition photo galleries (multi-image carousel), embedded video links, and PDF documents.</li>
              <li><strong>Categorized Post Types:</strong> General Updates, Official Notices, Executive Circulars, Condolences / Besna (Obituaries), Congratulations / Achievements (Abhinandan), Matrimonial Introductions.</li>
              <li><strong>Audience Privacy Controls:</strong> Entire Organization, Specific Branch/Kendra only, Managing Committee only, or Youth Wing only.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-heart"></i> 2. Social Reactions, Comments & Interactive Polls</h4>
            <ul>
              <li><strong>Engagement Matrix:</strong> Multi-reactions (Like, Celebrate, Heart, Support), threaded nested comments with mentions (<code>@member_name</code>).</li>
              <li><strong>Live Community Opinion Polls:</strong> Single-choice or multi-choice polls with live percentage visualization, expiry timer, and anonymous voting toggle.</li>
              <li><strong>Automated Birthday & Anniversary Wishes:</strong> Daily automated celebration banners honoring members on their special milestones with one-click community greetings.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-user-shield"></i> 3. Moderation, Flagging & Safety Controls</h4>
            <ul>
              <li><strong>Automated Word Filters:</strong> Real-time profanity, political hate speech, and abusive content blocking.</li>
              <li><strong>Moderation Queue:</strong> Configurable "Pre-moderation" mode where posts require admin approval before becoming visible, or "Post-moderation" via user flagging.</li>
              <li><strong>Report & Block Mechanism:</strong> Members can report inappropriate posts or comments with categorised justification; admins can shadowban or mute offenders.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module F -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">F</span>
          <div>
            <h3>Committee, Governance & Meeting Management</h3>
            <p class="subtitle">Complete trustee oversight, term governance, meeting agendas, digital minutes (Karyavahi), resolutions (Tharav), and action tracking.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-users-cog"></i> 1. Committee Formation, Wings & Tenures</h4>
            <ul>
              <li><strong>Committee Types:</strong> Board of Trustees (Trustee Mandal), Managing Committee (Karyakarini Samiti), Advisory Board (Margdarshak Mandal), Youth Wing (Yuva Mandal), Women's Wing (Mahila Mandal), Sports/Cultural Sub-committee.</li>
              <li><strong>Designation Hierarchy:</strong> Patron, President / Pramukh, Vice President, General Secretary / Maha Mantri, Joint Secretary, Treasurer / Koshadhyaksh, Executive Committee Member.</li>
              <li><strong>Tenure & Term Governance:</strong> Multi-year defined terms (e.g. 2024–2027) with term expiry tracking, re-election limits, and historical archive of past committees.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-handshake"></i> 2. Meeting Lifecycle, Digital Agendas & Minutes</h4>
            <ul>
              <li><strong>Meeting Notice Dispatch:</strong> Automated SMS, WhatsApp, and Email notices with formal PDF agenda attachment and venue/video conferencing links.</li>
              <li><strong>Structured Agenda Items:</strong> Numbered agenda topics with allotted time, discussion notes, and linked supporting documentation.</li>
              <li><strong>Attendance Tracking:</strong> Real-time digital roll call (Present, Absent, Leave of Absence / Sick) with digital sign-off and quorum validation.</li>
              <li><strong>Resolution Register (Tharav):</strong> Formal recording of approved resolutions, proposer member, seconder member, voting split (For/Against/Abstain), and official resolution numbering.</li>
              <li><strong>Action Item Delegation:</strong> Assigning action tasks arising from resolutions to specific committee members with deadlines and progress trackers.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module G -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">G</span>
          <div>
            <h3>Membership Fee & Billing Management</h3>
            <p class="subtitle">Automated dues invoicing, multi-mode payment collection, partial payments, receipts, and automated tally reconciliation.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-file-invoice"></i> 1. Fee Schedules & Demand Generation</h4>
            <ul>
              <li><strong>Dynamic Fee Matrix:</strong> Fee rules defined by Membership Tier, Organization Unit/Branch, or Age Group.</li>
              <li><strong>Batch Demand Run:</strong> Automated generation of annual/quarterly fee demand invoices at the start of each fiscal cycle.</li>
              <li><strong>Late Surcharges & Waivers:</strong> Grace period rule engine, daily/monthly late fee penalty computation, and authorized fee hardship waivers.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-cash-register"></i> 2. Payment Processing & Multi-Channel Receipts</h4>
            <ul>
              <li><strong>Online Payment Gateway:</strong> Native integration with Razorpay, Cashfree, Stripe, PayU, and UPI QR (Instant dynamic VPA generation).</li>
              <li><strong>Offline Cashier Desk:</strong> Collection via Cash, Cheque, Demand Draft, or Direct NEFT/RTGS bank transfer with cheque clearance tracking.</li>
              <li><strong>Instant Automated Receipts:</strong> Instant 80G compliant / standard receipt generation in PDF, auto-delivered to member's WhatsApp and registered email.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module H -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">H</span>
          <div>
            <h3>Donation Management & Philanthropy</h3>
            <p class="subtitle">Charitable campaign drives, memorial gifts (Smruti Bhet), 80G tax certificates, donor CRM, and fund earmarking.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-donate"></i> 1. Campaigns, Causes & Memorial Gifting</h4>
            <ul>
              <li><strong>Targeted Campaign Funds:</strong> Building/Bhavan Construction Fund, Education Scholarship Fund, Medical Relief Fund, Gaushala Fund, General Corpus.</li>
              <li><strong>Dedicated Memorial Donations:</strong> Donations made "In Loving Memory of (Late...)" or "On Occasion of (Silver Anniversary / Birthday)" with donor honor board listing.</li>
              <li><strong>Donor Recognition Tiers:</strong> Platinum Patron, Golden Benefactor, Silver Donor plaques with automated display on website and community bulletin.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-receipt"></i> 2. 80G Compliance & Fund Utilization</h4>
            <ul>
              <li><strong>Statutory 80G Certificates:</strong> Automated generation of valid 80G tax exemption certificates including mandatory Donor PAN, full address, and unique certificate serials.</li>
              <li><strong>Form 10BD Annual Filing Export:</strong> One-click export formatted strictly according to the Indian Income Tax Department's Form 10BD electronic filing specifications.</li>
              <li><strong>Tied Fund Utilization Tracking:</strong> Linking donation receipts to specific expenditure vouchers to prove 100% fund deployment to audited charitable activities.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module I -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">I</span>
          <div>
            <h3>Double-Entry Accounting & Financial Management</h3>
            <p class="subtitle">Complete institutional accounting compliant with Public Trust and Societies Acts. Full debit/credit vouchers, trial balance, and statutory reports.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-book"></i> 1. Chart of Accounts & Multi-Level Ledgers</h4>
            <ul>
              <li><strong>Four-Tier Hierarchical Ledger Tree:</strong> Class (Asset, Liability, Equity/Corpus, Income, Expense) &rarr; Major Group &rarr; Sub-Group &rarr; General Ledger Account.</li>
              <li><strong>Corpus vs Non-Corpus Distinction:</strong> Strict segregation of permanent endowment corpus funds from operational general funds.</li>
              <li><strong>Opening Balances & Sub-Ledgers:</strong> Fiscal year opening balance entry with debtor (member) and creditor (vendor) sub-ledger breakdown.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-file-invoice"></i> 2. Voucher Management System</h4>
            <ul>
              <li><strong>Standard Accounting Vouchers:</strong> Payment Voucher, Receipt Voucher, Journal Voucher, Contra Voucher (Bank-to-Cash / Bank-to-Bank transfer).</li>
              <li><strong>Mathematical Balance Guard:</strong> Real-time debit-credit equality enforcement: <code>&Sigma; Debit == &Sigma; Credit</code>. Vouchers cannot be saved out of balance.</li>
              <li><strong>Dual-Authorization Workflow:</strong> Maker-Checker approval workflow (Accountant drafts &rarr; Treasurer reviews &rarr; Managing Trustee approves &rarr; Posted to General Ledger).</li>
              <li><strong>Immutability & Audit Trail:</strong> Once posted and audited, vouchers cannot be edited or deleted without creating a formal reversal voucher with audit reason.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-chart-line"></i> 3. Statutory Financial Statements & Reconciliation</h4>
            <ul>
              <li><strong>Real-Time Financial Statements:</strong> Cash Book, Bank Book, Day Book, General Ledger Statement, Trial Balance, Income & Expenditure Statement, Balance Sheet.</li>
              <li><strong>Bank Reconciliation (BRS):</strong> Matching bank statements with recorded vouchers, tracking uncleared cheques, and automatic variance detection.</li>
              <li><strong>Budgetary Control:</strong> Departmental and unit annual budget allocation with live budget-vs-actual variance tracking and overspend warnings.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module J -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">J</span>
          <div>
            <h3>Events Management & Ticketing</h3>
            <p class="subtitle">Annual General Meetings (AGM), festivals (Diwali, Sneh Milan), cultural programs, attendee registration, and QR turnstile check-in.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-calendar-check"></i> 1. Event Publishing & Ticket Tiers</h4>
            <ul>
              <li><strong>Event Profile:</strong> Multi-day schedules, banner images, venue address with Google Map pin, speaker/dignitary bios, and food/catering coupons.</li>
              <li><strong>Tiered Ticketing:</strong> Member Free Pass, Member Family Bundle, Non-Member Guest Ticket, VIP Reserved Seating, Early Bird discounts.</li>
              <li><strong>Capacity Quotas:</strong> Venue maximum capacity limits with automatic waitlisting upon sell-out.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-barcode"></i> 2. Registration, QR Passes & Turnstile Check-In</h4>
            <ul>
              <li><strong>Instant Pass Generation:</strong> PDF pass with anti-counterfeit encrypted QR code delivered via SMS/WhatsApp/Email.</li>
              <li><strong>Mobile Scanning App:</strong> Smartphone camera scanning at entry gates for instant validation, duplicate pass detection, and real-time head-count.</li>
              <li><strong>Food & Dinner Coupon Validation:</strong> Secondary QR scan for dinner/snack counters (Bhojan coupon) ensuring zero leakage.</li>
              <li><strong>Post-Event Photo Gallery & Feedback:</strong> High-resolution curated photo albums, video highlights, and digital attendee satisfaction surveys.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module K -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">K</span>
          <div>
            <h3>Omnichannel Communication & Automation</h3>
            <p class="subtitle">Bulk SMS, verified WhatsApp Business API, transactional emails, automated milestone greetings, and payment reminders.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-broadcast-tower"></i> 1. Broadcast Channels & Gateway Routing</h4>
            <ul>
              <li><strong>SMS Gateways:</strong> Integration with DLT-compliant Indian telecom gateways (ValueFirst, Gupshup, Textlocal) and international Twilio.</li>
              <li><strong>WhatsApp Business API:</strong> Meta verified WhatsApp template messaging with interactive quick-reply buttons and rich media attachments.</li>
              <li><strong>Email Delivery:</strong> Transactional SMTP routing via Amazon SES, SendGrid, or Mailgun with open and click-rate analytics.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-robot"></i> 2. Automated Trigger & Cron Schedule Engine</h4>
            <ul>
              <li><strong>Milestone Automations:</strong> Automatic daily cron firing personalized Birthday wishes and 25th/50th Wedding Anniversary greetings at 08:00 AM.</li>
              <li><strong>Fee Dues & Expiry Sequences:</strong> Automated T-30, T-15, T-7, and T-Day WhatsApp payment reminders with one-click direct payment links.</li>
              <li><strong>Urgent Condolence / Besna Bulletins:</strong> Instant broadcast of sudden demise and prayer meeting notices to targeted zone members within 10 minutes.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module L -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">L</span>
          <div>
            <h3>Role-Based Access Control (RBAC) & User Security</h3>
            <p class="subtitle">Granular multi-tenant user permissions, jurisdictional data boundaries, two-factor authentication, and tamper-proof audit trails.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-user-lock"></i> 1. Multi-Tier Role Archetypes</h4>
            <ul>
              <li><strong>Super Admin (Platform):</strong> Global SaaS health, tenant provisioning, system billing, and platform configuration.</li>
              <li><strong>Central Trustee / President:</strong> Unrestricted organization-wide read access, policy approvals, financial oversight.</li>
              <li><strong>General Secretary:</strong> Member approvals, meeting scheduling, circular publishing, event management.</li>
              <li><strong>Treasurer / Head Accountant:</strong> Full financial management, voucher posting, ledger locking, bank reconciliation, audit reports.</li>
              <li><strong>Branch / Kendra Admin:</strong> Restricted access scoped strictly to their geographic chapter or local ward members.</li>
              <li><strong>Ordinary Member:</strong> Self-service portal: view profile, update family biodata, pay dues, download receipts, register for events, view social feed.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-key"></i> 2. Granular Permissions & Audit Vault</h4>
            <ul>
              <li><strong>Matrix Permissions:</strong> Scoped permissions per module across <code>Create</code>, <code>Read</code>, <code>Update</code>, <code>Delete</code>, <code>Approve</code>, <code>Export</code>, and <code>Print</code>.</li>
              <li><strong>Two-Factor Authentication (2FA):</strong> Mandatory TOTP authenticator (Google Authenticator) or SMS OTP for financial and trustee logins.</li>
              <li><strong>Tamper-Proof Audit Vault:</strong> Every CRUD operation logs user ID, timestamp, IP address, user agent, old JSON snapshot, and new JSON delta.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module M -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">M</span>
          <div>
            <h3>Digital Document Management Vault</h3>
            <p class="subtitle">Centralized cloud storage for statutory certificates, trust deeds, minutes books, property titles, and member documents.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-folder"></i> 1. Document Taxonomy & Version Control</h4>
            <ul>
              <li><strong>Categorised Hierarchy:</strong> Statutory & Legal (Trust Deed, 80G, 12A), Property & Asset Documents, Meeting Minutes & Resolutions, Financial Audits, Member Identity Proofs.</li>
              <li><strong>Encrypted Object Storage:</strong> Storage on S3-compatible private buckets with client-side AES-256 encryption.</li>
              <li><strong>Version History:</strong> Retains all historic amendments and updates with author notes and restoration options.</li>
              <li><strong>Expiration & Renewal Alerts:</strong> Automated reminders 60 days prior to expiry of insurance policies, AMC contracts, or statutory licenses.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module N -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">N</span>
          <div>
            <h3>Reports, Analytics & Executive Dashboards</h3>
            <p class="subtitle">Real-time KPI visualization, demographic intelligence, collection trends, and statutory compliance reporting.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-tachometer-alt"></i> 1. Role-Specific Executive Dashboards</h4>
            <ul>
              <li><strong>Trustee Executive Cockpit:</strong> Total members, net active growth, fund collection vs budget, outstanding dues, upcoming AGM countdown.</li>
              <li><strong>Kendra / Branch Dashboard:</strong> Chapter membership stats, local event signups, pending member approvals, local collection tally.</li>
              <li><strong>Member Personal Dashboard:</strong> Family profile completeness meter, membership status card, upcoming events, fee due alerts, community feed.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-chart-pie"></i> 2. Demographic & Financial Intelligence</h4>
            <ul>
              <li><strong>Demographic Analytics:</strong> Age-pyramid distribution, blood group availability chart, geographic heatmap, education & profession distribution.</li>
              <li><strong>Financial Intelligence:</strong> Monthly donation trends, fee collection aging analysis (0-30, 31-60, 61-90, 90+ days), campaign ROI.</li>
              <li><strong>Multi-Format Output:</strong> Instant export to formatted Excel, CSV, or signed print-ready PDF reports with corporate branding.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module O: Welfare Schemes -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">O</span>
          <div>
            <h3>Welfare, Scholarships & Medical Relief Module</h3>
            <p class="subtitle">Dedicated philanthropic aid workflows for community trusts (e.g. MMP and SNS Mumbai aid programs).</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-hand-holding-medical"></i> 1. Welfare Schemes & Online Beneficiary Applications</h4>
            <ul>
              <li><strong>Scheme Master:</strong> Higher Education Scholarships, Widow Monthly Pensions, Dialysis / Cancer Medical Subsidies, Senior Citizen Healthcare Aid, Marriage Financial Grants.</li>
              <li><strong>Beneficiary Application Funnel:</strong> Member applies online with student marksheets, fee structure, medical hospital bills, and family income certificates.</li>
              <li><strong>Confidential Scrutiny:</strong> Dedicated Welfare Committee scrutiny with privacy protection, home visit verification notes, and sanction amount recommendations.</li>
              <li><strong>Direct Disbursement Linkage:</strong> Approved sanctions automatically create pre-filled Payment Vouchers in the Accounting Module for direct NEFT disbursement.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module P: Facility & Hall Booking -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">P</span>
          <div>
            <h3>Community Halls, Wadi & Asset Lending Module</h3>
            <p class="subtitle">Rental and slot management for community properties (Bhavan/Wadi), guest houses, and medical equipment libraries.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-archway"></i> 1. Hall / Bhavan & Room Reservation Engine</h4>
            <ul>
              <li><strong>Venue Slots & Pricing:</strong> Morning, Evening, or Full-Day slots with differentiated pricing for Registered Members vs Non-Members.</li>
              <li><strong>Visual Availability Calendar:</strong> Color-coded live booking calendar preventing double bookings for weddings, prayer meetings, and seminars.</li>
              <li><strong>Security Deposit & Damage Refund:</strong> Tracking security deposits, kitchen cleaning charges, electricity meter surcharges, and deposit refund processing.</li>
            </ul>
          </div>
          <div class="feature-card">
            <h4><i class="fas fa-wheelchair"></i> 2. Medical Equipment Lending Library</h4>
            <ul>
              <li><strong>Lending Asset Inventory:</strong> Oxygen concentrators, hospital beds, wheelchairs, walkers, crutches, and suction machines.</li>
              <li><strong>Issue & Return Tracking:</strong> Borrower member identity, refundable deposit, condition check at issue/return, and maintenance logging.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module Q: Elections -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">Q</span>
          <div>
            <h3>Democratic Elections & Secret Ballot E-Voting</h3>
            <p class="subtitle">Constitutional electoral governance, nomination scrutiny, voter rolls, and verifiable secret ballot voting.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-vote-yea"></i> 1. Electoral Process & Secret Voting</h4>
            <ul>
              <li><strong>Election Notification:</strong> Formal election announcement with published schedule: Nomination filing, withdrawal deadline, scrutiny, voter list freeze.</li>
              <li><strong>Digital Nomination Filing:</strong> Candidates submit bio, manifesto, proposer, and seconder online with administrative scrutiny approval.</li>
              <li><strong>Blind Secret Ballot:</strong> Cryptographically sealed electronic voting ensuring complete voter anonymity while preventing duplicate voting.</li>
              <li><strong>Instant Automated Tallying:</strong> Real-time counting immediately upon voting deadline closure in the presence of election commissioners.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Module R: Grievances -->
      <div class="content-card">
        <div class="module-title-banner">
          <span class="mod-letter">R</span>
          <div>
            <h3>Grievance Redressal & Helpdesk Ticketing</h3>
            <p class="subtitle">Transparent complaint submission, SLA resolution tracking, officer assignment, and satisfaction ratings.</p>
          </div>
        </div>
        <div class="feature-grid">
          <div class="feature-card">
            <h4><i class="fas fa-headset"></i> 1. Helpdesk Ticketing & Resolution SLAs</h4>
            <ul>
              <li><strong>Multi-Channel Ticket Raising:</strong> Tickets raised via web portal, mobile app, or WhatsApp bot with automated tracking numbers.</li>
              <li><strong>Categorised Routing:</strong> Maintenance issues, membership certificate delays, billing queries, or committee grievances routed automatically to the designated officer.</li>
              <li><strong>Resolution SLA & Escalation:</strong> Automated escalation to General Secretary if a ticket remains unresolved beyond 7 business days.</li>
            </ul>
          </div>
        </div>
      </div>
    </section>
    `;
  }
};

