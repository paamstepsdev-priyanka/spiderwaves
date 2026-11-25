// generator/build.js
const fs = require('fs');
const path = require('path');

const overviewScope = require('./overview_scope');
const modulesFeatures = require('./modules_features');
const screensList = require('./screens_list');
const mastersList = require('./masters_list');
const dbTables = require('./db_tables');
const erdRelationships = require('./erd_relationships');
const workflowsList = require('./workflows_list');
const rbacApis = require('./rbac_apis');
const rulesReportsArch = require('./rules_reports_arch');
const roadmapTestingFuture = require('./roadmap_testing_future');

function buildHTML() {
  const htmlContent = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Universal Membership & Community Management System (UMCMS) — Enterprise Blueprint</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      if (typeof mermaid !== 'undefined') {
        mermaid.initialize({
          startOnLoad: true,
          theme: 'neutral',
          securityLevel: 'loose',
          themeVariables: {
            primaryColor: '#e0e7ff',
            primaryBorderColor: '#6366f1',
            lineColor: '#4f46e5'
          }
        });
      }
    });
  </script>
  <style>
    :root {
      --primary: #4338ca;
      --primary-hover: #3730a3;
      --primary-light: #e0e7ff;
      --accent: #0ea5e9;
      --success: #10b981;
      --warning: #f59e0b;
      --danger: #ef4444;
      --bg-main: #f8fafc;
      --bg-surface: #ffffff;
      --bg-sidebar: #0f172a;
      --sidebar-text: #94a3b8;
      --sidebar-hover: #1e293b;
      --border-color: #e2e8f0;
      --text-main: #1e293b;
      --text-muted: #64748b;
      --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
      --sidebar-width: 300px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: var(--font-sans);
      background-color: var(--bg-main);
      color: var(--text-main);
      line-height: 1.6;
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar Navigation */
    .app-sidebar {
      width: var(--sidebar-width);
      background-color: var(--bg-sidebar);
      color: #fff;
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      overflow-y: auto;
      z-index: 100;
      display: flex;
      flex-direction: column;
      box-shadow: 4px 0 10px rgba(0,0,0,0.05);
      transition: transform 0.3s ease;
    }

    .sidebar-header {
      padding: 24px 20px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    }

    .brand-title {
      font-size: 1.15rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #fff;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .brand-title i {
      color: var(--accent);
      font-size: 1.3rem;
    }

    .brand-subtitle {
      font-size: 0.75rem;
      color: var(--sidebar-text);
      margin-top: 4px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .sidebar-search {
      padding: 12px 20px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
    }

    .search-input-wrap {
      position: relative;
    }

    .search-input-wrap i {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--sidebar-text);
      font-size: 0.85rem;
    }

    .search-input {
      width: 100%;
      padding: 8px 10px 8px 32px;
      background: #1e293b;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 6px;
      color: #fff;
      font-size: 0.85rem;
      outline: none;
      transition: all 0.2s;
    }

    .search-input:focus {
      border-color: var(--accent);
      background: #243048;
    }

    .nav-menu {
      list-style: none;
      padding: 12px 10px;
      flex: 1;
    }

    .nav-item {
      margin-bottom: 2px;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 12px;
      color: var(--sidebar-text);
      text-decoration: none;
      font-size: 0.84rem;
      font-weight: 500;
      border-radius: 6px;
      transition: all 0.2s;
    }

    .nav-link:hover, .nav-link.active {
      color: #fff;
      background-color: var(--sidebar-hover);
    }

    .nav-link.active {
      color: #fff;
      background-color: var(--primary);
      box-shadow: 0 2px 8px rgba(67, 56, 202, 0.4);
    }

    .nav-link i {
      width: 18px;
      text-align: center;
      font-size: 0.9rem;
    }

    .nav-section-title {
      padding: 12px 12px 6px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #64748b;
    }

    .sidebar-footer {
      padding: 16px 20px;
      border-top: 1px solid rgba(255,255,255,0.08);
      font-size: 0.75rem;
      color: var(--sidebar-text);
      text-align: center;
    }

    /* Main Content Area */
    .app-main {
      margin-left: var(--sidebar-width);
      flex: 1;
      padding: 36px 48px;
      max-width: 1440px;
    }

    /* Top Bar */
    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 32px;
      padding-bottom: 20px;
      border-bottom: 1px solid var(--border-color);
    }

    .document-hero h1 {
      font-size: 1.85rem;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: -0.03em;
    }

    .document-hero p {
      color: var(--text-muted);
      font-size: 0.95rem;
      margin-top: 4px;
    }

    .top-actions {
      display: flex;
      gap: 12px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      font-size: 0.85rem;
      font-weight: 600;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
      border: none;
    }

    .btn-primary {
      background: var(--primary);
      color: #fff;
    }

    .btn-primary:hover {
      background: var(--primary-hover);
    }

    .btn-outline {
      background: #fff;
      color: var(--text-main);
      border: 1px solid var(--border-color);
    }

    .btn-outline:hover {
      background: #f1f5f9;
    }

    /* Section Styles */
    .doc-section {
      margin-bottom: 48px;
      scroll-margin-top: 24px;
    }

    .section-header {
      margin-bottom: 20px;
    }

    .section-badge {
      display: inline-block;
      padding: 3px 8px;
      background: var(--primary-light);
      color: var(--primary);
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      border-radius: 4px;
      letter-spacing: 0.05em;
      margin-bottom: 6px;
    }

    .section-header h2 {
      font-size: 1.5rem;
      font-weight: 700;
      color: #0f172a;
      letter-spacing: -0.02em;
    }

    .content-card {
      background: var(--bg-surface);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 28px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }

    .content-card h3 {
      font-size: 1.2rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 12px;
    }

    .content-card p {
      margin-bottom: 14px;
      color: #334155;
    }

    .lead-quote {
      font-size: 1.05rem;
      font-weight: 500;
      font-style: italic;
      color: var(--primary);
      background: var(--primary-light);
      padding: 16px 20px;
      border-left: 4px solid var(--primary);
      border-radius: 0 8px 8px 0;
      margin-bottom: 20px;
    }

    /* Grids & Cards */
    .grid-2col {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
    }

    .info-box {
      background: #f8fafc;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 20px;
    }

    .info-box.accent-box {
      background: #f0fdf4;
      border-color: #bbf7d0;
    }

    .info-box h4 {
      font-size: 0.95rem;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .info-box ul {
      padding-left: 20px;
    }

    .info-box li {
      margin-bottom: 8px;
      font-size: 0.88rem;
    }

    /* Module Banners */
    .module-title-banner {
      display: flex;
      align-items: center;
      gap: 16px;
      padding-bottom: 18px;
      margin-bottom: 20px;
      border-bottom: 2px solid var(--border-color);
    }

    .mod-letter {
      width: 44px;
      height: 44px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
      color: #fff;
      font-size: 1.3rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(67, 56, 202, 0.25);
    }

    .module-title-banner h3 {
      margin-bottom: 2px;
      font-size: 1.25rem;
    }

    .module-title-banner .subtitle {
      font-size: 0.85rem;
      color: var(--text-muted);
      margin-bottom: 0;
    }

    .feature-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 20px;
    }

    .feature-card {
      background: #f8fafc;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 20px;
    }

    .feature-card h4 {
      font-size: 0.95rem;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .feature-card ul {
      padding-left: 20px;
    }

    .feature-card li {
      font-size: 0.86rem;
      margin-bottom: 8px;
      color: #334155;
    }

    /* Tables */
    .table-responsive {
      overflow-x: auto;
      margin-top: 14px;
      border-radius: 8px;
      border: 1px solid var(--border-color);
    }

    .data-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.86rem;
    }

    .data-table th {
      background: #f1f5f9;
      color: #334155;
      font-weight: 600;
      padding: 12px 14px;
      border-bottom: 1px solid var(--border-color);
    }

    .data-table td {
      padding: 12px 14px;
      border-bottom: 1px solid var(--border-color);
      color: #334155;
      vertical-align: top;
    }

    .data-table tr:last-child td {
      border-bottom: none;
    }

    .data-table tr:hover td {
      background-color: #f8fafc;
    }

    /* Badges & Pills */
    .badge-pk {
      background: #fee2e2;
      color: #dc2626;
      font-size: 0.68rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 4px;
      font-family: var(--font-mono);
    }

    .badge-fk {
      background: #e0e7ff;
      color: #4338ca;
      font-size: 0.68rem;
      font-weight: 600;
      padding: 2px 6px;
      border-radius: 4px;
      font-family: var(--font-mono);
    }

    .badge-uq {
      background: #fef3c7;
      color: #d97706;
      font-size: 0.68rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 4px;
      font-family: var(--font-mono);
    }

    .type-pill {
      font-family: var(--font-mono);
      font-size: 0.75rem;
      color: #0369a1;
      background: #e0f2fe;
      padding: 2px 6px;
      border-radius: 4px;
    }

    .badge-post {
      background: #dcfce7;
      color: #15803d;
      font-weight: 700;
      font-size: 0.72rem;
      padding: 2px 8px;
      border-radius: 4px;
    }

    .badge-get {
      background: #e0f2fe;
      color: #0369a1;
      font-weight: 700;
      font-size: 0.72rem;
      padding: 2px 8px;
      border-radius: 4px;
    }

    .badge-tenant {
      background: #ede9fe;
      color: #6d28d9;
      font-size: 0.75rem;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    /* DB Table Detail Card */
    .db-table-card {
      background: #fff;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      padding: 24px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }

    .db-table-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 12px;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--border-color);
    }

    .table-num {
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--text-muted);
      text-transform: uppercase;
    }

    .table-title code {
      font-size: 1.15rem;
      color: #0f172a;
      font-family: var(--font-mono);
      font-weight: 700;
    }

    .table-meta p {
      font-size: 0.85rem;
      margin-bottom: 4px;
      color: #475569;
    }

    .table-meta code {
      font-family: var(--font-mono);
      font-size: 0.8rem;
      color: #0f172a;
    }

    .code-block-wrapper {
      background: #0f172a;
      border-radius: 6px;
      padding: 12px 16px;
      overflow-x: auto;
    }

    .code-block-wrapper code {
      font-family: var(--font-mono);
      font-size: 0.8rem;
      color: #e2e8f0;
    }

    /* Workflows */
    .workflow-card {
      background: #fff;
      border: 1px solid var(--border-color);
      border-radius: 10px;
      padding: 24px;
    }

    .workflow-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 12px;
      border-bottom: 1px solid var(--border-color);
    }

    .wf-badge {
      display: inline-block;
      background: var(--primary);
      color: #fff;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 4px;
      font-family: var(--font-mono);
      margin-bottom: 4px;
    }

    .wf-title {
      font-size: 1.15rem;
      font-weight: 700;
      color: #0f172a;
    }

    .wf-status-pill code {
      background: #f1f5f9;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 0.78rem;
      font-weight: 600;
      color: #334155;
    }

    .wf-meta-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      font-size: 0.85rem;
      background: #f8fafc;
      padding: 10px 14px;
      border-radius: 6px;
    }

    .step-num {
      width: 26px;
      height: 26px;
      background: var(--primary-light);
      color: var(--primary);
      font-weight: 700;
      font-size: 0.8rem;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
    }

    /* Tech Stack & Hierarchy */
    .tech-stack-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .tech-box {
      background: #f8fafc;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 20px;
    }

    .tech-layer {
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--primary);
      letter-spacing: 0.05em;
    }

    .tech-box h4 {
      font-size: 1rem;
      font-weight: 700;
      color: #0f172a;
      margin: 4px 0 8px;
    }

    .tech-box p {
      font-size: 0.85rem;
      color: #475569;
      margin-bottom: 0;
    }

    .hierarchy-tree-visual {
      background: #f8fafc;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 20px;
      margin-top: 16px;
    }

    .tree-node {
      padding: 10px 14px;
      border-radius: 6px;
      margin-bottom: 8px;
      background: #fff;
      border: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
    }

    .tree-node.level-1 { border-left: 4px solid #4338ca; }
    .tree-node.level-2 { border-left: 4px solid #0ea5e9; margin-left: 20px; }
    .tree-node.level-3 { border-left: 4px solid #10b981; margin-left: 40px; }
    .tree-node.level-4 { border-left: 4px solid #f59e0b; margin-left: 60px; }
    .tree-node.level-5 { border-left: 4px solid #8b5cf6; margin-left: 80px; }

    .tree-node strong { font-size: 0.9rem; color: #0f172a; }
    .tree-node span { font-size: 0.8rem; color: var(--text-muted); }

    .mermaid-container {
      background: #fff;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 20px;
      overflow-x: auto;
      margin-top: 14px;
    }

    /* Print Styles */
    @media print {
      body { background: #fff; color: #000; }
      .app-sidebar, .top-bar, .top-actions { display: none !important; }
      .app-main { margin-left: 0; padding: 0; }
      .content-card, .db-table-card, .workflow-card { box-shadow: none; border: 1px solid #ccc; page-break-inside: avoid; }
    }

    /* Responsive */
    @media (max-width: 1024px) {
      .app-sidebar { transform: translateX(-100%); }
      .app-sidebar.open { transform: translateX(0); }
      .app-main { margin-left: 0; padding: 24px 20px; }
      .mobile-menu-btn { display: inline-flex !important; }
    }

    .mobile-menu-btn {
      display: none;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      background: #fff;
      border: 1px solid var(--border-color);
      border-radius: 6px;
      cursor: pointer;
    }

    /* Utility */
    .mt-3 { margin-top: 12px; }
    .mt-4 { margin-top: 16px; }
    .mt-5 { margin-top: 20px; }
    .mt-6 { margin-top: 24px; }
    .mt-8 { margin-top: 32px; }
    .text-center { text-align: center; }
    .text-success { color: var(--success); }
    .text-info { color: var(--accent); }

    .role-archetypes-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 12px;
    }

    .role-pill {
      background: #f1f5f9;
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 6px 14px;
      font-size: 0.8rem;
      color: #334155;
    }

    .p-all { background: #dcfce7; color: #166534; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.72rem; }
    .p-read { background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 0.72rem; }
    .p-edit { background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 0.72rem; }
    .p-app { background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 0.72rem; }
  </style>
</head>
<body>

  <!-- Sidebar Navigation -->
  <aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
      <div class="brand-title">
        <i class="fas fa-cubes"></i>
        <span>UMCMS</span>
      </div>
      <div class="brand-subtitle">Universal Management OS</div>
    </div>

    <div class="sidebar-search">
      <div class="search-input-wrap">
        <i class="fas fa-search"></i>
        <input type="text" class="search-input" id="sidebarSearch" placeholder="Search blueprint..." onkeyup="filterContent()">
      </div>
    </div>

    <ul class="nav-menu">
      <li class="nav-section-title">Architecture & Overview</li>
      <li class="nav-item"><a href="#section-1" class="nav-link active"><i class="fas fa-home"></i> 01. Project Overview</a></li>
      <li class="nav-item"><a href="#section-2" class="nav-link"><i class="fas fa-eye"></i> 02. Vision & Scope</a></li>
      <li class="nav-item"><a href="#section-3" class="nav-link"><i class="fas fa-th-large"></i> 03. Complete Module List</a></li>
      <li class="nav-item"><a href="#section-4" class="nav-link"><i class="fas fa-list-check"></i> 04. Features & Subfeatures</a></li>

      <li class="nav-section-title">UI/UX & Master Data</li>
      <li class="nav-item"><a href="#section-5" class="nav-link"><i class="fas fa-desktop"></i> 05. Screen & UI Planning</a></li>
      <li class="nav-item"><a href="#section-6" class="nav-link"><i class="fas fa-table-list"></i> 06. Master Data System</a></li>

      <li class="nav-section-title">Database Engineering</li>
      <li class="nav-item"><a href="#section-7" class="nav-link"><i class="fas fa-database"></i> 07. Database Tables (Schema)</a></li>
      <li class="nav-item"><a href="#section-8" class="nav-link"><i class="fas fa-project-diagram"></i> 08. ERD & Relationships</a></li>

      <li class="nav-section-title">Operations & Governance</li>
      <li class="nav-item"><a href="#section-9" class="nav-link"><i class="fas fa-route"></i> 09. Operational Workflows</a></li>
      <li class="nav-item"><a href="#section-10" class="nav-link"><i class="fas fa-user-shield"></i> 10. RBAC Permission Matrix</a></li>
      <li class="nav-item"><a href="#section-11" class="nav-link"><i class="fas fa-network-wired"></i> 11. REST API Catalog</a></li>
      <li class="nav-item"><a href="#section-12" class="nav-link"><i class="fas fa-gavel"></i> 12. Business Rules</a></li>
      <li class="nav-item"><a href="#section-13" class="nav-link"><i class="fas fa-chart-line"></i> 13. Reports & Dashboards</a></li>

      <li class="nav-section-title">Technical Strategy</li>
      <li class="nav-item"><a href="#section-14" class="nav-link"><i class="fas fa-server"></i> 14. Technology Stack</a></li>
      <li class="nav-item"><a href="#section-15" class="nav-link"><i class="fas fa-lock"></i> 15. Security Architecture</a></li>
      <li class="nav-item"><a href="#section-16" class="nav-link"><i class="fas fa-timeline"></i> 16. Development Roadmap</a></li>
      <li class="nav-item"><a href="#section-17" class="nav-link"><i class="fas fa-vial-circle-check"></i> 17. Quality & Testing Plan</a></li>
      <li class="nav-item"><a href="#section-18" class="nav-link"><i class="fas fa-lightbulb"></i> 18. Future Innovations</a></li>
    </ul>

    <div class="sidebar-footer">
      <div>UMCMS Blueprint v2.5 Enterprise</div>
      <div>DeepMind Systems Architecture</div>
    </div>
  </aside>

  <!-- Main Content Wrapper -->
  <main class="app-main">
    <div class="top-bar">
      <div style="display: flex; align-items: center; gap: 14px;">
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <div class="document-hero">
          <h1>Universal Membership & Community Management System</h1>
          <p>Architectural Specification, Relational Schema, Multi-Tenant Governance & Operational Blueprint</p>
        </div>
      </div>
      <div class="top-actions">
        <button class="btn btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print / PDF</button>
      </div>
    </div>

    <!-- Injecting All 18 Sections -->
    ${overviewScope.getOverviewAndScope()}
    ${modulesFeatures.getModulesFeatures()}
    ${screensList.getScreensList()}
    ${mastersList.getMastersList()}
    ${dbTables.getDbTables()}
    ${erdRelationships.getErdRelationships()}
    ${workflowsList.getWorkflowsList()}
    ${rbacApis.getRbacAndApis()}
    ${rulesReportsArch.getRulesReportsAndArch()}
    ${roadmapTestingFuture.getRoadmapTestingFuture()}

  </main>

  <script>
    function toggleSidebar() {
      document.getElementById('appSidebar').classList.toggle('open');
    }

    function filterContent() {
      const query = document.getElementById('sidebarSearch').value.toLowerCase();
      const navLinks = document.querySelectorAll('.nav-link');
      navLinks.forEach(link => {
        const text = link.innerText.toLowerCase();
        link.parentElement.style.display = text.includes(query) ? 'block' : 'none';
      });
    }

    // Smooth scroll and active link highlighting
    const sections = document.querySelectorAll('.doc-section');
    const navLinks = document.querySelectorAll('.nav-link');

    window.addEventListener('scroll', () => {
      let current = '';
      sections.forEach(section => {
        const sectionTop = section.offsetTop - 100;
        if (pageYOffset >= sectionTop) {
          current = section.getAttribute('id');
        }
      });

      navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) {
          link.classList.add('active');
        }
      });
    });
  </script>
</body>
</html>
  `;

  const outputPath = path.join(__dirname, '..', 'universal_organization_management_blueprint.html');
  fs.writeFileSync(outputPath, htmlContent, 'utf8');
  console.log(`Blueprint written successfully to: ${outputPath}`);

  // Also write to artifact folder
  const artifactDir = 'C:\\Users\\Administrator.IMAGE-WIN2019\\.gemini\\antigravity\\brain\\49f1af9b-4bd8-4e8c-9771-e3c30c5f43bb';
  if (fs.existsSync(artifactDir)) {
    const artifactPath = path.join(artifactDir, 'universal_organization_management_blueprint.html');
    fs.writeFileSync(artifactPath, htmlContent, 'utf8');
    console.log(`Artifact blueprint written to: ${artifactPath}`);
  }
}

buildHTML();

