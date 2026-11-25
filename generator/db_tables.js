// generator/db_tables.js
const tablesData = [
  {
    name: "sys_tenants",
    purpose: "Root SaaS tenant client registry managing independent organizations, subscriptions, and database partition keys.",
    relationships: "1:N with org_organizations; 1:N with rbac_users",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_tenant_code (code); INDEX idx_tenant_status (status)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Global synthetic surrogate primary key." },
      { name: "code", type: "VARCHAR(50)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Unique tenant slug identifier (e.g. 'mmp_mumbai', 'sns_trust')." },
      { name: "name", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Full official legal name of the subscriber entity." },
      { name: "subdomain", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Subdomain for SaaS routing (e.g. 'mmp.umcms.com')." },
      { name: "custom_domain", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "YES", desc: "Whitelabel custom CNAME domain (e.g. 'portal.mmpmumbai.org')." },
      { name: "subscription_plan", type: "VARCHAR(50)", pk: "NO", fk: "-", null: "NO", def: "'ENTERPRISE'", uq: "NO", desc: "SaaS licensing tier: STANDARD, PRO, ENTERPRISE." },
      { name: "max_members_limit", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "10000", uq: "NO", desc: "Licensing quota cap for enrolled members." },
      { name: "is_active", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "1", uq: "NO", desc: "Global tenant enablement toggle." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Audit timestamp of tenant creation." },
      { name: "updated_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP ON UPDATE", uq: "NO", desc: "Audit timestamp of record update." }
    ],
    sampleData: [
      { id: 1, code: "MMP_MUMBAI", name: "Shree Maheshwari Mandal Mumbai", subdomain: "mmp", custom_domain: "portal.mmpmumbai.org", plan: "ENTERPRISE", max: 50000, active: 1 },
      { id: 2, code: "SNS_MUMBAI", name: "Shree Navnat Stree Mandal", subdomain: "sns", custom_domain: "portal.snsmumbai.org", plan: "ENTERPRISE", max: 25000, active: 1 }
    ]
  },
  {
    name: "org_organizations",
    purpose: "Primary institutional identity containing legal registrations, PAN, 80G certificates, branding colors, and fiscal settings.",
    relationships: "M:1 with sys_tenants.id; 1:N with org_units.organization_id; 1:N with mem_members.organization_id; 1:N with acc_chart_of_accounts.organization_id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_org_tenant_code (tenant_id, code); INDEX idx_org_type (organization_type_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Primary key identifier." },
      { name: "tenant_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "sys_tenants.id", null: "NO", def: "-", uq: "NO", desc: "Tenant isolation reference." },
      { name: "code", type: "VARCHAR(50)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Organization code (e.g. 'MMP_CENTRAL')." },
      { name: "name", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Complete official legal registered name." },
      { name: "organization_type_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_organization_types.id", null: "NO", def: "-", uq: "NO", desc: "Typology (Social Mandal, Trust, NGO, Society)." },
      { name: "trust_reg_no", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Bombay Public Trusts Act or Societies Act registration number." },
      { name: "pan_number", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Permanent Account Number for tax identity." },
      { name: "tax_80g_reg_no", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Income Tax 80G exemption approval order number." },
      { name: "logo_url", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Public CDN URL for brand vector emblem." },
      { name: "stamp_signature_url", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Authorized signatory stamp and signature image." },
      { name: "primary_color", type: "VARCHAR(10)", pk: "NO", fk: "-", null: "NO", def: "'#1E40AF'", uq: "NO", desc: "Primary branding theme hex code." },
      { name: "financial_year_start_month", type: "TINYINT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "4", uq: "NO", desc: "Starting month of fiscal calendar (4 = April)." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Audit creation date." },
      { name: "updated_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP ON UPDATE", uq: "NO", desc: "Audit update date." },
      { name: "deleted_at", type: "DATETIME", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Soft-delete timestamp." },
      { name: "status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'ACTIVE'", uq: "NO", desc: "Lifecycle state: ACTIVE, INACTIVE, ARCHIVED." }
    ],
    sampleData: [
      { id: 101, tenant_id: 1, code: "MMP_HO", name: "Shree Maheshwari Mandal Mumbai Central", type: "SOCIAL_MANDAL", reg_no: "E-1845(BOM)", pan: "AAATM0123M", "tax_80g": "CIT(E)/80G/14-15/M-451", color: "#831843", fy_month: 4, status: "ACTIVE" }
    ]
  },
  {
    name: "org_units",
    purpose: "Recursive jurisdictional hierarchy representing administrative tiers: National &rarr; State &rarr; Zonal Vibhag &rarr; City Kendra &rarr; Local Ward.",
    relationships: "M:1 with org_organizations.id; M:1 with org_units.id (parent_unit_id); 1:N with mem_members.unit_id",
    indexes: "PRIMARY KEY (id); INDEX idx_unit_org (organization_id); INDEX idx_unit_parent (parent_unit_id); INDEX idx_unit_code (code)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Unit primary key." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant organization scoping." },
      { name: "parent_unit_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_units.id", null: "YES", def: "NULL", uq: "NO", desc: "Parent branch/zone in recursive hierarchy." },
      { name: "unit_level", type: "TINYINT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "1", uq: "NO", desc: "Depth rank (1=Apex, 2=State, 3=Zone/Vibhag, 4=Kendra, 5=Ward)." },
      { name: "code", type: "VARCHAR(50)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Unit code (e.g. 'WZ-ANDHERI')." },
      { name: "name", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Official title (e.g. 'Western Suburbs Vibhag - Andheri Kendra')." },
      { name: "city_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_cities.id", null: "YES", def: "NULL", uq: "NO", desc: "City jurisdiction." },
      { name: "address_line", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Physical Kendra/Chapter office premises." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Audit timestamp." },
      { name: "created_by", type: "BIGINT UNSIGNED", pk: "NO", fk: "rbac_users.id", null: "YES", def: "NULL", uq: "NO", desc: "Creator user ID." },
      { name: "updated_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP ON UPDATE", uq: "NO", desc: "Update timestamp." },
      { name: "updated_by", type: "BIGINT UNSIGNED", pk: "NO", fk: "rbac_users.id", null: "YES", def: "NULL", uq: "NO", desc: "Updater user ID." },
      { name: "deleted_at", type: "DATETIME", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Soft-delete stamp." },
      { name: "status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'ACTIVE'", uq: "NO", desc: "Status: ACTIVE, INACTIVE, MERGED." }
    ],
    sampleData: [
      { id: 1, org_id: 101, parent: null, level: 1, code: "MMP_CENTRAL", name: "Central Head Office (Fort)", city: 1, status: "ACTIVE" },
      { id: 2, org_id: 101, parent: 1, level: 3, code: "MMP_WEST_ZONE", name: "Western Suburbs Vibhag", city: 1, status: "ACTIVE" },
      { id: 3, org_id: 101, parent: 2, level: 4, code: "MMP_ANDHERI_KND", name: "Andheri-Vile Parle Kendra", city: 1, status: "ACTIVE" }
    ]
  },
  {
    name: "mem_members",
    purpose: "Central individual member registry storing demographics, native origin, gotra, cultural lineage, photo, and life status.",
    relationships: "M:1 with org_organizations.id; M:1 with org_units.id; 1:N with mem_addresses; 1:N with mem_contacts; 1:N with fam_family_members; 1:N with mbr_subscriptions",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_mem_org_no (organization_id, membership_no); INDEX idx_mem_search (organization_id, last_name, first_name); INDEX idx_mem_native (native_place_vatan); INDEX idx_mem_blood (blood_group_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Unique member system ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping key." },
      { name: "unit_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_units.id", null: "NO", def: "-", uq: "NO", desc: "Assigned local Kendra / Chapter." },
      { name: "membership_no", type: "VARCHAR(50)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Unique organizational membership serial number." },
      { name: "title", type: "VARCHAR(15)", pk: "NO", fk: "-", null: "YES", def: "'Mr.'", uq: "NO", desc: "Honorific: Shri, Smt, Dr, CA, Adv, Er, Master, Miss." },
      { name: "first_name", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "First name." },
      { name: "middle_name", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Father or Husband middle name." },
      { name: "last_name", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Surname / Family title." },
      { name: "native_place_vatan", type: "VARCHAR(150)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Native town / ancestral origin (Vatan / Gam / Gaon)." },
      { name: "native_district_state", type: "VARCHAR(150)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Native district & state (e.g. 'Didwana, Nagaur, Rajasthan')." },
      { name: "gotra", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Ancestral Gotra / Shakha / Kul." },
      { name: "gender_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_genders.id", null: "NO", def: "-", uq: "NO", desc: "Gender reference." },
      { name: "blood_group_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_blood_groups.id", null: "YES", def: "NULL", uq: "NO", desc: "Blood group reference for emergency matching." },
      { name: "date_of_birth", type: "DATE", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Birth date." },
      { name: "marital_status_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_marital_statuses.id", null: "YES", def: "NULL", uq: "NO", desc: "Marital state." },
      { name: "marriage_anniversary_date", type: "DATE", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Wedding anniversary for automated greetings." },
      { name: "qualification_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_qualifications.id", null: "YES", def: "NULL", uq: "NO", desc: "Highest degree lookup." },
      { name: "occupation_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_occupations.id", null: "YES", def: "NULL", uq: "NO", desc: "Primary vocation lookup." },
      { name: "company_firm_name", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Business / Employer name." },
      { name: "membership_type_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_membership_types.id", null: "NO", def: "-", uq: "NO", desc: "Patron, Life, Annual member tier." },
      { name: "member_status_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_member_statuses.id", null: "NO", def: "-", uq: "NO", desc: "Active, Lapsed, Suspended, Deceased." },
      { name: "photo_url", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "High-resolution portrait photo URL." },
      { name: "joining_date", type: "DATE", pk: "NO", fk: "-", null: "NO", def: "CURRENT_DATE", uq: "NO", desc: "Official enrollment date." },
      { name: "expiry_date", type: "DATE", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Renewal due date (NULL for Life Members)." },
      { name: "death_date", type: "DATE", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Date of demise (triggers condolence feed & archive)." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Audit timestamp." },
      { name: "created_by", type: "BIGINT UNSIGNED", pk: "NO", fk: "rbac_users.id", null: "YES", def: "NULL", uq: "NO", desc: "Audit user creator." },
      { name: "updated_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP ON UPDATE", uq: "NO", desc: "Audit update timestamp." },
      { name: "updated_by", type: "BIGINT UNSIGNED", pk: "NO", fk: "rbac_users.id", null: "YES", def: "NULL", uq: "NO", desc: "Audit user updater." },
      { name: "deleted_at", type: "DATETIME", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Soft-delete timestamp." },
      { name: "status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'ACTIVE'", uq: "NO", desc: "ACTIVE, INACTIVE, DELETED." }
    ],
    sampleData: [
      { id: 1001, org_id: 101, unit_id: 3, mem_no: "MMP-LIFE-2041", title: "Shri", first: "Ramesh", middle: "Gopaldas", last: "Somani", native: "Didwana", gotra: "Somani (Jajoo)", gender: 1, blood: 5, dob: "1972-08-14", status_id: 2, joined: "2015-04-10" }
    ]
  },
  {
    name: "mem_addresses",
    purpose: "Stores multiple physical locations per member: Residential, Permanent, Business Office, and Native Village.",
    relationships: "M:1 with mem_members.id; M:1 with mst_address_types.id; M:1 with mst_cities.id",
    indexes: "PRIMARY KEY (id); INDEX idx_addr_member (member_id); INDEX idx_addr_pincode (postal_code)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Address primary key." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "NO", def: "-", uq: "NO", desc: "Owning member." },
      { name: "address_type_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_address_types.id", null: "NO", def: "-", uq: "NO", desc: "Residential, Permanent, Office, Native." },
      { name: "is_primary_communication", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Primary mailing flag." },
      { name: "flat_wing_building", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Flat no, wing, building / apartment name." },
      { name: "street_road_landmark", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Street name and proximate landmark." },
      { name: "locality_area", type: "VARCHAR(150)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Area / Suburb (e.g. 'Juhu Lane, Andheri West')." },
      { name: "city_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_cities.id", null: "NO", def: "-", uq: "NO", desc: "City lookup." },
      { name: "district_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_districts.id", null: "NO", def: "-", uq: "NO", desc: "District lookup." },
      { name: "state_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_states.id", null: "NO", def: "-", uq: "NO", desc: "State lookup." },
      { name: "postal_code", type: "VARCHAR(15)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "PIN code / Postal code." },
      { name: "latitude", type: "DECIMAL(10,8)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Geographic coordinate latitude." },
      { name: "longitude", type: "DECIMAL(11,8)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Geographic coordinate longitude." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation date." },
      { name: "status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'ACTIVE'", uq: "NO", desc: "ACTIVE, INACTIVE." }
    ],
    sampleData: [
      { id: 501, org_id: 101, member_id: 1001, type: 1, primary: 1, flat: "B-402, Shanti Heights", street: "Swami Samarth Nagar, Cross Road 3", locality: "Lokhandwala, Andheri West", city: 1, pin: "400053" }
    ]
  },
  {
    name: "fam_families",
    purpose: "Kutumb household master grouping families, ancestral origin lineage, and family head designation.",
    relationships: "M:1 with org_organizations.id; M:1 with mem_members.id (head_member_id); 1:N with fam_family_members.family_id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_fam_code (organization_id, family_code); INDEX idx_fam_head (head_member_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Family surrogate primary key." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "family_code", type: "VARCHAR(50)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Unique Kutumb code (e.g. 'FAM-MUM-0814')." },
      { name: "family_name", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Household surname title (e.g. 'Ramesh Gopaldas Somani Parivar')." },
      { name: "head_member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "NO", def: "-", uq: "NO", desc: "Designated Head of Family (Kutumb Pramukh)." },
      { name: "ancestral_native_place", type: "VARCHAR(150)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Ancestral home town." },
      { name: "kuldevta_kuldevi", type: "VARCHAR(150)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Family presiding deity." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation timestamp." },
      { name: "status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'ACTIVE'", uq: "NO", desc: "ACTIVE, SPLIT, MERGED." }
    ],
    sampleData: [
      { id: 201, org_id: 101, code: "FAM-MUM-0814", name: "Ramesh Somani Parivar", head_id: 1001, native: "Didwana", kuldevta: "Sundha Mataji", status: "ACTIVE" }
    ]
  },
  {
    name: "fam_family_members",
    purpose: "Maps individual members into households and defines specific kinship relative to the Head of Family.",
    relationships: "M:1 with fam_families.id; M:1 with mem_members.id; M:1 with mst_relationships.id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_fam_member (family_id, member_id); INDEX idx_fam_rel (relationship_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Kinship mapping ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "family_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "fam_families.id", null: "NO", def: "-", uq: "NO", desc: "Family household reference." },
      { name: "member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "NO", def: "-", uq: "NO", desc: "Individual member reference." },
      { name: "relationship_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_relationships.id", null: "NO", def: "-", uq: "NO", desc: "Relationship to Family Head (Self, Wife, Son, Mother, etc.)." },
      { name: "is_dependent", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Financial dependent flag." },
      { name: "living_together", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "1", uq: "NO", desc: "Co-residence indicator." },
      { name: "sequence_order", type: "SMALLINT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "1", uq: "NO", desc: "Display order in family directory." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation timestamp." }
    ],
    sampleData: [
      { id: 701, org_id: 101, family_id: 201, member_id: 1001, rel_id: 1, is_dep: 0, living: 1, order: 1 },
      { id: 702, org_id: 101, family_id: 201, member_id: 1002, rel_id: 2, is_dep: 1, living: 1, order: 2 },
      { id: 703, org_id: 101, family_id: 201, member_id: 1003, rel_id: 5, is_dep: 1, living: 1, order: 3 }
    ]
  },
  {
    name: "mbr_plans",
    purpose: "Configuration of membership tiers, pricing cycles, entrance fees, and grace period rules.",
    relationships: "M:1 with org_organizations.id; M:1 with mst_membership_types.id; 1:N with mbr_subscriptions.plan_id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_plan_code (organization_id, code)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Plan ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "membership_type_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_membership_types.id", null: "NO", def: "-", uq: "NO", desc: "Type reference (Patron, Life, Annual)." },
      { name: "code", type: "VARCHAR(50)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Plan identifier code." },
      { name: "name", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Plan title." },
      { name: "tenure_months", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "12", uq: "NO", desc: "Duration in months (0 for Lifetime)." },
      { name: "admission_fee", type: "DECIMAL(12,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "One-time entrance fee." },
      { name: "subscription_fee", type: "DECIMAL(12,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Recurring periodic fee." },
      { name: "grace_period_days", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "30", uq: "NO", desc: "Grace days before status turns Lapsed." },
      { name: "is_active", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "1", uq: "NO", desc: "Active toggle." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Audit date." }
    ],
    sampleData: [
      { id: 11, org_id: 101, type: 2, code: "LIFE_STANDARD", name: "Life Membership", months: 0, admission: 1000.00, fee: 15000.00, grace: 0, active: 1 },
      { id: 12, org_id: 101, type: 3, code: "ANNUAL_GENERAL", name: "Annual Ordinary Membership", months: 12, admission: 500.00, fee: 1200.00, grace: 45, active: 1 }
    ]
  },
  {
    name: "mbr_subscriptions",
    purpose: "Tracks individual member subscription lifecycles, active periods, renewals, and expiry dates.",
    relationships: "M:1 with mem_members.id; M:1 with mbr_plans.id",
    indexes: "PRIMARY KEY (id); INDEX idx_sub_member (member_id); INDEX idx_sub_dates (start_date, end_date)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Subscription record ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "NO", def: "-", uq: "NO", desc: "Subscribed member." },
      { name: "plan_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mbr_plans.id", null: "NO", def: "-", uq: "NO", desc: "Active membership plan." },
      { name: "start_date", type: "DATE", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Subscription inception date." },
      { name: "end_date", type: "DATE", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Expiration date (NULL for Lifetime)." },
      { name: "is_lifetime", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Perpetual lifetime status indicator." },
      { name: "renewal_count", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Count of historic renewals." },
      { name: "status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'ACTIVE'", uq: "NO", desc: "ACTIVE, EXPIRED, GRACE, CANCELLED." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation stamp." }
    ],
    sampleData: [
      { id: 801, org_id: 101, member_id: 1001, plan_id: 11, start_date: "2015-04-10", end_date: null, lifetime: 1, renewals: 0, status: "ACTIVE" }
    ]
  },
  {
    name: "cmt_committees",
    purpose: "Governing boards, managing committees (Karyakarini), wings (Mahila/Yuva), and their defined constitutional tenures.",
    relationships: "M:1 with org_organizations.id; M:1 with mst_committee_types.id; 1:N with cmt_committee_members.committee_id; 1:N with cmt_meetings.committee_id",
    indexes: "PRIMARY KEY (id); INDEX idx_cmt_tenure (organization_id, tenure_start_date, tenure_end_date)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Committee ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "unit_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_units.id", null: "YES", def: "NULL", uq: "NO", desc: "Branch/Zone jurisdiction (NULL if Apex Central)." },
      { name: "committee_type_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_committee_types.id", null: "NO", def: "-", uq: "NO", desc: "Trustee Board, Managing Committee, Yuva, Mahila." },
      { name: "name", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Official title (e.g. 'Central Managing Committee (Term 2024–2027)')." },
      { name: "tenure_start_date", type: "DATE", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Term commencement date." },
      { name: "tenure_end_date", type: "DATE", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Term conclusion date." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Audit date." },
      { name: "status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'ACTIVE'", uq: "NO", desc: "ACTIVE, EXPIRED, DISSOLVED." }
    ],
    sampleData: [
      { id: 51, org_id: 101, unit_id: null, type_id: 2, name: "MMP Central Managing Committee (2024-2027)", start: "2024-04-01", end: "2027-03-31", status: "ACTIVE" }
    ]
  },
  {
    name: "cmt_committee_members",
    purpose: "Binds individual members to designated executive office positions (President, Secretary, Treasurer) during a committee tenure.",
    relationships: "M:1 with cmt_committees.id; M:1 with mem_members.id; M:1 with mst_committee_designations.id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_cmt_mbr_pos (committee_id, member_id, designation_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Appointment record ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "committee_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "cmt_committees.id", null: "NO", def: "-", uq: "NO", desc: "Parent committee tenure." },
      { name: "member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "NO", def: "-", uq: "NO", desc: "Appointed member." },
      { name: "designation_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_committee_designations.id", null: "NO", def: "-", uq: "NO", desc: "Constitutional designation." },
      { name: "appointment_order_no", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Official appointment gazette / resolution number." },
      { name: "start_date", type: "DATE", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Assumption of office date." },
      { name: "end_date", type: "DATE", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Relinquishment date." },
      { name: "is_active", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "1", uq: "NO", desc: "Active toggle." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation stamp." }
    ],
    sampleData: [
      { id: 901, org_id: 101, cmt_id: 51, member_id: 1001, desig_id: 1, order: "RES-AGM-2024-04", start: "2024-04-01", active: 1 }
    ]
  },
  {
    name: "cmt_meetings",
    purpose: "Scheduling, formal notices, venues, video links, and proceedings of trustee, managing committee, and general body meetings.",
    relationships: "M:1 with cmt_committees.id; M:1 with mst_meeting_types.id; 1:N with cmt_meeting_agendas; 1:N with cmt_meeting_attendances; 1:N with cmt_resolutions",
    indexes: "PRIMARY KEY (id); INDEX idx_meeting_date (meeting_date); INDEX idx_meeting_cmt (committee_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Meeting ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "committee_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "cmt_committees.id", null: "NO", def: "-", uq: "NO", desc: "Convening committee." },
      { name: "meeting_type_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_meeting_types.id", null: "NO", def: "-", uq: "NO", desc: "AGM, Managing Committee, Trustee." },
      { name: "notice_number", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Official notice circular reference number." },
      { name: "title", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Meeting subject title." },
      { name: "meeting_date", type: "DATE", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Scheduled date." },
      { name: "start_time", type: "TIME", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Convening time." },
      { name: "venue_address", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Physical boardroom or auditorium venue." },
      { name: "video_conference_url", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Zoom / Google Meet hybrid participation link." },
      { name: "quorum_required_count", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "10", uq: "NO", desc: "Minimum members required for legal validity." },
      { name: "minutes_text", type: "LONGTEXT", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Recorded proceedings & minutes." },
      { name: "signed_minutes_doc_url", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Scanned signed minutes PDF link." },
      { name: "status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'SCHEDULED'", uq: "NO", desc: "SCHEDULED, IN_PROGRESS, CONCLUDED, ADJOURNED." }
    ],
    sampleData: [
      { id: 301, org_id: 101, cmt_id: 51, type_id: 3, notice: "NOT-2026-MC-02", title: "Monthly Managing Committee Review", date: "2026-03-10", time: "18:30:00", venue: "Mandal Boardroom, Fort, Mumbai", status: "CONCLUDED" }
    ]
  },
  {
    name: "cmt_resolutions",
    purpose: "Formal recording of passed constitutional decisions (Tharav), voting counts, proposers, and seconders.",
    relationships: "M:1 with cmt_meetings.id; M:1 with mem_members.id (proposer_id); M:1 with mem_members.id (seconder_id)",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_res_no (organization_id, resolution_number); INDEX idx_res_meeting (meeting_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Resolution ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "meeting_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "cmt_meetings.id", null: "NO", def: "-", uq: "NO", desc: "Deliberating meeting." },
      { name: "resolution_number", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Official resolution number (e.g. 'RES-2026-03-08')." },
      { name: "agenda_number", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "1", uq: "NO", desc: "Corresponding agenda item." },
      { name: "proposer_member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "NO", def: "-", uq: "NO", desc: "Proposing executive." },
      { name: "seconder_member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "NO", def: "-", uq: "NO", desc: "Seconding executive." },
      { name: "resolution_text", type: "TEXT", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Full official legal text of the resolution." },
      { name: "votes_in_favor", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Votes in favor." },
      { name: "votes_against", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Votes dissenting." },
      { name: "votes_abstained", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Abstaining votes." },
      { name: "outcome", type: "VARCHAR(30)", pk: "NO", fk: "-", null: "NO", def: "'PASSED_UNANIMOUSLY'", uq: "NO", desc: "PASSED_UNANIMOUSLY, PASSED_MAJORITY, REJECTED, DEFERRED." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation stamp." }
    ],
    sampleData: [
      { id: 101, org_id: 101, meeting_id: 301, res_no: "RES-2026-03-08", agenda_no: 2, prop: 1001, sec: 1004, text: "Resolved to sanction Rs. 50,00,000 from Bhavan Fund for modernization of Community Hall AC plant.", favor: 18, against: 0, outcome: "PASSED_UNANIMOUSLY" }
    ]
  },
  {
    name: "fee_demands",
    purpose: "Invoiced accounts receivable dues billed against individual members for membership subscriptions and maintenance levies.",
    relationships: "M:1 with mem_members.id; M:1 with acc_financial_years.id; 1:N with fee_collection_items.demand_id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_demand_invoice (organization_id, invoice_number); INDEX idx_demand_mem (member_id); INDEX idx_demand_status (payment_status)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Demand invoice ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "NO", def: "-", uq: "NO", desc: "Billed member." },
      { name: "financial_year_id", type: "INT UNSIGNED", pk: "NO", fk: "acc_financial_years.id", null: "NO", def: "-", uq: "NO", desc: "Fiscal year billed." },
      { name: "invoice_number", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Unique demand invoice reference." },
      { name: "invoice_date", type: "DATE", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Billing date." },
      { name: "due_date", type: "DATE", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Payment deadline." },
      { name: "billed_amount", type: "DECIMAL(12,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Gross fee charged." },
      { name: "waiver_discount_amount", type: "DECIMAL(12,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Concession / hardship waiver." },
      { name: "late_fee_surcharge", type: "DECIMAL(12,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Penalty for overdue payment." },
      { name: "net_payable_amount", type: "DECIMAL(12,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Final amount receivable." },
      { name: "paid_amount", type: "DECIMAL(12,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Cumulative amount paid." },
      { name: "balance_due", type: "DECIMAL(12,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Outstanding balance." },
      { name: "payment_status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'UNPAID'", uq: "NO", desc: "UNPAID, PARTIALLY_PAID, PAID, WAIVED." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation stamp." }
    ],
    sampleData: [
      { id: 401, org_id: 101, member_id: 1005, fy: 2026, inv: "INV-2026-00912", inv_date: "2026-04-01", due: "2026-05-15", billed: 1200.00, paid: 0.00, bal: 1200.00, status: "UNPAID" }
    ]
  },
  {
    name: "fee_collections",
    purpose: "Financial receipt transactions recording member dues collections, payment modes, and payment gateway callbacks.",
    relationships: "M:1 with mem_members.id; M:1 with mst_payment_modes.id; 1:N with fee_collection_items.collection_id; 1:1 with acc_vouchers.id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_fee_receipt (organization_id, receipt_number); INDEX idx_fee_date (collection_date)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Collection record ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "receipt_number", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Printed receipt serial number." },
      { name: "member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "NO", def: "-", uq: "NO", desc: "Paying member." },
      { name: "collection_date", type: "DATE", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Transaction date." },
      { name: "amount_paid", type: "DECIMAL(12,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Total currency amount collected." },
      { name: "payment_mode_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_payment_modes.id", null: "NO", def: "-", uq: "NO", desc: "Cash, Cheque, UPI, Card, NEFT." },
      { name: "transaction_reference", type: "VARCHAR(150)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Cheque No / UPI UTR / Bank Ref." },
      { name: "bank_name", type: "VARCHAR(150)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Issuing bank name for cheques." },
      { name: "cashier_user_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "rbac_users.id", null: "NO", def: "-", uq: "NO", desc: "Collecting cashier / counter staff." },
      { name: "accounting_voucher_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "acc_vouchers.id", null: "YES", def: "NULL", uq: "NO", desc: "Linked double-entry Receipt Voucher." },
      { name: "payment_status_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_payment_statuses.id", null: "NO", def: "-", uq: "NO", desc: "Completed, Cleared, Bounced." },
      { name: "receipt_pdf_url", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Direct download link for official receipt PDF." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation stamp." }
    ],
    sampleData: [
      { id: 601, org_id: 101, rcpt_no: "REC-2026-00412", member_id: 1005, date: "2026-04-12", amt: 1200.00, mode: 3, utr: "UPI/3941209384", cashier: 5, status: 2 }
    ]
  },
  {
    name: "don_donors",
    purpose: "Permanent master directory of individual, family, trust, and corporate benefactors with mandatory tax PAN details.",
    relationships: "M:1 with org_organizations.id; 1:N with don_donations.donor_id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_donor_pan (organization_id, pan_number); INDEX idx_donor_name (donor_name)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Donor ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "YES", def: "NULL", uq: "NO", desc: "Linked member (if existing community member)." },
      { name: "donor_type", type: "VARCHAR(30)", pk: "NO", fk: "-", null: "NO", def: "'INDIVIDUAL'", uq: "NO", desc: "INDIVIDUAL, FAMILY_TRUST, CORPORATE_CSR, NON_MEMBER." },
      { name: "donor_name", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Full official donor name as per PAN." },
      { name: "pan_number", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Permanent Account Number for 80G validity." },
      { name: "mobile_number", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "WhatsApp / Contact number." },
      { name: "email_address", type: "VARCHAR(150)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Email for electronic 80G certificates." },
      { name: "postal_address", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Full postal address required for statutory filing." },
      { name: "is_anonymous", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Hide identity from public honour board." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation stamp." }
    ],
    sampleData: [
      { id: 151, org_id: 101, mem_id: 1001, type: "INDIVIDUAL", name: "Ramesh Gopaldas Somani", pan: "AAAPS9812M", mobile: "+919820199881", address: "B-402 Shanti Heights, Lokhandwala, Andheri West, Mumbai 400053", anon: 0 }
    ]
  },
  {
    name: "don_donations",
    purpose: "Charitable donation receipts, 80G tax exemption numbers, dedicated in-memory memorials (Smruti Bhet), and earmarked fund tracking.",
    relationships: "M:1 with don_donors.id; M:1 with mst_donation_categories.id; M:1 with acc_vouchers.id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_don_rcpt (organization_id, receipt_number); UNIQUE KEY uq_80g_cert (tax_80g_certificate_number); INDEX idx_don_date (donation_date)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Donation ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "receipt_number", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Official donation receipt serial." },
      { name: "tax_80g_certificate_number", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "YES", desc: "Statutory Form 10BE / 80G certificate serial." },
      { name: "donor_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "don_donors.id", null: "NO", def: "-", uq: "NO", desc: "Benefactor reference." },
      { name: "category_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_donation_categories.id", null: "NO", def: "-", uq: "NO", desc: "Corpus, Building, Education, Medical, General." },
      { name: "amount", type: "DECIMAL(14,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Financial contribution amount." },
      { name: "donation_date", type: "DATE", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Realization date." },
      { name: "in_memory_of_name", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Dedicated in loving memory of (Late...)." },
      { name: "in_celebration_of_event", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Dedicated on occasion of (50th Anniversary / Graduation)." },
      { name: "payment_mode_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_payment_modes.id", null: "NO", def: "-", uq: "NO", desc: "Payment mode." },
      { name: "transaction_ref", type: "VARCHAR(150)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Cheque / NEFT / UTR number." },
      { name: "accounting_voucher_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "acc_vouchers.id", null: "YES", def: "NULL", uq: "NO", desc: "Linked double-entry voucher." },
      { name: "form_10bd_filed", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Status of annual e-filing with Income Tax Dept." },
      { name: "certificate_pdf_url", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Direct download link for 80G PDF certificate." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation stamp." }
    ],
    sampleData: [
      { id: 701, org_id: 101, rcpt_no: "DON-2026-00108", cert_80g: "80G-MMP-2026-0108", donor_id: 151, cat_id: 2, amt: 25000.00, date: "2026-04-18", in_memory: "Late Gopaldasji Somani", mode: 4, ref: "NEFT/HDFC98129841", "form_10bd": 0 }
    ]
  },
  {
    name: "acc_chart_of_accounts",
    purpose: "Rigorous hierarchical general ledger chart of accounts compliant with trust accounting standards (Assets, Liabilities, Corpus, Incomes, Expenses).",
    relationships: "M:1 with org_organizations.id; M:1 with mst_account_groups.id; 1:N with acc_voucher_details.account_id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_acc_code (organization_id, account_code); INDEX idx_acc_group (account_group_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Account ledger ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "account_group_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_account_groups.id", null: "NO", def: "-", uq: "NO", desc: "Group in Balance Sheet hierarchy." },
      { name: "account_code", type: "VARCHAR(50)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Standard accounting ledger code (e.g. '1110-HDFC-MAIN')." },
      { name: "account_name", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Ledger account name." },
      { name: "normal_balance", type: "ENUM('DEBIT','CREDIT')", pk: "NO", fk: "-", null: "NO", def: "'DEBIT'", uq: "NO", desc: "Standard balance side." },
      { name: "is_bank_account", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Bank ledger (requires BRS reconciliation)." },
      { name: "is_cash_account", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Petty cash / drawer account." },
      { name: "bank_account_number", type: "VARCHAR(50)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Bank account number for auto-reconciliation." },
      { name: "bank_ifsc_code", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Bank IFSC." },
      { name: "opening_balance", type: "DECIMAL(14,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Opening balance." },
      { name: "opening_balance_type", type: "ENUM('DEBIT','CREDIT')", pk: "NO", fk: "-", null: "NO", def: "'DEBIT'", uq: "NO", desc: "Dr / Cr opening position." },
      { name: "is_reconciliation_active", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "1", uq: "NO", desc: "Active ledger indicator." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation stamp." }
    ],
    sampleData: [
      { id: 21, org_id: 101, grp_id: 4, code: "1110-HDFC-MAIN", name: "HDFC Bank A/c No. 50100012345678", normal: "DEBIT", is_bank: 1, op_bal: 1450200.00, op_type: "DEBIT" },
      { id: 41, org_id: 101, grp_id: 1, code: "2010-CORPUS-BHAVAN", name: "Building & Bhavan Corpus Fund", normal: "CREDIT", is_bank: 0, op_bal: 85000000.00, op_type: "CREDIT" },
      { id: 61, org_id: 101, grp_id: 6, code: "3010-MEM-ANNUAL-INC", name: "Annual Membership Fees Received", normal: "CREDIT", is_bank: 0, op_bal: 0.00, op_type: "CREDIT" }
    ]
  },
  {
    name: "acc_vouchers",
    purpose: "Central header for all double-entry financial transactions: Receipts, Payments, Journals, and Contras.",
    relationships: "M:1 with org_organizations.id; M:1 with mst_voucher_types.id; M:1 with acc_financial_years.id; 1:N with acc_voucher_details.voucher_id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_vch_no (organization_id, voucher_number); INDEX idx_vch_date (voucher_date); INDEX idx_vch_status (status)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Voucher ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "voucher_number", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Sequential audit voucher serial (e.g. 'PAY-2026-00451')." },
      { name: "voucher_type_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_voucher_types.id", null: "NO", def: "-", uq: "NO", desc: "Receipt, Payment, Journal, Contra." },
      { name: "financial_year_id", type: "INT UNSIGNED", pk: "NO", fk: "acc_financial_years.id", null: "NO", def: "-", uq: "NO", desc: "Fiscal year reference." },
      { name: "voucher_date", type: "DATE", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Accounting entry date." },
      { name: "narration", type: "TEXT", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Detailed explanation / particulars of transaction." },
      { name: "total_debit", type: "DECIMAL(14,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Sum of debit rows (must equal total_credit)." },
      { name: "total_credit", type: "DECIMAL(14,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Sum of credit rows (must equal total_debit)." },
      { name: "prepared_by_user_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "rbac_users.id", null: "NO", def: "-", uq: "NO", desc: "Maker (Accountant)." },
      { name: "approved_by_user_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "rbac_users.id", null: "YES", def: "NULL", uq: "NO", desc: "Checker (Treasurer / Trustee)." },
      { name: "approval_timestamp", type: "DATETIME", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Approval date & time." },
      { name: "is_posted", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Posted to general ledger flag." },
      { name: "is_locked", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Locked against modification by auditor." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation stamp." },
      { name: "status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'DRAFT'", uq: "NO", desc: "DRAFT, SUBMITTED, APPROVED, POSTED, REJECTED." }
    ],
    sampleData: [
      { id: 9501, org_id: 101, vch_no: "PAY-2026-00451", type: 2, fy: 2026, date: "2026-04-20", narr: "Being scholarship aid sanctioned for 5 engineering students paid via NEFT", dr: 150000.00, cr: 150000.00, maker: 8, checker: 3, posted: 1, status: "POSTED" }
    ]
  },
  {
    name: "acc_voucher_details",
    purpose: "Atomic debit and credit line items enforcing the fundamental mathematical equation: &Sigma; Dr == &Sigma; Cr.",
    relationships: "M:1 with acc_vouchers.id; M:1 with acc_chart_of_accounts.id",
    indexes: "PRIMARY KEY (id); INDEX idx_vch_det_parent (voucher_id); INDEX idx_vch_det_acc (account_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Line item ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "voucher_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "acc_vouchers.id", null: "NO", def: "-", uq: "NO", desc: "Header voucher." },
      { name: "account_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "acc_chart_of_accounts.id", null: "NO", def: "-", uq: "NO", desc: "Debited or credited ledger account." },
      { name: "debit_amount", type: "DECIMAL(14,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Debit side figure (0.00 if Credit line)." },
      { name: "credit_amount", type: "DECIMAL(14,2)", pk: "NO", fk: "-", null: "NO", def: "0.00", uq: "NO", desc: "Credit side figure (0.00 if Debit line)." },
      { name: "particulars", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Line-level memo note." },
      { name: "cheque_or_ref_no", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Cheque / UTR number." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation stamp." }
    ],
    sampleData: [
      { id: 25001, org_id: 101, vch_id: 9501, acc_id: 55, dr: 150000.00, cr: 0.00, part: "Scholarship Expense A/c", ref: "NEFT-BATCH-81" },
      { id: 25002, org_id: 101, vch_id: 9501, acc_id: 21, dr: 0.00, cr: 150000.00, part: "HDFC Bank Main A/c", ref: "NEFT-BATCH-81" }
    ]
  },
  {
    name: "evt_events",
    purpose: "Manages community conventions, AGMs, cultural programs, festival celebrations, venues, and ticket capacities.",
    relationships: "M:1 with org_organizations.id; M:1 with mst_event_categories.id; 1:N with evt_ticket_types.event_id; 1:N with evt_registrations.event_id",
    indexes: "PRIMARY KEY (id); INDEX idx_evt_dates (start_datetime, end_datetime); INDEX idx_evt_cat (category_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Event ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "category_id", type: "INT UNSIGNED", pk: "NO", fk: "mst_event_categories.id", null: "NO", def: "-", uq: "NO", desc: "Festival, AGM, Sports, Medical." },
      { name: "title", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Event title (e.g. 'Diwali Sneh Milan & Cultural Night 2026')." },
      { name: "start_datetime", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Commencement timestamp." },
      { name: "end_datetime", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Conclusion timestamp." },
      { name: "venue_name", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Venue (e.g. 'Shree Navnat Bhavan Auditorium')." },
      { name: "venue_address", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Complete street address." },
      { name: "total_capacity", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "500", uq: "NO", desc: "Maximum attendee seat quota." },
      { name: "requires_ticket", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "1", uq: "NO", desc: "Requires registration pass." },
      { name: "includes_food_dinner", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "1", uq: "NO", desc: "Includes Bhojan pass coupon." },
      { name: "banner_image_url", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Promotional banner image." },
      { name: "status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'PUBLISHED'", uq: "NO", desc: "DRAFT, PUBLISHED, COMPLETED, CANCELLED." }
    ],
    sampleData: [
      { id: 81, org_id: 101, cat_id: 2, title: "Diwali Sneh Milan & Felicitation 2026", start: "2026-11-05 18:00:00", end: "2026-11-05 22:30:00", venue: "Maheshwari Bhavan, Girgaon", cap: 1200, food: 1, status: "PUBLISHED" }
    ]
  },
  {
    name: "soc_posts",
    purpose: "Private community social timeline storing articles, photos, condolences (Besna), congratulatory notices, and polls.",
    relationships: "M:1 with org_organizations.id; M:1 with mem_members.id (author_member_id); 1:N with soc_post_likes; 1:N with soc_post_comments",
    indexes: "PRIMARY KEY (id); INDEX idx_post_created (created_at DESC); INDEX idx_post_type (post_type); INDEX idx_post_author (author_member_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Post ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "author_member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "NO", def: "-", uq: "NO", desc: "Authoring member." },
      { name: "post_type", type: "VARCHAR(30)", pk: "NO", fk: "-", null: "NO", def: "'GENERAL'", uq: "NO", desc: "GENERAL, NOTICE, CONDOLENCE_BESNA, CONGRATS, POLL." },
      { name: "content_text", type: "TEXT", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Post body text." },
      { name: "media_json", type: "JSON", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Array of uploaded photo URLs, video links, or PDFs." },
      { name: "visibility_scope", type: "VARCHAR(30)", pk: "NO", fk: "-", null: "NO", def: "'ALL_MEMBERS'", uq: "NO", desc: "ALL_MEMBERS, LOCAL_KENDRA, COMMITTEE_ONLY." },
      { name: "is_pinned", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Pinned announcement banner." },
      { name: "likes_count", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Cached counter of likes." },
      { name: "comments_count", type: "INT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Cached counter of comments." },
      { name: "moderation_status", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "'APPROVED'", uq: "NO", desc: "PENDING, APPROVED, FLAGGED, REJECTED." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Post timestamp." }
    ],
    sampleData: [
      { id: 1101, org_id: 101, author: 1001, type: "NOTICE", text: "Warm invitation to all mandal members for the Annual General Meeting on 25th September at 10 AM.", likes: 45, comments: 12, status: "APPROVED" }
    ]
  },
  {
    name: "rbac_users",
    purpose: "Authentication identity accounts for staff, administrators, trustees, and members accessing the platform.",
    relationships: "M:1 with org_organizations.id; M:1 with mem_members.id; 1:N with rbac_user_roles.user_id",
    indexes: "PRIMARY KEY (id); UNIQUE KEY uq_user_login (organization_id, username); UNIQUE KEY uq_user_email (organization_id, email); INDEX idx_user_mobile (mobile)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "User system ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "member_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "mem_members.id", null: "YES", def: "NULL", uq: "NO", desc: "Linked member record." },
      { name: "username", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Unique login handle." },
      { name: "email", type: "VARCHAR(150)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Email address." },
      { name: "mobile", type: "VARCHAR(20)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Mobile number for OTP authentication." },
      { name: "password_hash", type: "VARCHAR(255)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Bcrypt / Argon2id salted hash." },
      { name: "is_2fa_enabled", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "TOTP Two-Factor toggle." },
      { name: "totp_secret", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Encrypted 2FA authenticator secret key." },
      { name: "is_locked", type: "TINYINT(1)", pk: "NO", fk: "-", null: "NO", def: "0", uq: "NO", desc: "Account security lock toggle." },
      { name: "last_login_at", type: "DATETIME", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Last sign-in timestamp." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Creation stamp." }
    ],
    sampleData: [
      { id: 1, org_id: 101, member_id: 1001, username: "ramesh.somani", email: "ramesh@somani.org", mobile: "+919820199881", "mfa_2fa": 1, locked: 0 }
    ]
  },
  {
    name: "sys_audit_logs",
    purpose: "Immutable forensic security log capturing user, IP, user-agent, entity, before-snapshot, and after-delta for every system modification.",
    relationships: "M:1 with org_organizations.id; M:1 with rbac_users.id",
    indexes: "PRIMARY KEY (id); INDEX idx_audit_entity (entity_type, entity_id); INDEX idx_audit_time (created_at DESC); INDEX idx_audit_user (user_id)",
    columns: [
      { name: "id", type: "BIGINT UNSIGNED AUTO_INCREMENT", pk: "YES", fk: "-", null: "NO", def: "-", uq: "YES", desc: "Audit log ID." },
      { name: "organization_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "org_organizations.id", null: "NO", def: "-", uq: "NO", desc: "Tenant scoping." },
      { name: "user_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "rbac_users.id", null: "YES", def: "NULL", uq: "NO", desc: "Executing user ID." },
      { name: "action", type: "VARCHAR(50)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "CREATE, UPDATE, DELETE, LOGIN, APPROVE, EXPORT." },
      { name: "entity_type", type: "VARCHAR(100)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Target table (e.g. 'acc_vouchers', 'mem_members')." },
      { name: "entity_id", type: "BIGINT UNSIGNED", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Target record primary key." },
      { name: "old_values_json", type: "LONGTEXT", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Pre-change JSON snapshot." },
      { name: "new_values_json", type: "LONGTEXT", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Post-change JSON delta." },
      { name: "ip_address", type: "VARCHAR(45)", pk: "NO", fk: "-", null: "NO", def: "-", uq: "NO", desc: "Client IPv4 / IPv6 address." },
      { name: "user_agent", type: "VARCHAR(500)", pk: "NO", fk: "-", null: "YES", def: "NULL", uq: "NO", desc: "Browser user-agent string." },
      { name: "created_at", type: "DATETIME", pk: "NO", fk: "-", null: "NO", def: "CURRENT_TIMESTAMP", uq: "NO", desc: "Immutable event timestamp." }
    ],
    sampleData: [
      { id: 89012, org_id: 101, user_id: 3, action: "APPROVE", entity: "acc_vouchers", entity_id: 9501, ip: "114.143.201.5", time: "2026-04-20 14:15:32" }
    ]
  }
];

module.exports = {
  getDbTables: function() {
    let html = `
    <section id="section-7" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 07</span>
        <h2>7. Complete Database Tables & Schema Specifications</h2>
      </div>

      <div class="content-card">
        <p>Every table is modeled with strict relational constraints, tenant data partitioning keys (<code>organization_id</code>), foreign keys, standard audit trails (<code>created_at, created_by, updated_at, updated_by, deleted_at, status</code>), and real-world sample rows.</p>
        
        <div class="db-tables-wrapper">
    `;

    tablesData.forEach((table, idx) => {
      html += `
        <div class="db-table-card mt-6" id="table-${table.name}">
          <div class="db-table-header">
            <div>
              <span class="table-num">Table ${idx + 1} of ${tablesData.length}</span>
              <h3 class="table-title"><code>${table.name}</code></h3>
            </div>
            <span class="badge-tenant"><i class="fas fa-shield-alt"></i> Tenant Scoped</span>
          </div>
          
          <div class="table-meta">
            <p><strong>Purpose:</strong> ${table.purpose}</p>
            <p><strong>Relationships:</strong> ${table.relationships}</p>
            <p><strong>Indexes & Constraints:</strong> <code>${table.indexes}</code></p>
          </div>

          <div class="table-responsive mt-3">
            <table class="data-table column-spec-table">
              <thead>
                <tr>
                  <th style="width: 180px;">Column Name</th>
                  <th style="width: 220px;">Data Type</th>
                  <th style="width: 60px;">PK</th>
                  <th style="width: 140px;">Foreign Key</th>
                  <th style="width: 60px;">Null</th>
                  <th style="width: 100px;">Default</th>
                  <th style="width: 60px;">UQ</th>
                  <th>Description & Business Purpose</th>
                </tr>
              </thead>
              <tbody>
      `;

      table.columns.forEach(col => {
        html += `
                <tr>
                  <td><code>${col.name}</code></td>
                  <td><span class="type-pill">${col.type}</span></td>
                  <td>${col.pk === 'YES' ? '<span class="badge-pk">PK</span>' : '-'}</td>
                  <td>${col.fk !== '-' ? `<span class="badge-fk">${col.fk}</span>` : '-'}</td>
                  <td>${col.null}</td>
                  <td><code>${col.def}</code></td>
                  <td>${col.uq === 'YES' ? '<span class="badge-uq">UQ</span>' : '-'}</td>
                  <td>${col.desc}</td>
                </tr>
        `;
      });

      html += `
              </tbody>
            </table>
          </div>

          <div class="sample-data-box mt-3">
            <h5><i class="fas fa-database"></i> Representative Sample Data:</h5>
            <div class="code-block-wrapper">
              <pre><code>${JSON.stringify(table.sampleData, null, 2)}</code></pre>
            </div>
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
