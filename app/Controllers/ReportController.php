<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AccountModel;
use App\Models\StockPointModel;
use App\Services\LedgerService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ReportController
 * 
 * MODULE: REPORTS & STATEMENTS
 * Handles operational and management reports including:
 * - Stock Report (Department-wise Fine MT balances)
 * - Legacy alias loss_report
 */
class ReportController extends BaseController
{
    protected AccountModel $accountModel;
    protected StockPointModel $stockPointModel;
    protected LedgerService $ledgerService;

    public function __construct()
    {
        $this->accountModel    = model(AccountModel::class);
        $this->stockPointModel = model(StockPointModel::class);
        $this->ledgerService   = new LedgerService();
    }

    /**
     * Stock Report (Department-wise metal balances).
     * Recreates legacy report/loss_report with modern CI4 architecture and performance.
     *
     * @return string|ResponseInterface
     */
    public function stockReport()
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        // Dynamic RBAC authorization
        if (!can('stock.view')) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Stock module.');
        }

        // Fetch high-performance aggregated stock report data
        $reportData = $this->ledgerService->getStockReportData();

        $data = [
            'title'          => 'Stock - SpiderWaves',
            'breadcrumb'     => 'Stock',
            'stockPointDate' => $reportData['stock_point_date'],
            'reportItems'    => $reportData['items'],
            'totalBalance'   => $reportData['total_balance'],
            'displayTotal'   => $reportData['display_total'],
        ];

        return view('report/stock_report', $data);
    }

    /**
     * Customer Wise Report: lists all active customers with their net combined metal balance (MT+OR).
     *
     * @return string|ResponseInterface
     */
    public function customerWiseReport()
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        // Role-based authorization matching legacy system
        $allowedRoles = ['admin', 'superadmin'];
        if (!in_array($accessLevel, $allowedRoles, true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Customer Wise Report.');
        }

        $reportData = $this->ledgerService->getCustomerWiseReportData();

        $data = [
            'title'        => 'Customer Wise Report - SpiderWaves',
            'breadcrumb'   => 'Customer Wise Report',
            'items'        => $reportData['items'],
            'totalCount'   => $reportData['total_count'],
            'totalBalance' => $reportData['total_balance'],
            'displayTotal' => $reportData['display_total'],
        ];

        return view('report/cust_wise_report', $data);
    }

    /**
     * Customer Ledger: displays Receipts vs Issues with running balances, opening balances, and date filters.
     *
     * @param string $key 32-character customer private_key
     * @return string|ResponseInterface
     */
    public function customerLedger(string $key)
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        // Authorization check: Staff roles (admin/superadmin) or customer self-access
        if ($accessLevel === 'cust') {
            if (($spider['private_key'] ?? '') !== $key) {
                return redirect()->to(base_url('report/customer_ledger_for_customer/' . ($spider['private_key'] ?? '')))
                    ->with('error', 'Unauthorized access to other customer ledger.');
            }
        } elseif (!in_array($accessLevel, ['admin', 'superadmin'], true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Customer Ledger.');
        }

        // Retrieve optional date filters from GET or POST
        $fromDate = $this->request->getVar('from_date');
        $toDate   = $this->request->getVar('to_date');

        $ledgerData = $this->ledgerService->getCustomerLedgerData($key, $fromDate, $toDate);

        if (!$ledgerData) {
            return redirect()->to(base_url('report/cust_wise_report'))->with('error', 'Customer profile not found.');
        }

        $ledgerData['title']      = 'Customer Ledger - ' . $ledgerData['customer']['cust_name'];
        $ledgerData['breadcrumb'] = 'Customer Ledger';

        return view('report/customer_ledger', $ledgerData);
    }

    /**
     * Customer Self-Service Portal Ledger.
     * Accessible exclusively to the customer matching the private key.
     *
     * @param string $key Customer private key
     * @return string|ResponseInterface
     */
    public function customerLedgerForCustomer(string $key)
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        // Security check: Customer can only view their own ledger
        if ($accessLevel !== 'cust' || ($spider['private_key'] ?? '') !== $key) {
            if (in_array($accessLevel, ['admin', 'superadmin'], true)) {
                return redirect()->to(base_url('report/customer_ledger/' . $key));
            }
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized customer access.');
        }

        $fromDate = $this->request->getVar('from_date');
        $toDate   = $this->request->getVar('to_date');

        $ledgerData = $this->ledgerService->getCustomerLedgerData($key, $fromDate, $toDate);
        if (!$ledgerData) {
            return redirect()->to(base_url('login'))->with('error', 'Customer profile not found.');
        }

        $openings = [
            'display_mt'    => view_round_up($ledgerData['opening_balances']['mt'] ?? 0, 3),
            'display_or'    => view_round_up($ledgerData['opening_balances']['or'] ?? 0, 3),
            'display_rk'    => number_format((float)($ledgerData['opening_balances']['rk'] ?? 0), 2, '.', ''),
            'display_metal' => view_round_up($ledgerData['opening_balances']['metal'] ?? 0, 3),
        ];

        $rows = [];
        $runningMetal = (float)($ledgerData['opening_balances']['metal'] ?? 0);
        $runningCash  = (float)($ledgerData['opening_balances']['rk'] ?? 0);

        foreach ($ledgerData['pairedRows'] ?? [] as $pair) {
            $r = $pair['receipt'] ?? null;
            $i = $pair['issue'] ?? null;

            if ($r && empty($r['is_opening'])) {
                $runningMetal += (float)($r['fine_mt'] ?? 0) + (float)($r['fine_or'] ?? 0);
                $runningCash  += (float)($r['rk_amount'] ?? 0);
            }
            if ($i && empty($i['is_opening'])) {
                $runningMetal -= (float)($i['fine_mt'] ?? 0) + (float)($i['fine_or'] ?? 0);
                $runningCash  -= (float)($i['rk_amount'] ?? 0);
            }

            $rFine = $r ? (($r['fine_mt'] ?? 0) + ($r['fine_or'] ?? 0) + ($r['rk_amount'] ?? 0)) : 0;
            $iFine = $i ? (($i['fine_mt'] ?? 0) + ($i['fine_or'] ?? 0) + ($i['rk_amount'] ?? 0)) : 0;

            $rows[] = [
                'r_date'     => $r ? ($r['date'] ?? '') : '',
                'r_mode'     => $r ? ($r['mode'] ?? $r['description'] ?? '') : '',
                'r_gross'    => $r ? ($r['gross_weight'] ?? '-') : '-',
                'r_less'     => $r ? ($r['less_weight'] ?? '-') : '-',
                'r_net'      => $r ? ($r['net_weight'] ?? '-') : '-',
                'r_purity'   => $r ? ($r['purity'] ?? '-') : '-',
                'r_fine_val' => $r ? (($rFine > 0) ? view_round_up($rFine, 3) : '-') : '-',
                'i_date'     => $i ? ($i['date'] ?? '') : '',
                'i_mode'     => $i ? ($i['mode'] ?? $i['description'] ?? '') : '',
                'i_gross'    => $i ? ($i['gross_weight'] ?? '-') : '-',
                'i_less'     => $i ? ($i['less_weight'] ?? '-') : '-',
                'i_net'      => $i ? ($i['net_weight'] ?? '-') : '-',
                'i_purity'   => $i ? ($i['purity'] ?? '-') : '-',
                'i_fine_val' => $i ? (($iFine > 0) ? view_round_up($iFine, 3) : '-') : '-',
                'run_metal'  => view_round_up($runningMetal, 3),
                'run_cash'   => number_format($runningCash, 2, '.', ''),
            ];
        }

        $ledgerData['openings']   = $openings;
        $ledgerData['rows']       = $rows;
        $ledgerData['title']      = 'Statement - ' . $ledgerData['customer']['cust_name'];
        $ledgerData['breadcrumb'] = 'Customer Statement';

        return view('report/customer_ledger_for_customer', $ledgerData);
    }

    /**
     * Customer Statement Approval Action.
     * Updates waves.approved_by_cust = '1' for the verified customer.
     *
     * @param string $key Customer private key
     * @return ResponseInterface
     */
    public function custApproved(string $key): ResponseInterface
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        if ($accessLevel !== 'cust' || ($spider['private_key'] ?? '') !== $key) {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized statement approval.');
        }

        $this->ledgerService->approveCustomerStatement($key);

        return redirect()->to(base_url('report/customer_ledger_for_customer/' . $key))
            ->with('success', 'Statement approved successfully.');
    }

    /**
     * Daily Report: displays unapproved counter receipts and issues side-by-side
     * with net balance calculation matching legacy CI2 interface.
     *
     * @return string|ResponseInterface
     */
    public function dailyReport()
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        // Customer role redirects to customer ledger
        if ($accessLevel === 'cust') {
            $privateKey = $spider['private_key'] ?? '';
            return redirect()->to(base_url('report/customer_ledger_for_customer/' . $privateKey));
        }

        $allowedRoles = ['admin', 'superadmin'];
        if (!in_array($accessLevel, $allowedRoles, true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Daily Report.');
        }

        $reportDate = $this->request->getVar('date');
        $dailyData  = $this->ledgerService->getDailyReportData($reportDate);

        $data = [
            'title'            => 'Daily Report - SpiderWaves',
            'breadcrumb'       => 'Daily Report',
            'or_data'          => $dailyData['or_data'],
            'or_balance'       => $dailyData['or_balance'],
            'display_balance'  => $dailyData['display_balance'],
            'total_receipt_fw' => $dailyData['total_receipt_fw'],
            'total_issue_fw'   => $dailyData['total_issue_fw'],
            'receipt_count'    => $dailyData['receipt_count'],
            'issue_count'      => $dailyData['issue_count'],
            'accessLevel'      => $accessLevel,
            'reportDate'       => $dailyData['report_date'] ?? $reportDate,
        ];

        return view('report/daily_report', $data);
    }

    /**
     * Print Daily Report: clean, printable day summary with printer auto-trigger.
     *
     * @return string|ResponseInterface
     */
    public function printDailyReport()
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        if ($accessLevel === 'cust') {
            $privateKey = $spider['private_key'] ?? '';
            return redirect()->to(base_url('report/customer_ledger_for_customer/' . $privateKey));
        }

        $allowedRoles = ['admin', 'superadmin'];
        if (!in_array($accessLevel, $allowedRoles, true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access to Print Daily Report.');
        }

        $reportDate = $this->request->getVar('date');
        $dailyData  = $this->ledgerService->getDailyReportData($reportDate);

        $data = [
            'or_data'          => $dailyData['or_data'],
            'or_balance'       => $dailyData['or_balance'],
            'display_balance'  => $dailyData['display_balance'],
            'total_receipt_fw' => $dailyData['total_receipt_fw'],
            'total_issue_fw'   => $dailyData['total_issue_fw'],
            'accessLevel'      => $accessLevel,
            'reportDate'       => $dailyData['report_date'] ?? $reportDate,
        ];

        return view('report/print_daily_report', $data);
    }

    /**
     * Export / Approve and Submit Daily Report.
     * Marks all pending transactions as approved (approved_data = '1').
     *
     * @return ResponseInterface
     */
    public function exportDailyReport()
    {
        $session = session();
        $spider  = $session->get('spider') ?? [];
        $accessLevel = $spider['access_level'] ?? '';

        if ($accessLevel === 'cust') {
            $privateKey = $spider['private_key'] ?? '';
            return redirect()->to(base_url('report/customer_ledger_for_customer/' . $privateKey));
        }

        $allowedRoles = ['admin', 'superadmin'];
        if (!in_array($accessLevel, $allowedRoles, true)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Unauthorized access.');
        }

        $approved = $this->request->getPost('approved');
        $reportDate = $this->request->getPost('date') ?? $this->request->getGet('date');
        if ($approved === 'approved') {
            $affected = $this->ledgerService->approveDailyReport($reportDate);
            return redirect()->to(base_url('report/daily_report'))->with('success', "Daily report approved and submitted successfully ({$affected} records committed).");
        }

        return redirect()->to(base_url('report/daily_report'))->with('error', 'Approval confirmation required before submission.');
    }

    /**
     * Backward-compatible alias for legacy CI2 URL report/loss_report.
     *
     * @return string|ResponseInterface
     */
    public function lossReport()
    {
        return $this->stockReport();
    }
}

