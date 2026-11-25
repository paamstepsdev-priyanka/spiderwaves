<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Check if user is authenticated with a valid 'spider' session.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     * @return \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\ResponseInterface|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // 1. Verify existence of 'spider' session
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

        // 2. Validate session structure
        if (!is_array($sessionData) || empty($sessionData['access_level'])) {
            $session->remove('spider');
            $session->destroy();

            if ($request->isAJAX()) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'status'   => false,
                        'error'    => 'Session invalid. Please sign in again.',
                        'redirect' => base_url('login'),
                    ]);
            }

            $session->setFlashdata('error', 'Your session is invalid. Please sign in again.');
            return redirect()->to(base_url('login'));
        }

        // 3. Server-side account verification against database
        $userId = $sessionData['user_id'] ?? $sessionData['id'] ?? null;
        if (!empty($userId) && is_numeric($userId)) {
            $userModel = new \App\Models\UserModel();
            $user = $userModel->find($userId);

            // If user record exists in DB, ensure status is active
            if ($user && ($user['status'] ?? '0') !== '1') {
                $session->remove('spider');
                $session->destroy();

                if ($request->isAJAX()) {
                    return service('response')
                        ->setStatusCode(401)
                        ->setJSON([
                            'status'   => false,
                            'error'    => 'Account deactivated. Please contact administrator.',
                            'redirect' => base_url('login'),
                        ]);
                }

                $session->setFlashdata('error', 'Your account has been deactivated. Please contact an administrator.');
                return redirect()->to(base_url('login'));
            }
        }
    }

    /**
     * Post-processing: enforce anti-caching headers on all protected responses.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     * @return ResponseInterface
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Enforce anti-caching so copied/back-button URLs cannot expose protected session data
        $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', '0');

        return $response;
    }
}
