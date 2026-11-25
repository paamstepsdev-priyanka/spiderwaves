<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Display login page or redirect if already authenticated with valid session.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|string
     */
    public function index()
    {
        $session = session();
        $sessionData = $session->get('spider');

        // Server-side verification for existing session
        if (!empty($sessionData) && is_array($sessionData)) {
            $isValid = true;
            $accessLevel = $sessionData['access_level'] ?? '';
            $userId = $sessionData['user_id'] ?? $sessionData['id'] ?? null;

            // Verify user in database if user_id is provided
            if (!empty($userId) && is_numeric($userId)) {
                $user = $this->userModel->find($userId);
                if ($user && ($user['status'] ?? '0') !== '1') {
                    $isValid = false;
                }
            } elseif ($accessLevel === 'cust' && !empty($sessionData['private_key'])) {
                $db = \Config\Database::connect('default');
                $cust = $db->table('mst_customer')->where('private_key', $sessionData['private_key'])->get()->getRowArray();
                if ($cust && ($cust['status'] ?? '0') !== '1') {
                    $isValid = false;
                }
            }

            if ($isValid && !empty($accessLevel)) {
                // If user is already authenticated with valid access, redirect to authorized landing page
                if ($accessLevel === 'user') {
                    return redirect()->to(base_url('raw-waves'));
                }

                if ($accessLevel === 'cust') {
                    $privateKey = $sessionData['private_key'] ?? '';
                    return redirect()->to(base_url('report/customer_ledger_for_customer/' . $privateKey));
                }

                if (in_array($accessLevel, [
                    'admin',
                    'superadmin',
                    'process_manager',
                    'jr_process_manager',
                    'locker',
                    'g58',
                    'dispatch_entry',
                    'dispatch_verify',
                ], true)) {
                    return redirect()->to(base_url('dashboard'));
                }
            } else {
                // Invalid or deactivated session - purge immediately
                $session->remove('spider');
                $session->destroy();
            }
        }

        // Determine error message from session flashdata or query parameter
        $errorMessage = $session->getFlashdata('error');
        $hasError = !empty($errorMessage);

        if (!$hasError && $this->request->getGet('error') !== null) {
            $hasError = true;
            $errorType = $this->request->getGet('error');
            if ($errorType === 'expired' || $errorType === 'session') {
                $errorMessage = 'Your session has expired. Please sign in again to continue.';
            } elseif ($errorType === 'unauthorized' || $errorType === 'login_required') {
                $errorMessage = 'Please sign in to access the requested page.';
            } else {
                $errorMessage = 'Invalid username or password.';
            }
        }

        return view('auth/login', [
            'hasError'     => $hasError,
            'errorMessage' => $errorMessage,
        ]);
    }

    /**
     * Handle authentication credentials and session initialization.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function authenticate()
    {
        $username = (string) $this->request->getPost('user_name');
        $password = (string) $this->request->getPost('password');

        if (empty($username) || empty($password)) {
            return redirect()->to(base_url('login?error=0'));
        }

        $user = $this->userModel->verifyCredentials($username, $password);

        if (!$user) {
            return redirect()->to(base_url('login?error=0'));
        }

        // Fetch latest stock point timestamp for session scoping
        $stockPoint = $this->userModel->getLatestStockPoint();
        $stockPointStartDateTime = $stockPoint['date_time'] ?? '';

        $loginData = [
            'private_key'                 => $user['private_key'],
            'user'                        => $user['username'],
            'user_id'                     => (int) $user['user_id'],
            'access_level'                => $user['access_level'],
            'location'                    => $user['location'],
            'transactional_mode'          => '',
            'transaction_type'            => '',
            'stock_point_id'              => '',
            'stock_point_start_date_time' => $stockPointStartDateTime,
            'stock_point_end_date_time'   => '',
        ];

        session()->set('spider', $loginData);

        // Role-based redirection identical to CI3
        $accessLevel = $user['access_level'];

        if (in_array($accessLevel, [
            'admin',
            'superadmin',
            'process_manager',
            'jr_process_manager',
            'locker',
            'g58',
        ], true)) {
            return redirect()->to(base_url('dashboard'));
        }

        if ($accessLevel === 'cust') {
            return redirect()->to(base_url('report/customer_ledger_for_customer/' . $user['private_key']));
        }

        return redirect()->to(base_url('raw-waves'));
    }

    /**
     * Destroy user session and redirect to login page.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function destroy()
    {
        $session = session();
        $session->remove('spider');
        $session->destroy();

        return redirect()->to(base_url('login'));
    }
}
