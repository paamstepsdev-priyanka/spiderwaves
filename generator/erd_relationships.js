module.exports = {
  getErdRelationships: function() {
    return `
    <section id="section-8" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 08</span>
        <h2>8. Table Relationships & Entity Relationship Diagrams (ERD)</h2>
      </div>

      <div class="content-card">
        <h3>8.1 Visual Entity Relationship Diagrams (Mermaid)</h3>
        <p>The UMCMS schema is divided into 4 tightly-integrated subsystems ensuring high cohesion, modularity, and database performance.</p>

        <h4 class="mt-4"><i class="fas fa-sitemap"></i> Subsystem 1: Tenant, Organization & Member Core Ecosystem</h4>
        <div class="mermaid-container">
          <pre class="mermaid">
erDiagram
    sys_tenants ||--o{ org_organizations : "provisions"
    org_organizations ||--o{ org_units : "hierarchically divides"
    org_units ||--o{ org_units : "parent/child unit"
    org_organizations ||--o{ mem_members : "enrolls"
    org_units ||--o{ mem_members : "assigns to Kendra"
    mem_members ||--o{ mem_addresses : "resides at"
    fam_families ||--o{ fam_family_members : "contains"
    mem_members ||--o{ fam_family_members : "linked to"
    mem_members ||--o| fam_families : "heads as Kutumb Pramukh"
    mbr_plans ||--o{ mbr_subscriptions : "subscribes"
    mem_members ||--o{ mbr_subscriptions : "holds"
          </pre>
        </div>

        <h4 class="mt-6"><i class="fas fa-landmark"></i> Subsystem 2: Committee Governance & Democratic Proceedings</h4>
        <div class="mermaid-container">
          <pre class="mermaid">
erDiagram
    org_organizations ||--o{ cmt_committees : "constitutes"
    cmt_committees ||--o{ cmt_committee_members : "appoints"
    mem_members ||--o{ cmt_committee_members : "serves on"
    cmt_committees ||--o{ cmt_meetings : "convenes"
    cmt_meetings ||--o{ cmt_meeting_agendas : "deliberates"
    cmt_meetings ||--o{ cmt_meeting_attendances : "records presence"
    mem_members ||--o{ cmt_meeting_attendances : "attends"
    cmt_meetings ||--o{ cmt_resolutions : "passes official Tharav"
    mem_members ||--o{ cmt_resolutions : "proposes / seconds"
          </pre>
        </div>

        <h4 class="mt-6"><i class="fas fa-calculator"></i> Subsystem 3: Financial Accounting, Dues & Donations</h4>
        <div class="mermaid-container">
          <pre class="mermaid">
erDiagram
    org_organizations ||--o{ acc_chart_of_accounts : "maintains ledger tree"
    org_organizations ||--o{ acc_vouchers : "records double-entry"
    acc_vouchers ||--|{ acc_voucher_details : "balances Dr == Cr"
    acc_chart_of_accounts ||--o{ acc_voucher_details : "posts line items"
    mem_members ||--o{ fee_demands : "invoiced dues"
    mem_members ||--o{ fee_collections : "pays counter receipts"
    fee_collections ||--o| acc_vouchers : "auto-creates Receipt Voucher"
    don_donors ||--o{ don_donations : "contributes philanthropic gift"
    don_donations ||--o| acc_vouchers : "posts 80G receipt voucher"
          </pre>
        </div>

        <h4 class="mt-6"><i class="fas fa-share-alt"></i> Subsystem 4: Social Community, Events & Public Facilities</h4>
        <div class="mermaid-container">
          <pre class="mermaid">
erDiagram
    org_organizations ||--o{ evt_events : "organizes"
    evt_events ||--o{ evt_ticket_types : "offers tiers"
    mem_members ||--o{ evt_registrations : "books passes"
    evt_registrations ||--o{ evt_attendees : "issues entry badge"
    mem_members ||--o{ soc_posts : "publishes feed"
    soc_posts ||--o{ soc_post_likes : "receives reactions"
    soc_posts ||--o{ soc_post_comments : "receives discussions"
    mem_members ||--o{ grv_tickets : "files grievance ticket"
    mem_members ||--o{ fac_bookings : "reserves community hall"
          </pre>
        </div>

        <h3 class="mt-8">8.2 Complete Table Relationship Mapping Matrix</h3>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Relationship Type</th>
                <th>Primary Table (1)</th>
                <th>Foreign Table (N / 1)</th>
                <th>Foreign Key Column</th>
                <th>Cascade / Delete Rule</th>
                <th>Business Integrity Purpose</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>One-to-Many (1:N)</strong></td>
                <td><code>org_organizations</code></td>
                <td><code>mem_members</code></td>
                <td><code>mem_members.organization_id</code></td>
                <td><code>RESTRICT</code></td>
                <td>Guarantees member cannot be orphaned from their legal organization.</td>
              </tr>
              <tr>
                <td><strong>One-to-Many (1:N)</strong></td>
                <td><code>org_units</code> (Parent)</td>
                <td><code>org_units</code> (Child)</td>
                <td><code>org_units.parent_unit_id</code></td>
                <td><code>RESTRICT</code></td>
                <td>Maintains hierarchical tree from Apex Body down to local Wards.</td>
              </tr>
              <tr>
                <td><strong>One-to-Many (1:N)</strong></td>
                <td><code>mem_members</code></td>
                <td><code>mem_addresses</code></td>
                <td><code>mem_addresses.member_id</code></td>
                <td><code>CASCADE</code></td>
                <td>Allows recording Residential, Permanent, Office, and Native addresses per member.</td>
              </tr>
              <tr>
                <td><strong>One-to-Many (1:N)</strong></td>
                <td><code>fam_families</code></td>
                <td><code>fam_family_members</code></td>
                <td><code>fam_family_members.family_id</code></td>
                <td><code>CASCADE</code></td>
                <td>Associates multiple relatives under a single Kutumb household grouping.</td>
              </tr>
              <tr>
                <td><strong>One-to-One (1:1)</strong></td>
                <td><code>fam_families</code></td>
                <td><code>mem_members</code></td>
                <td><code>fam_families.head_member_id</code></td>
                <td><code>RESTRICT</code></td>
                <td>Each family unit must designate exactly one Kutumb Pramukh (Head of Family).</td>
              </tr>
              <tr>
                <td><strong>Many-to-Many (N:M)</strong></td>
                <td><code>cmt_committees</code></td>
                <td><code>mem_members</code></td>
                <td>Junction: <code>cmt_committee_members</code></td>
                <td><code>RESTRICT</code></td>
                <td>Allows members to serve on multiple committees (e.g. Managing Committee and Welfare Board).</td>
              </tr>
              <tr>
                <td><strong>One-to-Many (1:N)</strong></td>
                <td><code>cmt_meetings</code></td>
                <td><code>cmt_resolutions</code></td>
                <td><code>cmt_resolutions.meeting_id</code></td>
                <td><code>RESTRICT</code></td>
                <td>Tracks formal resolutions (Tharav) passed during official committee meetings.</td>
              </tr>
              <tr>
                <td><strong>One-to-Many (1:N)</strong></td>
                <td><code>acc_vouchers</code></td>
                <td><code>acc_voucher_details</code></td>
                <td><code>acc_voucher_details.voucher_id</code></td>
                <td><code>CASCADE</code></td>
                <td>Atomic double-entry lines balancing debit and credit entries.</td>
              </tr>
              <tr>
                <td><strong>One-to-One (1:1)</strong></td>
                <td><code>fee_collections</code></td>
                <td><code>acc_vouchers</code></td>
                <td><code>fee_collections.accounting_voucher_id</code></td>
                <td><code>SET NULL</code></td>
                <td>Automatic bridge from counter fee collection to general ledger Receipt Voucher.</td>
              </tr>
              <tr>
                <td><strong>One-to-One (1:1)</strong></td>
                <td><code>don_donations</code></td>
                <td><code>acc_vouchers</code></td>
                <td><code>don_donations.accounting_voucher_id</code></td>
                <td><code>SET NULL</code></td>
                <td>Automatic double-entry posting of philanthropic 80G donations to trust funds.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    `;
  }
};

