module.exports = {
  getScreensList: function() {
    return `
    <section id="section-5" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 05</span>
        <h2>5. Detailed Screen & UI/UX Planning</h2>
      </div>

      <div class="content-card">
        <p>The platform provides <strong>46 purpose-built responsive screens</strong> structured into intuitive workflows for Desktop, Tablet, and Mobile devices. Every screen adheres to strict usability guidelines, keyboard shortcuts, inline validations, and contextual permission gates.</p>
        
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width: 100px;">Screen ID</th>
                <th style="width: 180px;">Screen Name</th>
                <th>Purpose & Description</th>
                <th>Key Fields & UI Controls</th>
                <th>Actions & Buttons</th>
                <th>Validations & RBAC Permissions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>SCR-001</code></td>
                <td><strong>System Login & 2FA</strong></td>
                <td>Secure authentication gateway for members, staff, and trustees.</td>
                <td>Username/Mobile/Email, Password (masked), Remember Me, Captcha, TOTP OTP Code input.</td>
                <td>Sign In, Forgot Password, Register New Member, Send OTP via WhatsApp.</td>
                <td>Account lockout after 5 invalid attempts; rate limiting; public access.</td>
              </tr>
              <tr>
                <td><code>SCR-002</code></td>
                <td><strong>Organization Switcher</strong></td>
                <td>Allows multi-unit trustees to switch operational context between National, State, Zonal, or City Kendra.</td>
                <td>Searchable dropdown of authorized units/branches, visual tree selector, active tenant badge.</td>
                <td>Switch Context, Set Default Unit, View Jurisdictional Scope.</td>
                <td>Session refresh with updated scoped permissions; Super Admin & Multi-Unit Admins only.</td>
              </tr>
              <tr>
                <td><code>SCR-003</code></td>
                <td><strong>Executive Central Dashboard</strong></td>
                <td>Executive high-level visual telemetry of organization health, finance, and members.</td>
                <td>KPI Cards (Total Members, Active Dues, Monthly Collections, Active Committees), Trend Charts, Recent Activity Log.</td>
                <td>Filter Date Range, Quick Action (+ Add Member, + New Voucher, + Post Notice), Export PDF Snapshot.</td>
                <td>Data masked according to role; cached with 5-minute background refresh; Admins & Trustees only.</td>
              </tr>
              <tr>
                <td><code>SCR-004</code></td>
                <td><strong>Organization Profile & Branding</strong></td>
                <td>Configuration of organization legal credentials, branding, letterhead, and tax registration.</td>
                <td>Trust Name, Reg No, PAN, 80G No, Logo upload, Stamp/Signature upload, Primary/Secondary Colors, Registered Address.</td>
                <td>Save Changes, Preview Letterhead, Verify 80G Status, Upload Seal.</td>
                <td>Mandatory PAN format validation, file size limit (2MB PNG/SVG); Super Admin & Org Admin only.</td>
              </tr>
              <tr>
                <td><code>SCR-005</code></td>
                <td><strong>Organization Hierarchy & Units</strong></td>
                <td>Visual tree editor for establishing Zones, Kendras, Chapters, and Wards.</td>
                <td>Unit Code, Unit Name, Unit Type (Zone/Kendra/Ward), Parent Unit, Head Office Address, Designated President.</td>
                <td>+ Add Sub-Unit, Reassign Parent, Deactivate Unit, View Unit Directory.</td>
                <td>Loop prevention in tree structure; cannot delete unit with active members; Org Admin only.</td>
              </tr>
              <tr>
                <td><code>SCR-006</code></td>
                <td><strong>Master Data Console</strong></td>
                <td>Unified editor for all system master lookup tables.</td>
                <td>Master Table Selector, Code, Name, Native Language Label, Display Order, Active Status flag.</td>
                <td>+ Add Record, Edit Inline, Toggle Status, Bulk Master Reorder.</td>
                <td>Unique code enforcement per organization; system masters locked against deletion; Admins only.</td>
              </tr>
              <tr>
                <td><code>SCR-007</code></td>
                <td><strong>Custom Field Builder</strong></td>
                <td>Zero-code dynamic attribute configuration for members, donations, and events.</td>
                <td>Entity Type, Field Label, Field Key, Data Type (Text, Number, Date, Dropdown), Mandatory flag, Visibility.</td>
                <td>+ Add Field, Reorder Fields, Configure Options, Save Layout.</td>
                <td>Field key immutability after record insertion; Org Admin only.</td>
              </tr>
              <tr>
                <td><code>SCR-008</code></td>
                <td><strong>Member Directory & Grid</strong></td>
                <td>Core member management list with advanced multi-parametric filtering and batch actions.</td>
                <td>Search input, Filters (Unit, Status, Blood Group, Gotra, Native Village, Age Bracket), Column Chooser.</td>
                <td>+ New Member, Bulk SMS, Bulk WhatsApp, Export to Excel, Print ID Cards.</td>
                <td>Scoped to user's assigned unit; pagination (25/50/100); Admins & Staff.</td>
              </tr>
              <tr>
                <td><code>SCR-009</code></td>
                <td><strong>Member Registration Wizard</strong></td>
                <td>4-Step wizard for enrolling new members with complete biodata, family linkage, and docs.</td>
                <td>Step 1: Personal Biodata, Step 2: Native Place & Culture, Step 3: Addresses & Contact, Step 4: Documents & Membership Tier.</td>
                <td>Previous, Next, Save Draft, Submit Application, Verify Proposer.</td>
                <td>Mobile & Email uniqueness check; Aadhaar checksum validation; Admins & Self-Registration.</td>
              </tr>
              <tr>
                <td><code>SCR-010</code></td>
                <td><strong>Member 360&deg; Profile View</strong></td>
                <td>Comprehensive single-pane view of a member's complete engagement and history.</td>
                <td>Tabs: Personal Details, Family Tree, Membership & Cards, Payment Ledger, Donations, Committee Tenures, Documents, Event Passes.</td>
                <td>Edit Profile, Print Biodata PDF, Issue ID Card, Record Payment, Add Note.</td>
                <td>Strict field-level privacy masking for sensitive personal data; Admins & Member Self-Service.</td>
              </tr>
              <tr>
                <td><code>SCR-011</code></td>
                <td><strong>Member Bulk Import Tool</strong></td>
                <td>High-volume Excel onboarding tool with auto-mapping and error validation.</td>
                <td>File upload dropzone, Column mapping dropdowns, Validation preview table, Error highlight badges.</td>
                <td>Download Template, Validate File, Execute Import, Download Error Log.</td>
                <td>Rollback transaction on unhandled fatal schema error; Org Admin only.</td>
              </tr>
              <tr>
                <td><code>SCR-012</code></td>
                <td><strong>Family Registry (Kutumb Directory)</strong></td>
                <td>List of all registered households with member count and head of family.</td>
                <td>Family Code, Family Head Name, Native Village, Residential Address, Member Count, Zone.</td>
                <td>+ Create Family, View Family, Print Family Roster, Split Family.</td>
                <td>Head of family must be an active adult member; Admins & Members.</td>
              </tr>
              <tr>
                <td><code>SCR-013</code></td>
                <td><strong>Family Detail & Kinship Tree</strong></td>
                <td>Visual interactive generational genealogy chart and household member list.</td>
                <td>Family Head details, Member cards with relationships (Spouse, Child, Parent), Ancestral lineage notes.</td>
                <td>+ Add Family Member, Change Family Head, Detach Member, Print Family Tree.</td>
                <td>Relationship validation (cannot have two mothers, spouse gender consistency); Admins & Family Head.</td>
              </tr>
              <tr>
                <td><code>SCR-014</code></td>
                <td><strong>Membership Plans & Pricing</strong></td>
                <td>Subscription tiers and admission fee rule configuration.</td>
                <td>Plan Name, Plan Code, Validity (Monthly/Annual/Life), Fee Amount, Admission Fee, Grace Days.</td>
                <td>+ New Plan, Edit Plan, Toggle Active Status, View Subscribed Members.</td>
                <td>Cannot delete plan with active subscribers; Admins & Treasurers.</td>
              </tr>
              <tr>
                <td><code>SCR-015</code></td>
                <td><strong>Membership Application Scrutiny</strong></td>
                <td>Multi-level workflow queue for reviewing online membership requests.</td>
                <td>Applicant List, Attached ID proofs, Proposer verification badge, Scrutiny history, Comments.</td>
                <td>Approve Application, Request More Info, Reject with Reason, Assign Member ID.</td>
                <td>Mandatory rejection reason; multi-level approval progression; Committee Admins.</td>
              </tr>
              <tr>
                <td><code>SCR-016</code></td>
                <td><strong>Digital ID Cards & Batch Print</strong></td>
                <td>Template designer and bulk generator for digital passes and physical PVC cards.</td>
                <td>Card Template selector, Batch filter, Print preview canvas, QR token inspector.</td>
                <td>Generate PDF Batch, Send to Apple/Google Wallet, Mark Printed, Reissue Card.</td>
                <td>Encrypted QR signature generation; Admins only.</td>
              </tr>
              <tr>
                <td><code>SCR-017</code></td>
                <td><strong>Social Community Feed</strong></td>
                <td>Interactive timeline for organization updates, celebrations, and notices.</td>
                <td>Post Composer (Text, Photos, Video URL, Poll), Timeline feed items, Like/Comment drawers.</td>
                <td>Publish Post, React, Comment, Share, Report Content, Filter by Category.</td>
                <td>Profanity filter check; media size restrictions (Max 10MB); All Active Members.</td>
              </tr>
              <tr>
                <td><code>SCR-018</code></td>
                <td><strong>Social Content Moderation</strong></td>
                <td>Queue for reviewing reported posts and pre-moderation items.</td>
                <td>Pending Posts Queue, Reported Comments, Flag Reason, Author profile snippet.</td>
                <td>Approve Post, Delete Post, Warn User, Shadowban Author, Dismiss Report.</td>
                <td>Audit logging of moderator action; Moderator & Admin role.</td>
              </tr>
              <tr>
                <td><code>SCR-019</code></td>
                <td><strong>Committee & Board Directory</strong></td>
                <td>Overview of active governance bodies, wings, and committee listings.</td>
                <td>Committee Name, Committee Type, Current Tenure, President Name, Member Count, Status.</td>
                <td>+ Create Committee, View Members, Archive Tenure, Print Committee List.</td>
                <td>Admins & Committee Members.</td>
              </tr>
              <tr>
                <td><code>SCR-020</code></td>
                <td><strong>Committee Member & Designation Editor</strong></td>
                <td>Assigning members to specific executive roles and tenures.</td>
                <td>Committee Selector, Member Picker, Designation (President, Secretary, Treasurer), Appointment Date, Term End.</td>
                <td>Appoint Member, Relieve Member, Reorder Hierarchy, Export Official Order.</td>
                <td>Only one President and Treasurer allowed per committee tenure; Committee Admins.</td>
              </tr>
              <tr>
                <td><code>SCR-021</code></td>
                <td><strong>Meeting Scheduler & Agenda Builder</strong></td>
                <td>Organizing executive and general meetings with structured agendas.</td>
                <td>Meeting Title, Committee, Date & Time, Venue / Video Link, Numbered Agenda Items with allot times.</td>
                <td>Save Notice, Send WhatsApp Notice, Attach Board Papers, Finalize Agenda.</td>
                <td>Notice period check (e.g. 7 days prior for AGM); Committee Secretaries.</td>
              </tr>
              <tr>
                <td><code>SCR-022</code></td>
                <td><strong>Meeting Minutes & Resolutions (Tharav)</strong></td>
                <td>Live meeting recording console for attendance, minutes, and voting resolutions.</td>
                <td>Attendance Roll Call (Present/Absent/Leave), Minutes Rich Text Editor, Resolution Form (Proposer, Seconder, Votes).</td>
                <td>Save Draft Minutes, Record Resolution, Close Meeting, Publish Official Minutes PDF.</td>
                <td>Quorum validation check before voting; Committee Secretary & President.</td>
              </tr>
              <tr>
                <td><code>SCR-023</code></td>
                <td><strong>Fee Schedules & Demand Run</strong></td>
                <td>Configuration of periodic dues and batch invoicing for members.</td>
                <td>Fiscal Year, Tier Selector, Amount, Due Date, Late Fee Rule, Exemption Category.</td>
                <td>Run Batch Invoicing, Preview Demand, Post Demands, Send Invoices.</td>
                <td>Cannot generate duplicate invoices for the same fiscal cycle; Treasurers & Accountants.</td>
              </tr>
              <tr>
                <td><code>SCR-024</code></td>
                <td><strong>Cashier Collection Desk (POS)</strong></td>
                <td>Fast counter collection screen for processing cash, cheque, and UPI payments.</td>
                <td>Member Search, Outstanding Demands table, Collection Amount, Payment Mode, Cheque No, Bank Name.</td>
                <td>Collect & Print Receipt, Send WhatsApp Receipt, Split Dues Allocation.</td>
                <td>Cheque date cannot be older than 3 months; Cashiers & Accountants.</td>
              </tr>
              <tr>
                <td><code>SCR-025</code></td>
                <td><strong>Dues & Defaulters Register</strong></td>
                <td>Tracking unpaid membership balances with aging brackets and automated reminders.</td>
                <td>Member List, Total Billed, Total Paid, Balance Dues, Overdue Days (0-30, 31-90, 90+).</td>
                <td>Send Payment Reminder, Apply Surcharge, Process Hardship Waiver, Export List.</td>
                <td>Waiver requires Trustee authorization; Treasurers & Admins.</td>
              </tr>
              <tr>
                <td><code>SCR-026</code></td>
                <td><strong>Donation Campaigns & Drives</strong></td>
                <td>Creating and monitoring targeted fundraising causes and corpus drives.</td>
                <td>Campaign Title, Goal Amount, Collected Amount progress bar, Start/End Dates, 80G flag, Fund Ledger.</td>
                <td>+ Create Campaign, Share Campaign Link, View Donors, Close Campaign.</td>
                <td>Tied to a valid liability/equity accounting fund; Admins & Treasurers.</td>
              </tr>
              <tr>
                <td><code>SCR-027</code></td>
                <td><strong>Donation Receipting & 80G Console</strong></td>
                <td>Recording philanthropic donations and issuing legal tax certificates.</td>
                <td>Donor Name, Donor PAN, Full Address, Amount, Purpose/Campaign, Payment Mode, In Memory Of text.</td>
                <td>Generate 80G Receipt, Print 80G PDF, Email Certificate, Export Form 10BD.</td>
                <td>Mandatory PAN for donations exceeding Rs. 5,000; Accountants & Treasurers.</td>
              </tr>
              <tr>
                <td><code>SCR-028</code></td>
                <td><strong>Chart of Accounts Manager</strong></td>
                <td>Hierarchical tree of accounting classes, groups, and general ledgers.</td>
                <td>Account Code, Account Name, Class (Asset/Liab/Inc/Exp), Group, Opening Balance (Dr/Cr), Subledger flag.</td>
                <td>+ Add Ledger Account, Edit Ledger, View Account Register, Deactivate Account.</td>
                <td>Cannot delete account containing posted transactions; Head Accountant only.</td>
              </tr>
              <tr>
                <td><code>SCR-029</code></td>
                <td><strong>Voucher Entry Screen (Multi-Type)</strong></td>
                <td>Core double-entry journal, payment, receipt, and contra voucher creation.</td>
                <td>Voucher Type, Voucher No, Date, Debit Account, Debit Amount, Credit Account, Credit Amount, Narration, Cost Center.</td>
                <td>+ Add Row, Balance Voucher, Save as Draft, Submit for Approval, Post.</td>
                <td>Mathematical rule: Sum of Debits must equal Sum of Credits; Accountants only.</td>
              </tr>
              <tr>
                <td><code>SCR-030</code></td>
                <td><strong>Voucher Approval Queue (Checker)</strong></td>
                <td>Dual-control verification queue for posted financial entries.</td>
                <td>Pending Voucher List, Maker Name, Amount, Accounts Breakdown, Attached Bill Scans.</td>
                <td>Approve & Post to GL, Send Back with Remarks, Reject Voucher.</td>
                <td>Checker cannot be the same user who created the voucher; Treasurer & Managing Trustee.</td>
              </tr>
              <tr>
                <td><code>SCR-031</code></td>
                <td><strong>Financial Statements (Trial Balance, P&L, Balance Sheet)</strong></td>
                <td>Real-time institutional financial reporting engine.</td>
                <td>Date Range, Financial Year, Level of Detail (Group/Subgroup/Ledger), Branch/Unit Filter.</td>
                <td>Generate Statement, Drill Down to Ledger, Export Excel, Print Audited Format.</td>
                <td>Read-only aggregated calculation; Trustees, Treasurers & Auditors.</td>
              </tr>
              <tr>
                <td><code>SCR-032</code></td>
                <td><strong>Bank Reconciliation (BRS)</strong></td>
                <td>Reconciling bank statement transactions with internal cash/bank book entries.</td>
                <td>Bank Account Selector, Statement Date, Internal Vouchers list, Bank Statement upload drawer.</td>
                <td>Auto-Match by Cheque/UTR, Mark Cleared, Add Bank Charges Voucher, Finalize BRS.</td>
                <td>Variance must be zero for finalized statement; Accountants & Auditors.</td>
              </tr>
              <tr>
                <td><code>SCR-033</code></td>
                <td><strong>Events & Program Manager</strong></td>
                <td>Publishing upcoming community festivals, AGMs, seminars, and sports meets.</td>
                <td>Event Title, Category, Dates & Times, Venue, Ticket Tiers, Capacity, Banner Upload, Public toggle.</td>
                <td>+ Create Event, Publish, Manage Attendees, Print Food Coupons, Close Event.</td>
                <td>Start date must be in future; Admins & Event Secretaries.</td>
              </tr>
              <tr>
                <td><code>SCR-034</code></td>
                <td><strong>Event Check-In & Gate Scanner</strong></td>
                <td>High-speed entrance scanning for mobile QR ticket verification.</td>
                <td>Webcam / Scanner viewport, Attendee lookup search, Entry Gate selector, Live Attendance Count.</td>
                <td>Scan Pass, Manual Check-in, Redeem Food Pass, View Checked-in Log.</td>
                <td>Duplicate check-in prevention with audible alert; Gate Volunteers & Staff.</td>
              </tr>
              <tr>
                <td><code>SCR-035</code></td>
                <td><strong>Communication Broadcast Console</strong></td>
                <td>Omnichannel SMS, WhatsApp, and Email broadcast dispatch.</td>
                <td>Channel Selector (WhatsApp/SMS/Email), Target Group (All, Kendra, Committee, Defaulters), Template Selector, Variables.</td>
                <td>Send Test Message, Schedule Blast, Send Now, View Delivery Analytics.</td>
                <td>DLT template approval validation; rate limit governor; Admins only.</td>
              </tr>
              <tr>
                <td><code>SCR-036</code></td>
                <td><strong>Automated Reminder Rules</strong></td>
                <td>Configuring auto-firing crons for birthdays, dues, and meetings.</td>
                <td>Event Trigger, Days Before/After (-30, 0, +7), Execution Time (e.g. 08:30 AM), Channels, Dynamic Placeholders.</td>
                <td>+ New Rule, Toggle Active, Test Execution, View Run Logs.</td>
                <td>Admins & Communication Managers.</td>
              </tr>
              <tr>
                <td><code>SCR-037</code></td>
                <td><strong>User & Role Management</strong></td>
                <td>Creating staff, admin, and trustee logins with granular permission matrices.</td>
                <td>User Name, Email, Mobile, Role Selector, Assigned Branch/Unit, MFA Status toggle.</td>
                <td>+ Add User, Reset 2FA, Lock Account, Configure Custom Permissions.</td>
                <td>Super Admin & Org Admin only.</td>
              </tr>
              <tr>
                <td><code>SCR-038</code></td>
                <td><strong>Audit Logs & Security Viewer</strong></td>
                <td>Forensic timeline of all system actions, IP addresses, and data changes.</td>
                <td>Date Range, User Filter, Action Filter (Create/Update/Delete/Login), Entity Filter.</td>
                <td>View Diff Modal, Export Audit Log, Flag Suspicious IP.</td>
                <td>Read-only append-only table; Super Admin & Compliance Auditor.</td>
              </tr>
              <tr>
                <td><code>SCR-039</code></td>
                <td><strong>Document Vault & Categories</strong></td>
                <td>Cloud folder hierarchy for organizational trust deeds, licenses, and minutes.</td>
                <td>Folder Tree, File Upload Dropzone, Document Title, Expiry Date, Confidentiality Level.</td>
                <td>Upload Document, Download, Set Expiry Alert, Share Secure Link.</td>
                <td>Encrypted file storage; virus scan on upload; Admins & Trustees.</td>
              </tr>
              <tr>
                <td><code>SCR-040</code></td>
                <td><strong>Welfare Schemes & Applications</strong></td>
                <td>Managing education scholarships and medical aid grants.</td>
                <td>Scheme Name, Budget Allocated, Applications Queue, Applicant Income, Requested Amount, Sanctioned Amount.</td>
                <td>Sanction Grant, Reject Application, Generate Disbursement Voucher, View Beneficiaries.</td>
                <td>Welfare Committee approval required before disbursement; Welfare Committee & Trustees.</td>
              </tr>
              <tr>
                <td><code>SCR-041</code></td>
                <td><strong>Hall & Wadi Booking Calendar</strong></td>
                <td>Visual reservation matrix for community halls, wadis, and guest rooms.</td>
                <td>Venue Selector, Month/Week Calendar View, Time Slot, Event Purpose, Security Deposit Status.</td>
                <td>+ New Booking, Block Dates, Collect Deposit, Refund Deposit, Print Booking Slip.</td>
                <td>Conflict prevention (double-booking lock); Facility Managers & Cashiers.</td>
              </tr>
              <tr>
                <td><code>SCR-042</code></td>
                <td><strong>Medical Equipment Lending Register</strong></td>
                <td>Inventory tracking for oxygen concentrators, hospital beds, and wheelchairs.</td>
                <td>Equipment Serial, Asset Type, Current Status (Available/Issued/Maintenance), Borrower Member, Return Due Date.</td>
                <td>Issue Equipment, Record Return, Collect Deposit, Log Maintenance.</td>
                <td>Only active members eligible; Equipment In-Charge.</td>
              </tr>
              <tr>
                <td><code>SCR-043</code></td>
                <td><strong>Elections & E-Voting Console</strong></td>
                <td>Governance console for managing constitutional committee elections.</td>
                <td>Election Title, Posts/Seats, Candidate Nominations List, Scrutiny Status, Live Voting Timer, Blind Ballot Tally.</td>
                <td>Publish Election, Scrutinize Candidates, Open Voting, Close Voting, Decrypt Results.</td>
                <td>Cryptographic secret ballot; voter authentication check; Election Commissioners only.</td>
              </tr>
              <tr>
                <td><code>SCR-044</code></td>
                <td><strong>Grievance & Helpdesk Console</strong></td>
                <td>Member complaint tracking and resolution ticketing system.</td>
                <td>Ticket No, Subject, Category, Assigned Officer, Status (Open/In-Progress/Resolved), SLA Timer.</td>
                <td>Assign Ticket, Post Internal Note, Reply to Member, Close Ticket.</td>
                <td>SLA escalation notifications; Helpdesk Staff & Admins.</td>
              </tr>
              <tr>
                <td><code>SCR-045</code></td>
                <td><strong>Member Self-Service Dashboard</strong></td>
                <td>Mobile-first portal for individual community members and families.</td>
                <td>Digital ID Card with dynamic QR, Dues balance card, Family member list, Upcoming community events, Social feed preview.</td>
                <td>Pay Dues Online, Download Receipt, Register for Event, Update Family Biodata, View Notices.</td>
                <td>Restricted strictly to the logged-in member's family records; Member Role.</td>
              </tr>
              <tr>
                <td><code>SCR-046</code></td>
                <td><strong>Matrimonial Directory & Search</strong></td>
                <td>Dedicated community matrimonial profile directory (as standard in MMP/SNS portals).</td>
                <td>Search filters (Age, Height, Gotra, Education, Profession, Native Place), Profile Photo, Horoscopic Details.</td>
                <td>Express Interest, Download Biodata PDF, Request Contact Details, Shortlist Profile.</td>
                <td>Requires verified matrimonial registration and parental consent flag; Registered Members.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    `;
  }
};

