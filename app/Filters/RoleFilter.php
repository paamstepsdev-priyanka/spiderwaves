<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    /**
     * Check if user possesses required role for the requested route.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments Allowed roles passed as filter arguments (e.g. ['admin', 'superadmin'])
     * @return \CodeIgniter\HTTP\RedirectResponse|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

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
        $userRole    = $sessionData['access_level'] ?? '';
        $userId      = !empty($sessionData['user_id']) ? (int)$sessionData['user_id'] : null;

        // Super Admin possesses full master bypass
        if ($userRole === 'superadmin') {
            return;
        }

        // Check if dynamic permission governs this route
        $uri = trim($request->getUri()->getPath(), '/');
        $permCode = null;
        if (str_starts_with($uri, 'raw-waves') || str_starts_with($uri, 'waves')) {
            $permCode = 'raw_waves.view';
        } elseif (str_starts_with($uri, 'locker')) {
            $permCode = 'locker.view';
        } elseif (str_starts_with($uri, 'loss')) {
            $permCode = 'loss.view';
        } elseif (str_starts_with($uri, 'stock') || str_starts_with($uri, 'report/stock') || str_starts_with($uri, 'report/loss_report')) {
            $permCode = 'stock.view';
        }

        if ($permCode !== null) {
            if (!can($permCode, $userId)) {
                if ($request->isAJAX()) {
                    return service('response')
                        ->setStatusCode(403)
                        ->setJSON(['status' => false, 'error' => 'Unauthorized access: You do not have permission for this module.']);
                }
                $session->setFlashdata('error', 'Unauthorized access: You do not have permission for this module.');
                return redirect()->to(base_url('dashboard'));
            }
            return; // Explicitly granted by dynamic RBAC
        }

        // If specific roles were provided in route definition, verify role membership
        if (!empty($arguments) && !in_array($userRole, $arguments, true)) {
            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON(['status' => false, 'error' => 'Unauthorized access to requested resource.']);
            }

            $session->setFlashdata('error', 'Unauthorized access to the requested module.');

            // Redirect unauthorized roles to their designated landing pages
            if ($userRole === 'user') {
                return redirect()->to(base_url('raw-waves'));
            }

            if ($userRole === 'cust') {
                $privateKey = $sessionData['private_key'] ?? '';
                return redirect()->to(base_url('report/customer_ledger_for_customer/' . $privateKey));
            }

            return redirect()->to(base_url('dashboard'));
        }
    }

    /**
     * Post-processing.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing required
    }
}
