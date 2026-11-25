module.exports = {
  getMastersList: function() {
    return `
    <section id="section-6" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 06</span>
        <h2>6. Complete Master Data Management System</h2>
      </div>

      <div class="content-card">
        <p>The Master Management module standardizes all organizational lookups, classifications, and system taxonomies. Every master is tenant-isolated with default system presets and customizable localized overrides.</p>
        
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width: 180px;">Master Table Name</th>
                <th style="width: 220px;">Purpose & Scope</th>
                <th>Columns Specification</th>
                <th>Sample Domain Values</th>
                <th>Entity Relationships</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>mst_organization_types</code></td>
                <td>Classifies tenant organizational legal structures and behavioral templates.</td>
                <td><code>id, code, name, description, default_modules_json, status, created_at</code></td>
                <td><code>SOCIAL_MANDAL</code> ("Social Mandal / Samaj"), <code>CHARITABLE_TRUST</code> ("Public Charitable Trust"), <code>CLUB_ASSOCIATION</code> ("Recreational / Sports Club"), <code>HOUSING_SOCIETY</code> ("Co-operative Housing Society"), <code>RELIGIOUS_BODY</code> ("Temple / Religious Trust")</td>
                <td>Referenced by <code>org_organizations.organization_type_id</code> (1:N)</td>
              </tr>
              <tr>
                <td><code>mst_membership_types</code></td>
                <td>Defines legal standing and broad constitutional categories of members.</td>
                <td><code>id, organization_id, code, name, voting_eligible, tenure_type, status</code></td>
                <td><code>PATRON</code> ("Sanrakshak / Patron Member"), <code>LIFE</code> ("Ajeevan Sabhya / Life Member"), <code>ANNUAL</code> ("Ordinary / Annual Member"), <code>HONORARY</code> ("Honorary Dignitary"), <code>STUDENT_YOUTH</code> ("Youth Member")</td>
                <td>Referenced by <code>mbr_plans.membership_type_id</code>, <code>mem_members.membership_type_id</code></td>
              </tr>
              <tr>
                <td><code>mst_membership_categories</code></td>
                <td>Internal organizational demographic grouping for fee concession or special rights.</td>
                <td><code>id, organization_id, code, name, discount_pct, status</code></td>
                <td><code>GENERAL</code> ("General Category"), <code>SENIOR_CITIZEN</code> ("Senior Citizen (60+ yrs)"), <code>WIDOW_DEFENSE</code> ("Veer Nari / Widow"), <code>STUDENT</code> ("Full-time Student")</td>
                <td>Referenced by <code>mbr_plans.category_id</code>, <code>mem_members.category_id</code></td>
              </tr>
              <tr>
                <td><code>mst_member_statuses</code></td>
                <td>Tracks the operational and administrative lifecycle state of a member.</td>
                <td><code>id, code, name, badge_color, allows_login, allows_voting, status</code></td>
                <td><code>PENDING_APPROVAL</code> ("Applied / In Scrutiny"), <code>ACTIVE</code> ("Fully Active & Enrolled"), <code>LAPSED_FEES</code> ("Dues Overdue / Lapsed"), <code>SUSPENDED</code> ("Disciplinary Suspension"), <code>DECEASED</code> ("Demised / Condolence")</td>
                <td>Referenced by <code>mem_members.member_status_id</code></td>
              </tr>
              <tr>
                <td><code>mst_genders</code></td>
                <td>Standard gender definitions for cultural and demographic tracking.</td>
                <td><code>id, code, name, abbreviation, display_order, status</code></td>
                <td><code>MALE</code> ("Male", "M"), <code>FEMALE</code> ("Female", "F"), <code>OTHER</code> ("Non-Binary / Other", "O")</td>
                <td>Referenced by <code>mem_members.gender_id</code></td>
              </tr>
              <tr>
                <td><code>mst_blood_groups</code></td>
                <td>Standard blood group classifications for community emergency donor matching.</td>
                <td><code>id, code, name, rh_factor, status</code></td>
                <td><code>A_POS</code> ("A+"), <code>A_NEG</code> ("A-"), <code>B_POS</code> ("B+"), <code>B_NEG</code> ("B-"), <code>O_POS</code> ("O+"), <code>O_NEG</code> ("O-"), <code>AB_POS</code> ("AB+"), <code>AB_NEG</code> ("AB-")</td>
                <td>Referenced by <code>mem_members.blood_group_id</code></td>
              </tr>
              <tr>
                <td><code>mst_marital_statuses</code></td>
                <td>Marital classifications for family and matrimonial record keeping.</td>
                <td><code>id, code, name, display_order, status</code></td>
                <td><code>SINGLE</code> ("Unmarried / Bachelor"), <code>MARRIED</code> ("Married"), <code>WIDOWED</code> ("Widow / Widower"), <code>DIVORCED</code> ("Divorced / Separated")</td>
                <td>Referenced by <code>mem_members.marital_status_id</code></td>
              </tr>
              <tr>
                <td><code>mst_qualifications</code></td>
                <td>Standardized educational attainment levels for talent directory.</td>
                <td><code>id, organization_id, code, name, education_level, status</code></td>
                <td><code>DOCTORATE</code> ("Ph.D. / Doctorate"), <code>POST_GRAD</code> ("Post Graduate (CA, MBA, MS, M.Tech)"), <code>GRADUATE</code> ("Graduate (B.Com, B.E, B.Sc)"), <code>HIGHER_SEC</code> ("12th / HSC"), <code>SECONDARY</code> ("10th / SSC")</td>
                <td>Referenced by <code>mem_members.qualification_id</code></td>
              </tr>
              <tr>
                <td><code>mst_occupations</code></td>
                <td>Standard professional sectors and vocations for community business networking.</td>
                <td><code>id, organization_id, code, name, sector, status</code></td>
                <td><code>BUSINESS_OWNER</code> ("Business / Industrialist"), <code>CHARTERED_ACCOUNTANT</code> ("Chartered Accountant / Financial Consultant"), <code>MEDICAL_DOCTOR</code> ("Doctor / Healthcare Specialist"), <code>SOFTWARE_IT</code> ("IT / Software Professional"), <code>LEGAL_ADVOCATE</code> ("Advocate / Legal Counsel"), <code>HOMEMAKER</code> ("Homemaker"), <code>RETIRED</code> ("Retired")</td>
                <td>Referenced by <code>mem_members.occupation_id</code></td>
              </tr>
              <tr>
                <td><code>mst_relationships</code></td>
                <td>Rigorous kinship definitions relative to Family Head (Kutumb Pramukh).</td>
                <td><code>id, code, name, reverse_male_rel, reverse_female_rel, generation_delta, status</code></td>
                <td><code>HEAD</code> ("Head of Family", delta=0), <code>SPOUSE</code> ("Husband / Wife", delta=0), <code>FATHER</code> ("Father", delta=+1), <code>MOTHER</code> ("Mother", delta=+1), <code>SON</code> ("Son", delta=-1), <code>DAUGHTER</code> ("Daughter", delta=-1), <code>DAUGHTER_IN_LAW</code> ("Daughter-in-Law", delta=-1), <code>BROTHER</code> ("Brother", delta=0), <code>SISTER</code> ("Sister", delta=0), <code>GRANDSON</code> ("Grandson", delta=-2), <code>GRANDDAUGHTER</code> ("Granddaughter", delta=-2)</td>
                <td>Referenced by <code>fam_family_members.relationship_id</code></td>
              </tr>
              <tr>
                <td><code>mst_committee_types</code></td>
                <td>Categorizes governance committees and functional wings.</td>
                <td><code>id, organization_id, code, name, is_standing_committee, status</code></td>
                <td><code>TRUSTEE_BOARD</code> ("Board of Trustees"), <code>MANAGING_COMMITTEE</code> ("Central Managing Committee / Karyakarini"), <code>MAHILA_MANDAL</code> ("Women Wing"), <code>YUVA_MANDAL</code> ("Youth Wing"), <code>WELFARE_COMMITTEE</code> ("Medical & Education Relief Committee"), <code>ELECTION_BOARD</code> ("Election Scrutiny Board")</td>
                <td>Referenced by <code>cmt_committees.committee_type_id</code></td>
              </tr>
              <tr>
                <td><code>mst_committee_designations</code></td>
                <td>Constitutional office-bearer ranks and titles with seniority hierarchy.</td>
                <td><code>id, organization_id, code, name, seniority_rank, is_signatory, status</code></td>
                <td><code>PRESIDENT</code> ("President / Adhyaksh / Pramukh", rank=1, signatory=true), <code>VICE_PRESIDENT</code> ("Vice President / Upadhyaksh", rank=2), <code>GEN_SECRETARY</code> ("General Secretary / Maha Mantri", rank=3, signatory=true), <code>JOINT_SECRETARY</code> ("Joint Secretary / Sah Mantri", rank=4), <code>TREASURER</code> ("Treasurer / Koshadhyaksh", rank=5, signatory=true), <code>COMMITTEE_MEMBER</code> ("Executive Member / Karyakarini Sadasya", rank=10)</td>
                <td>Referenced by <code>cmt_committee_members.designation_id</code></td>
              </tr>
              <tr>
                <td><code>mst_meeting_types</code></td>
                <td>Classifies committee and general body meeting proceedings.</td>
                <td><code>id, organization_id, code, name, quorum_percentage, notice_days_required, status</code></td>
                <td><code>AGM</code> ("Annual General Meeting", quorum=33%, notice=21 days), <code>EGM</code> ("Extraordinary General Meeting", quorum=25%, notice=14 days), <code>MANAGING_COMMITTEE</code> ("Monthly Managing Committee Meeting", quorum=50%, notice=7 days), <code>TRUSTEE_MEETING</code> ("Board of Trustees Quarterly Review", quorum=60%, notice=5 days)</td>
                <td>Referenced by <code>cmt_meetings.meeting_type_id</code></td>
              </tr>
              <tr>
                <td><code>mst_countries</code></td>
                <td>Global ISO 3166-1 standard country registry.</td>
                <td><code>id, iso2, iso3, name, phone_code, currency_code, status</code></td>
                <td><code>IN</code> ("India", "+91", "INR"), <code>US</code> ("United States", "+1", "USD"), <code>AE</code> ("United Arab Emirates", "+971", "AED"), <code>GB</code> ("United Kingdom", "+44", "GBP")</td>
                <td>Referenced by <code>mst_states.country_id</code>, <code>mem_addresses.country_id</code></td>
              </tr>
              <tr>
                <td><code>mst_states</code></td>
                <td>Provincial and federal state jurisdiction master.</td>
                <td><code>id, country_id, code, name, gst_state_code, status</code></td>
                <td><code>MH</code> ("Maharashtra", "27"), <code>GJ</code> ("Gujarat", "24"), <code>RJ</code> ("Rajasthan", "08"), <code>MP</code> ("Madhya Pradesh", "23"), <code>DL</code> ("Delhi", "07")</td>
                <td>Referenced by <code>mst_districts.state_id</code>, <code>mem_addresses.state_id</code></td>
              </tr>
              <tr>
                <td><code>mst_districts</code></td>
                <td>Revenue district mapping for zonal management.</td>
                <td><code>id, state_id, code, name, status</code></td>
                <td><code>MUM_CITY</code> ("Mumbai City"), <code>MUM_SUB</code> ("Mumbai Suburban"), <code>THANE</code> ("Thane"), <code>PUNE</code> ("Pune"), <code>AHMEDABAD</code> ("Ahmedabad")</td>
                <td>Referenced by <code>mst_cities.district_id</code>, <code>mem_addresses.district_id</code></td>
              </tr>
              <tr>
                <td><code>mst_cities</code></td>
                <td>City and municipal corporation lookups.</td>
                <td><code>id, district_id, name, postal_code_pattern, status</code></td>
                <td><code>MUMBAI</code> ("Mumbai"), <code>THANE</code> ("Thane"), <code>NAVI_MUMBAI</code> ("Navi Mumbai"), <code>PUNE</code> ("Pune"), <code>SURAT</code> ("Surat")</td>
                <td>Referenced by <code>mem_addresses.city_id</code>, <code>org_units.city_id</code></td>
              </tr>
              <tr>
                <td><code>mst_address_types</code></td>
                <td>Categorizes member geographical and physical premises.</td>
                <td><code>id, code, name, display_order, status</code></td>
                <td><code>RESIDENTIAL</code> ("Current Residence"), <code>PERMANENT</code> ("Permanent Ancestral Address"), <code>OFFICE</code> ("Commercial Office / Clinic / Shop"), <code>VATAN</code> ("Native Village / Gam Address")</td>
                <td>Referenced by <code>mem_addresses.address_type_id</code></td>
              </tr>
              <tr>
                <td><code>mst_id_proof_types</code></td>
                <td>Standard identity verification documents.</td>
                <td><code>id, code, name, validation_regex, status</code></td>
                <td><code>AADHAAR</code> ("Aadhaar Card", "^[2-9]{1}[0-9]{3}[0-9]{4}[0-9]{4}$"), <code>PAN</code> ("Permanent Account Number (PAN)", "^[A-Z]{5}[0-9]{4}[A-Z]{1}$"), <code>PASSPORT</code> ("Passport"), <code>VOTER_ID</code> ("Election Voter ID")</td>
                <td>Referenced by <code>mem_documents.id_proof_type_id</code></td>
              </tr>
              <tr>
                <td><code>mst_payment_modes</code></td>
                <td>Methods accepted for fee collection and donations.</td>
                <td><code>id, code, name, requires_bank_ref, is_online, status</code></td>
                <td><code>CASH</code> ("Cash Currency", false, false), <code>CHEQUE</code> ("Bank Cheque", true, false), <code>UPI_QR</code> ("UPI Dynamic QR / VPA", true, true), <code>GATEWAY_CARD</code> ("Credit/Debit Card via Gateway", true, true), <code>NEFT_RTGS</code> ("Bank Wire / IMPS", true, false)</td>
                <td>Referenced by <code>fee_collections.payment_mode_id</code>, <code>don_donations.payment_mode_id</code></td>
              </tr>
              <tr>
                <td><code>mst_payment_statuses</code></td>
                <td>Transaction state indicators for receipts and invoices.</td>
                <td><code>id, code, name, badge_class, status</code></td>
                <td><code>PENDING</code> ("Pending Clearance"), <code>COMPLETED</code> ("Successful & Realized"), <code>FAILED</code> ("Payment Failed"), <code>BOUNCED</code> ("Cheque Dishonored / Bounced"), <code>REFUNDED</code> ("Refunded / Reversal")</td>
                <td>Referenced by <code>fee_collections.payment_status_id</code>, <code>don_donations.payment_status_id</code></td>
              </tr>
              <tr>
                <td><code>mst_donation_categories</code></td>
                <td>Classifies philanthropic donations according to statutory trust bylaws.</td>
                <td><code>id, organization_id, code, name, is_corpus, is_80g_eligible, status</code></td>
                <td><code>CORPUS_BUILDING</code> ("Permanent Building / Bhavan Fund", corpus=true), <code>EDUCATION_SCHOLARSHIP</code> ("Student Education Fund", corpus=false, 80G=true), <code>MEDICAL_RELIEF</code> ("Chikitsa Sahayata / Medical Relief", 80G=true), <code>GENERAL_CHARITY</code> ("Sadavrat / General Welfare", 80G=true), <code>FESTIVAL_POOJA</code> ("Diwali / Mahotsav Sponsorship", 80G=false)</td>
                <td>Referenced by <code>don_donations.category_id</code>, <code>don_campaigns.category_id</code></td>
              </tr>
              <tr>
                <td><code>mst_account_classes</code></td>
                <td>Top-tier fundamental accounting classification according to GAAP.</td>
                <td><code>id, code, name, normal_balance, display_order</code></td>
                <td><code>ASSET</code> ("Assets", "DEBIT"), <code>LIABILITY</code> ("Liabilities", "CREDIT"), <code>EQUITY_CORPUS</code> ("Corpus & Reserves", "CREDIT"), <code>INCOME</code> ("Revenue & Incomes", "CREDIT"), <code>EXPENSE</code> ("Expenses & Outgoings", "DEBIT")</td>
                <td>Referenced by <code>mst_account_groups.account_class_id</code></td>
              </tr>
              <tr>
                <td><code>mst_account_groups</code></td>
                <td>Institutional grouping of general ledger accounts for balance sheet presentation.</td>
                <td><code>id, account_class_id, code, name, parent_group_id, status</code></td>
                <td><code>CORPUS_FUNDS</code> ("Trust Corpus & Earmarked Funds"), <code>CURRENT_LIABILITIES</code> ("Sundry Creditors & Deposits"), <code>FIXED_ASSETS</code> ("Land, Buildings & Community Hall"), <code>BANK_BALANCES</code> ("Bank Accounts & Fixed Deposits"), <code>DIRECT_CHARITY_EXP</code> ("Charitable Welfare Disbursements"), <code>MEMBERSHIP_INCOME</code> ("Annual & Life Membership Fees")</td>
                <td>Referenced by <code>acc_chart_of_accounts.account_group_id</code></td>
              </tr>
              <tr>
                <td><code>mst_voucher_types</code></td>
                <td>Official transaction types for double-entry financial voucher entries.</td>
                <td><code>id, code, name, prefix, allows_bank, allows_cash, status</code></td>
                <td><code>RECEIPT</code> ("Receipt Voucher", "REC-"), <code>PAYMENT</code> ("Payment Voucher", "PAY-"), <code>JOURNAL</code> ("Journal Voucher", "JRN-"), <code>CONTRA</code> ("Contra Voucher (Bank/Cash Transfer)", "CTR-")</td>
                <td>Referenced by <code>acc_vouchers.voucher_type_id</code></td>
              </tr>
              <tr>
                <td><code>mst_event_categories</code></td>
                <td>Classifies community activities, festivals, and governance conventions.</td>
                <td><code>id, organization_id, code, name, default_banner_url, status</code></td>
                <td><code>ANNUAL_AGM</code> ("Annual General Body Meeting"), <code>FESTIVAL_CELEBRATION</code> ("Diwali Sneh Milan / Navratri / Holi"), <code>MEDICAL_CAMP</code> ("Free Health & Blood Donation Camp"), <code>YOUTH_SPORTS</code> ("Cricket Tournament / Sports Meet"), <code>CAREER_SEMINAR</code> ("Student Career Guidance & Award Felicitation")</td>
                <td>Referenced by <code>evt_events.category_id</code></td>
              </tr>
              <tr>
                <td><code>mst_document_types</code></td>
                <td>Taxonomy for digital archive repository and legal vault.</td>
                <td><code>id, organization_id, code, name, requires_expiry, max_size_mb, status</code></td>
                <td><code>TRUST_DEED</code> ("Constitution & Trust Deed"), <code>TAX_12A_80G</code> ("12A / 80G / FCRA Statutory Orders"), <code>AUDIT_REPORT</code> ("Statutory Audited Balance Sheet & Form 10B"), <code>MINUTES_BOOK</code> ("Signed Board Minutes & Tharav"), <code>PROPERTY_TITLE</code> ("Land Title / 7-12 Extract / Conveyance")</td>
                <td>Referenced by <code>doc_documents.document_type_id</code></td>
              </tr>
              <tr>
                <td><code>mst_notification_templates</code></td>
                <td>Standardized message blueprints with dynamic merge tokens for SMS/WhatsApp.</td>
                <td><code>id, organization_id, channel, event_trigger, template_code, body_text, dlt_template_id, status</code></td>
                <td><code>BIRTHDAY_WISH</code> ("Dear {member_name}, Happy Birthday from {org_name}!"), <code>FEE_REMINDER</code> ("Dear {member_name}, your membership renewal fee of Rs. {amount} is due by {due_date}. Pay online: {payment_link}"), <code>80G_RECEIPT_SMS</code> ("Received donation of Rs. {amount} from {donor_name}. Receipt No: {receipt_no}. Thank you.")</td>
                <td>Referenced by <code>com_campaigns.template_id</code>, <code>com_logs.template_id</code></td>
              </tr>
              <tr>
                <td><code>mst_welfare_categories</code></td>
                <td>Classifies charitable assistance schemes for community members in need.</td>
                <td><code>id, organization_id, code, name, max_sanction_limit, requires_income_proof, status</code></td>
                <td><code>HIGHER_EDUCATION</code> ("Higher Education Engineering/Medical Scholarship"), <code>DIALYSIS_CANCER</code> ("Chronic Illness Dialysis & Cancer Aid"), <code>WIDOW_MONTHLY_AID</code> ("Destitute Widow Monthly Sustenance Pension"), <code>MARRIAGE_GRANT</code> ("Underprivileged Girl Marriage Financial Help")</td>
                <td>Referenced by <code>sch_welfare_applications.category_id</code></td>
              </tr>
              <tr>
                <td><code>mst_facility_types</code></td>
                <td>Community real estate assets and venues available for reservation.</td>
                <td><code>id, organization_id, code, name, default_capacity, status</code></td>
                <td><code>COMMUNITY_HALL</code> ("Air-Conditioned Community Hall / Wadi"), <code>DINING_HALL</code> ("Bhojan Shala / Dining Area"), <code>GUEST_ROOM</code> ("AC Guest Room / Dharamsala Room"), <code>CONFERENCE_ROOM</code> ("Committee Boardroom")</td>
                <td>Referenced by <code>fac_facilities.facility_type_id</code></td>
              </tr>
              <tr>
                <td><code>mst_equipment_types</code></td>
                <td>Healthcare and community support equipment loaned to needy members.</td>
                <td><code>id, organization_id, code, name, security_deposit_amt, max_loan_days, status</code></td>
                <td><code>OXYGEN_CONCENTRATOR</code> ("5L/10L Oxygen Concentrator", dep=5000, days=30), <code>HOSPITAL_BED</code> ("Adjustable 2-Function Hospital Bed", dep=3000, days=60), <code>WHEELCHAIR</code> ("Folding Wheelchair", dep=1000, days=90)</td>
                <td>Referenced by <code>fac_equipment.equipment_type_id</code></td>
              </tr>
              <tr>
                <td><code>mst_election_post_types</code></td>
                <td>Constitutional elected officer positions contested during mandal elections.</td>
                <td><code>id, organization_id, code, name, min_membership_years_required, max_age_limit, status</code></td>
                <td><code>PRESIDENT_POST</code> ("President (Pramukh)", min_years=5), <code>SECRETARY_POST</code> ("General Secretary (Maha Mantri)", min_years=3), <code>TREASURER_POST</code> ("Treasurer (Koshadhyaksh)", min_years=3), <code>ZONE_REPRESENTATIVE</code> ("Zonal Managing Committee Member", min_years=1)</td>
                <td>Referenced by <code>elc_election_posts.post_type_id</code></td>
              </tr>
              <tr>
                <td><code>mst_grievance_categories</code></td>
                <td>Ticket categories for member inquiries, feedback, and complaint triage.</td>
                <td><code>id, organization_id, code, name, default_sla_hours, escalation_role_id, status</code></td>
                <td><code>MEMBERSHIP_CARD</code> ("ID Card Correction / Delayed Delivery", sla=48), <code>BILLING_RECEIPT</code> ("Fee Payment Not Reflected / Receipt Query", sla=24), <code>HALL_BOOKING_ISSUE</code> ("Facility Booking Cancellation / Refund", sla=48), <code>GENERAL_GOVERNANCE</code> ("Complaint Regarding Managing Committee Decision", sla=120)</td>
                <td>Referenced by <code>grv_tickets.category_id</code></td>
              </tr>
              <tr>
                <td><code>mst_roles</code></td>
                <td>Standard role archetypes for RBAC authorization hierarchy.</td>
                <td><code>id, code, name, description, is_system_locked, status</code></td>
                <td><code>SUPER_ADMIN</code> ("Platform Super Administrator"), <code>TRUSTEE_CHAIR</code> ("Board of Trustees Chairman"), <code>GENERAL_SECRETARY</code> ("Central General Secretary"), <code>CHIEF_ACCOUNTANT</code> ("Head Accountant / Auditor"), <code>KENDRA_ADMIN</code> ("Zonal / Branch Secretary"), <code>MEMBER</code> ("Ordinary Enrolled Member")</td>
                <td>Referenced by <code>rbac_user_roles.role_id</code></td>
              </tr>
              <tr>
                <td><code>mst_permissions</code></td>
                <td>Atomic capability definitions across all modular endpoints.</td>
                <td><code>id, module_key, action_key, name, description</code></td>
                <td><code>MEMBER_CREATE</code>, <code>MEMBER_READ</code>, <code>MEMBER_UPDATE</code>, <code>MEMBER_DELETE</code>, <code>MEMBER_EXPORT</code>, <code>VOUCHER_CREATE</code>, <code>VOUCHER_APPROVE</code>, <code>VOUCHER_POST</code>, <code>80G_GENERATE</code>, <code>MEETING_CONDUCT</code>, <code>ELECTION_VOTE</code></td>
                <td>Referenced by <code>rbac_role_permissions.permission_id</code></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    `;
  }
};

