<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    /**
     * Enforce server-side authorization for routes based on permissions.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments Permissions passed as filter arguments (e.g. ['raw_waves.view'])
     * @return \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // 1. Check authentication session
        if (!$session->has('spider') || empty($session->get('spider'))) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'status'   => false,
                        'error'    => 'Authentication required. Please sign in.',
                        'redirect' => base_url('login'),
                    ]);
            }

            $session->setFlashdata('error', 'Please sign in to access the requested page.');
            return redirect()->to(base_url('login'));
        }

        $sessionData = $session->get('spider');
        $userId      = !empty($sessionData['user_id']) ? (int)$sessionData['user_id'] : null;

        // Customer portal isolation check: customer clients use distinct customer ledger authentication
        if (($sessionData['access_level'] ?? '') === 'cust') {
            $uriString = $request->getUri()->getPath();
            if (str_contains($uriString, 'customer_ledger_for_customer') || str_contains($uriString, 'cust_approved')) {
                return; // Allowed for customer portal
            }
            $pKey = $sessionData['private_key'] ?? '';
            return redirect()->to(base_url('report/customer_ledger_for_customer/' . $pKey));
        }

        // 2. Authoritative live user database verification
        /** @var \App\Services\PermissionService $permService */
        $permService = service('permission');
        $user = $permService->resolveAuthoritativeUser($userId);

        if (!$user) {
            $session->remove('spider');
            $session->destroy();

            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'status'   => false,
                        'error'    => 'Account deactivated or invalid. Please sign in again.',
                        'redirect' => base_url('login'),
                    ]);
            }

            $session->setFlashdata('error', 'Your session is invalid or account deactivated.');
            return redirect()->to(base_url('login'));
        }

        // Super Admin possesses full master bypass
        if ($user['access_level'] === 'superadmin') {
            return;
        }

        // 3. Permission checks against arguments
        if (!empty($arguments)) {
            $hasAccess = false;
            foreach ($arguments as $permCode) {
                if ($permService->hasPermission($permCode, $user['user_id'])) {
                    $hasAccess = true;
                    break;
                }
            }

            if (!$hasAccess) {
                if ($request->isAJAX()) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON([
                            'status' => false,
                            'error'  => 'Unauthorized: Access denied for requested action.',
                        ]);
                }

                $session->setFlashdata('error', 'Unauthorized access: You do not possess permission for this page or action.');

                // Redirect to designated landing page based on authoritative role
                if ($user['access_level'] === 'user') {
                    return redirect()->to(base_url('raw-waves'));
                }

                return redirect()->to(base_url('dashboard'));
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Enforce anti-caching headers on all protected responses
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', '0');
    }
}

