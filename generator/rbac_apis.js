// generator/rbac_apis.js
module.exports = {
  getRbacAndApis: function() {
    return `
    <section id="section-10" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 10</span>
        <h2>10. Role-Based Access Control (RBAC) & Permission Matrix</h2>
      </div>

      <div class="content-card">
        <p>UMCMS implements a multi-dimensional authorization model combining <strong>Role-Based Access Control (RBAC)</strong> with <strong>Attribute-Based Access Control (ABAC)</strong> for geographic unit scoping. A user's privileges are evaluated against their assigned Role, the Organization context, and their designated Branch/Kendra boundary.</p>

        <h4 class="mt-4">10.1 Key Organizational Role Archetypes</h4>
        <div class="role-archetypes-grid">
          <div class="role-pill"><strong>R1: Super Admin</strong> (Global Platform Owner)</div>
          <div class="role-pill"><strong>R2: Trustee Chair / President</strong> (Apex Legal Head)</div>
          <div class="role-pill"><strong>R3: General Secretary</strong> (Chief Executive Officer)</div>
          <div class="role-pill"><strong>R4: Treasurer</strong> (Chief Financial Officer)</div>
          <div class="role-pill"><strong>R5: Head Accountant</strong> (Maker of Financial Vouchers)</div>
          <div class="role-pill"><strong>R6: Kendra / Branch Admin</strong> (Local Chapter Secretary)</div>
          <div class="role-pill"><strong>R7: Committee Member</strong> (Executive Council Member)</div>
          <div class="role-pill"><strong>R8: Cashier / Desk Clerk</strong> (Counter Collection Operator)</div>
          <div class="role-pill"><strong>R9: Enrolled Member</strong> (Self-Service Household Portal)</div>
          <div class="role-pill"><strong>R10: Non-Member Donor / Guest</strong> (External Benefactor)</div>
        </div>

        <h4 class="mt-6">10.2 Comprehensive Permission Matrix</h4>
        <div class="table-responsive">
          <table class="data-table rbac-matrix-table">
            <thead>
              <tr>
                <th rowspan="2" style="width: 200px;">Functional Module & Capability</th>
                <th colspan="10" class="text-center">Role Privileges (CRUD + Approve + Export)</th>
              </tr>
              <tr>
                <th>R1 Super</th>
                <th>R2 Trust</th>
                <th>R3 Secy</th>
                <th>R4 Treas</th>
                <th>R5 Acct</th>
                <th>R6 Kndr</th>
                <th>R7 Cmt</th>
                <th>R8 Cash</th>
                <th>R9 Mem</th>
                <th>R10 Gst</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Organization Profile & Hierarchy</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-edit">RU</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-read">R*</span></td>
                <td><span class="p-read">R*</span></td>
                <td>-</td>
                <td><span class="p-read">R</span></td>
                <td>-</td>
              </tr>
              <tr>
                <td><strong>Member Management & Biodata</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-read">RE</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-all">CRUE*</span></td>
                <td><span class="p-read">R*</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-edit">RU^</span></td>
                <td>-</td>
              </tr>
              <tr>
                <td><strong>Family Registry (Kutumb)</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-all">CRUE*</span></td>
                <td><span class="p-read">R*</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-edit">RU^</span></td>
                <td>-</td>
              </tr>
              <tr>
                <td><strong>Membership Scrutiny & Approval</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-app">APPROVE</span></td>
                <td><span class="p-app">APPROVE</span></td>
                <td><span class="p-read">R</span></td>
                <td>-</td>
                <td><span class="p-app">SCRUT*</span></td>
                <td>-</td>
                <td>-</td>
                <td><span class="p-edit">APPLY</span></td>
                <td>-</td>
              </tr>
              <tr>
                <td><strong>Committee Meetings & Minutes</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-app">APPROVE</span></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-read">R</span></td>
                <td>-</td>
                <td><span class="p-read">R*</span></td>
                <td><span class="p-read">R*</span></td>
                <td>-</td>
                <td><span class="p-read">CIRC</span></td>
                <td>-</td>
              </tr>
              <tr>
                <td><strong>Membership Fee Billing & Dues</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-read">RE</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-read">RE*</span></td>
                <td>-</td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-read">VIEW^</span></td>
                <td>-</td>
              </tr>
              <tr>
                <td><strong>Fee Collection & Counter Receipts</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-all">CRU*</span></td>
                <td>-</td>
                <td><span class="p-all">COLLECT</span></td>
                <td><span class="p-edit">PAY^</span></td>
                <td>-</td>
              </tr>
              <tr>
                <td><strong>Donation Management & 80G Receipts</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-all">CRU*</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-all">COLLECT</span></td>
                <td><span class="p-edit">DONATE</span></td>
                <td><span class="p-edit">DONATE</span></td>
              </tr>
              <tr>
                <td><strong>Accounting Vouchers (Maker - Draft)</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td>-</td>
                <td>-</td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-all">MAKER</span></td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
              </tr>
              <tr>
                <td><strong>Accounting Vouchers (Checker - Post)</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-app">CHECKER</span></td>
                <td>-</td>
                <td><span class="p-app">CHECKER</span></td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
              </tr>
              <tr>
                <td><strong>Financial Statements (TB, P&L, BS)</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-read">RE</span></td>
                <td><span class="p-read">RE</span></td>
                <td><span class="p-read">RE</span></td>
                <td><span class="p-read">RE</span></td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
              </tr>
              <tr>
                <td><strong>Events & Check-In Turnstile</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-all">CRUDE</span></td>
                <td><span class="p-read">R</span></td>
                <td>-</td>
                <td><span class="p-all">CRU*</span></td>
                <td><span class="p-read">SCAN</span></td>
                <td><span class="p-read">SCAN</span></td>
                <td><span class="p-edit">REGISTER</span></td>
                <td><span class="p-edit">REGISTER</span></td>
              </tr>
              <tr>
                <td><strong>Social Timeline & Feed</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-edit">MODERATE</span></td>
                <td><span class="p-edit">MODERATE</span></td>
                <td><span class="p-read">R</span></td>
                <td>-</td>
                <td><span class="p-edit">POST</span></td>
                <td><span class="p-edit">POST</span></td>
                <td>-</td>
                <td><span class="p-edit">POST</span></td>
                <td><span class="p-read">VIEW</span></td>
              </tr>
              <tr>
                <td><strong>Welfare & Scholarship Aid</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-app">SANCTION</span></td>
                <td><span class="p-app">SANCTION</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-read">R</span></td>
                <td><span class="p-read">VERIFY*</span></td>
                <td><span class="p-read">R</span></td>
                <td>-</td>
                <td><span class="p-edit">APPLY</span></td>
                <td>-</td>
              </tr>
              <tr>
                <td><strong>Audit Logs & Security Vault</strong></td>
                <td><span class="p-all">CRUD</span></td>
                <td><span class="p-read">RE</span></td>
                <td><span class="p-read">RE</span></td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
              </tr>
            </tbody>
          </table>
          <p class="legend-note"><small><strong>Legend:</strong> C=Create, R=Read, U=Update, D=Delete, E=Export, P=Print. <code>*</code> = Scoped strictly to assigned Kendra/Branch jurisdiction. <code>^</code> = Scoped strictly to logged-in member's own family records.</small></p>
        </div>
      </div>
    </section>

    <section id="section-11" class="doc-section">
      <div class="section-header">
        <span class="section-badge">Section 11</span>
        <h2>11. REST API Endpoint Specifications</h2>
      </div>

      <div class="content-card">
        <p>The UMCMS backend exposes a complete, versioned, RESTful API (<code>/api/v1/...</code>) adhering to JSON:API standards. All calls require tenant scoping via header: <code>X-Tenant-ID: &lt;tenant_code&gt;</code> and authentication via <code>Authorization: Bearer &lt;JWT&gt;</code>.</p>

        <h3 class="mt-4">11.1 Key API Endpoints Catalog</h3>
        <div class="table-responsive">
          <table class="data-table api-endpoints-table">
            <thead>
              <tr>
                <th style="width: 80px;">Method</th>
                <th style="width: 280px;">Endpoint URI</th>
                <th>Functional Purpose</th>
                <th>Request Headers & Payload Schema</th>
                <th>Success Response (200 / 201)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span class="badge-post">POST</span></td>
                <td><code>/api/v1/auth/login</code></td>
                <td>Authenticates credentials and issues JWT token with 2FA requirement flag.</td>
                <td><code>{"username": "ramesh.somani", "password": "...", "device_id": "..."}</code></td>
                <td><code>{"token": "ey...", "requires_2fa": true, "user": {...}}</code></td>
              </tr>
              <tr>
                <td><span class="badge-post">POST</span></td>
                <td><code>/api/v1/auth/verify-2fa</code></td>
                <td>Validates TOTP authenticator code and returns authorized session.</td>
                <td><code>{"temp_token": "...", "totp_code": "481920"}</code></td>
                <td><code>{"access_token": "ey...", "expires_in": 86400, "roles": [...]}</code></td>
              </tr>
              <tr>
                <td><span class="badge-get">GET</span></td>
                <td><code>/api/v1/members</code></td>
                <td>Lists paginated members with multi-parametric search and unit filters.</td>
                <td>Query params: <code>?page=1&limit=25&unit_id=3&blood_group=5&gotra=Somani</code></td>
                <td><code>{"data": [...], "meta": {"total": 4120, "page": 1, "last_page": 165}}</code></td>
              </tr>
              <tr>
                <td><span class="badge-post">POST</span></td>
                <td><code>/api/v1/members</code></td>
                <td>Enrolls a new member with complete biodata, addresses, and contacts.</td>
                <td><code>{"first_name": "Ramesh", "last_name": "Somani", "native_place_vatan": "Didwana", "mobile": "+91982...", "addresses": [...]}</code></td>
                <td><code>{"status": "success", "member_id": 1001, "membership_no": "MMP-LIFE-2041"}</code></td>
              </tr>
              <tr>
                <td><span class="badge-get">GET</span></td>
                <td><code>/api/v1/members/{id}/family-tree</code></td>
                <td>Returns generational kinship graph data for visual family tree rendering.</td>
                <td>Path: <code>id=1001</code></td>
                <td><code>{"family_code": "FAM-0814", "nodes": [...], "edges": [...]}</code></td>
              </tr>
              <tr>
                <td><span class="badge-post">POST</span></td>
                <td><code>/api/v1/fees/collect</code></td>
                <td>Records payment collection, generates receipt, and posts to accounting.</td>
                <td><code>{"member_id": 1005, "demand_ids": [401], "amount": 1200.00, "payment_mode": "UPI_QR", "utr": "UPI394..."}</code></td>
                <td><code>{"receipt_number": "REC-2026-00412", "receipt_pdf_url": "https://...", "voucher_id": 9502}</code></td>
              </tr>
              <tr>
                <td><span class="badge-post">POST</span></td>
                <td><code>/api/v1/donations</code></td>
                <td>Records philanthropic contribution, creates 80G cert, and funds ledger.</td>
                <td><code>{"donor_id": 151, "amount": 25000.00, "category_id": 2, "in_memory_of": "Late Gopaldasji Somani", "payment_mode": "NEFT"}</code></td>
                <td><code>{"receipt_no": "DON-2026-00108", "cert_80g": "80G-MMP-2026-0108", "pdf_url": "https://..."}</code></td>
              </tr>
              <tr>
                <td><span class="badge-post">POST</span></td>
                <td><code>/api/v1/accounting/vouchers</code></td>
                <td>Creates double-entry financial voucher (Payment, Receipt, Journal, Contra).</td>
                <td><code>{"voucher_type": "PAYMENT", "voucher_date": "2026-04-20", "narration": "Scholarship", "lines": [{"account_id": 55, "dr": 150000, "cr": 0}, {"account_id": 21, "dr": 0, "cr": 150000}]}</code></td>
                <td><code>{"voucher_number": "PAY-2026-00451", "status": "DRAFT", "balanced": true}</code></td>
              </tr>
              <tr>
                <td><span class="badge-post">POST</span></td>
                <td><code>/api/v1/accounting/vouchers/{id}/approve</code></td>
                <td>Dual-control checker approval posting voucher to the General Ledger.</td>
                <td>Path: <code>id=9501</code>; Payload: <code>{"remarks": "Verified with bank statement"}</code></td>
                <td><code>{"voucher_id": 9501, "status": "POSTED", "posted_at": "2026-04-20T14:15:32Z"}</code></td>
              </tr>
              <tr>
                <td><span class="badge-get">GET</span></td>
                <td><code>/api/v1/social/feed</code></td>
                <td>Retrieves community timeline posts, polls, condolences, and notices.</td>
                <td>Query params: <code>?page=1&category=ALL</code></td>
                <td><code>{"posts": [{"id": 1101, "author": {...}, "type": "NOTICE", "likes": 45, "user_reacted": true}]}</code></td>
              </tr>
              <tr>
                <td><span class="badge-post">POST</span></td>
                <td><code>/api/v1/events/{id}/check-in</code></td>
                <td>Scans and redeems encrypted attendee QR pass at event turnstile gate.</td>
                <td><code>{"qr_payload": "MMP-PASS-81-X9A21", "gate_id": "GATE-01", "redeem_meal": true}</code></td>
                <td><code>{"status": "VALID", "attendee_name": "Ramesh Somani", "meal_redeemed": true, "check_in_time": "..."}</code></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    `;
  }
};

