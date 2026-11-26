<?= $this->extend('layouts/default') ?>

<?= $this->section('title') ?>Dashboard - SpiderWaves<?= $this->endSection() ?>
<?= $this->section('breadcrumb') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $sessionData = session()->get('spider') ?? [];
    $userId      = !empty($sessionData['user_id']) ? (int)$sessionData['user_id'] : null;
    $accessLevel = $sessionData['access_level'] ?? '';
    $username    = $sessionData['user'] ?? 'User';

    // Dynamically resolve permitted Process Department cards for this user (role + user overrides)
    $processAccounts = model(\App\Models\AccountModel::class)->getProcessNavigationAccounts($accessLevel, $userId);
    $canRefining     = function_exists('can')
        ? can('refining.view', $userId, $accessLevel)
        : in_array($accessLevel, ['superadmin', 'admin', 'process_manager'], true);
    $canLockerCard   = ($accessLevel === 'locker')
        || (empty($processAccounts) && function_exists('can') && can('locker.view', $userId, $accessLevel));

    // Single lightweight query for active/pending batch counts per department
    $pendingByDept = [];
    try {
        $db = \Config\Database::connect();
        $startDate = (new \App\Services\LedgerService())->getActiveStockPointDate();
        $rows = $db->query("
            SELECT to_id, COUNT(*) AS cnt
            FROM process_waves
            WHERE inner_transfer = '1'
              AND further_to_process_waves_id IS NULL
              AND date >= ?
            GROUP BY to_id
        ", [$startDate])->getResultArray();
        foreach ($rows as $r) {
            $pendingByDept[(int)$r['to_id']] = (int)$r['cnt'];
        }
    } catch (\Throwable $e) {
        $pendingByDept = [];
    }

    // Balanced enterprise palette (white card + colored top accent + verified Icomoon icons)
    $themeMap = [
        'casting'          => ['tint' => '#eff6ff', 'accent' => '#2563eb', 'icon' => 'icon-fire',          'desc' => 'Metal casting & melting'],
        'filing'           => ['tint' => '#f5f3ff', 'accent' => '#7c3aed', 'icon' => 'icon-wrench3',       'desc' => 'Surface filing & shaping'],
        'heera light'      => ['tint' => '#f0fdfa', 'accent' => '#0d9488', 'icon' => 'icon-sun3',          'desc' => 'Precision light finishing'],
        'packing'          => ['tint' => '#fff7ed', 'accent' => '#ea580c', 'icon' => 'icon-box',           'desc' => 'Final batch packaging'],
        'transfer packing' => ['tint' => '#f0fdf4', 'accent' => '#16a34a', 'icon' => 'icon-truck',         'desc' => 'Dispatch & transfer prep'],
        'polish'           => ['tint' => '#fdf2f8', 'accent' => '#db2777', 'icon' => 'icon-magic-wand',    'desc' => 'Buffing & shine finish'],
        'g58-polish'       => ['tint' => '#eef2ff', 'accent' => '#4f46e5', 'icon' => 'icon-magic-wand',    'desc' => 'G58 division polishing'],
        'g58 polish'       => ['tint' => '#eef2ff', 'accent' => '#4f46e5', 'icon' => 'icon-magic-wand',    'desc' => 'G58 division polishing'],
        'g58-filing'       => ['tint' => '#f5f3ff', 'accent' => '#7c3aed', 'icon' => 'icon-hammer-wrench', 'desc' => 'G58 division filing'],
        'g58 filing'       => ['tint' => '#f5f3ff', 'accent' => '#7c3aed', 'icon' => 'icon-hammer-wrench', 'desc' => 'G58 division filing'],
        'g58-setting'      => ['tint' => '#ecfeff', 'accent' => '#0891b2', 'icon' => 'icon-diamond',       'desc' => 'G58 stone setting'],
        'g58 setting'      => ['tint' => '#ecfeff', 'accent' => '#0891b2', 'icon' => 'icon-diamond',       'desc' => 'G58 stone setting'],
        'g58-transfer'     => ['tint' => '#ecfdf5', 'accent' => '#059669', 'icon' => 'icon-loop3',         'desc' => 'G58 batch handoff'],
        'g58 transfer'     => ['tint' => '#ecfdf5', 'accent' => '#059669', 'icon' => 'icon-loop3',         'desc' => 'G58 batch handoff'],
        'po check'         => ['tint' => '#fffbeb', 'accent' => '#d97706', 'icon' => 'icon-clipboard5',    'desc' => 'Order & quality check'],
        'galai'            => ['tint' => '#f1f5f9', 'accent' => '#475569', 'icon' => 'icon-droplet',       'desc' => 'Alloy melting & galai'],
        'tash'             => ['tint' => '#f0f9ff', 'accent' => '#0284c7', 'icon' => 'icon-lab',           'desc' => 'Purity & tash testing'],
        'sapphire'         => ['tint' => '#eff6ff', 'accent' => '#1d4ed8', 'icon' => 'icon-star-full2',    'desc' => 'Sapphire processing'],
        'megnet'           => ['tint' => '#f1f5f9', 'accent' => '#475569', 'icon' => 'icon-cog5',          'desc' => 'CNC & magnet stage'],
        'cnc'              => ['tint' => '#f1f5f9', 'accent' => '#475569', 'icon' => 'icon-cogs',          'desc' => 'CNC machining stage'],
        'rodium'           => ['tint' => '#fff1f2', 'accent' => '#e11d48', 'icon' => 'icon-contrast',      'desc' => 'Rhodium plating finish'],
        'setting'          => ['tint' => '#f0fdf4', 'accent' => '#16a34a', 'icon' => 'icon-diamond',       'desc' => 'Stone & mount setting'],
        'zero balance'     => ['tint' => '#f8fafc', 'accent' => '#64748b', 'icon' => 'icon-balance',       'desc' => 'Zero balance clearing'],
        'refining'         => ['tint' => '#fffbeb', 'accent' => '#d97706', 'icon' => 'icon-sync',          'desc' => 'Gold refining & recovery'],
        'locker'           => ['tint' => '#fef2f2', 'accent' => '#dc2626', 'icon' => 'icon-lock2',         'desc' => 'Vault & stock custody'],
    ];

    $defaultTheme = ['tint' => '#eff6ff', 'accent' => '#2563eb', 'icon' => 'icon-stack2', 'desc' => 'Process department'];

    $cards = [];
    foreach ($processAccounts as $acc) {
        $rawName = (string)($acc['account_name'] ?? 'Process');
        $label   = trim(str_replace('_', ' ', $rawName));
        $key     = strtolower($label);
        $style   = $themeMap[$key] ?? $defaultTheme;
        $acctId  = (int)($acc['account_id'] ?? 0);

        $cards[] = [
            'title'   => $label,
            'url'     => base_url('process-waves/manage/' . $acc['private_key']),
            'pending' => $pendingByDept[$acctId] ?? 0,
            'style'   => $style,
        ];
    }

    if ($canRefining && $accessLevel !== 'locker') {
        $cards[] = [
            'title'   => 'Refining',
            'url'     => base_url('refining'),
            'pending' => $pendingByDept[\App\Models\AccountModel::REFINING_ID] ?? 0,
            'style'   => $themeMap['refining'],
        ];
    }

    if ($canLockerCard) {
        $cards[] = [
            'title'   => 'Locker',
            'url'     => base_url('locker'),
            'pending' => $pendingByDept[\App\Models\AccountModel::STOCK_ID] ?? 0,
            'style'   => $themeMap['locker'],
        ];
    }
?>

<!-- Dashboard Header -->
<div class="sw-page-header" style="margin-bottom: 18px;">
    <div class="sw-page-title-box">
        <h1>Process Departments</h1>
        <p class="text-muted" style="margin-top: 3px; font-size: 13px;">
            Welcome back, <strong><?= esc($username) ?></strong> — select an authorized department to manage batches.
        </p>
    </div>
    <div class="sw-page-actions">
        <span class="sw-pill-badge active">
            <span class="sw-badge-dot"></span> <?= count($cards) ?> Active Stage<?= count($cards) === 1 ? '' : 's' ?>
        </span>
    </div>
</div>

<!-- Compact Responsive Process Cards Grid -->
<div class="row">
    <?php if (!empty($cards)): ?>
        <?php foreach ($cards as $card): ?>
            <?php $st = $card['style']; ?>
            <div class="col-lg-3 col-md-4 col-sm-6 col-xs-12" style="margin-bottom: 16px;">
                <a href="<?= $card['url'] ?>" style="text-decoration: none; display: block;">
                    <div
                        class="sw-card"
                        style="padding: 15px 16px; border-radius: 10px; border: 1px solid #e2e8f0; border-top: 3px solid <?= $st['accent'] ?>; background: #ffffff; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04); transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;"
                        onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 10px 22px rgba(15, 23, 42, 0.09)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(15, 23, 42, 0.04)';"
                    >
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 10px; background: <?= $st['tint'] ?>; color: <?= $st['accent'] ?>; display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0;">
                                <i class="<?= esc($st['icon']) ?>"></i>
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-size: 14.5px; font-weight: 700; color: #0f172a; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= esc($card['title']) ?>
                                </div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= esc($st['desc']) ?>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                            <?php if ($card['pending'] > 0): ?>
                                <span style="background: <?= $st['tint'] ?>; color: <?= $st['accent'] ?>; padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 700;">
                                    <?= (int)$card['pending'] ?> Pending
                                </span>
                            <?php else: ?>
                                <span style="background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600;">
                                    Ready
                                </span>
                            <?php endif; ?>
                            <span style="font-size: 12px; font-weight: 600; color: <?= $st['accent'] ?>;">
                                Open <i class="icon-arrow-right8" style="font-size: 11px;"></i>
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-sm-12">
            <div class="sw-card" style="padding: 36px 20px; text-align: center;">
                <i class="icon-lock2 text-muted" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                <div style="font-size: 16px; font-weight: 600; color: var(--sw-text-main);">No Process Departments Assigned</div>
                <div class="text-muted" style="font-size: 13px; margin-top: 4px;">
                    Contact your Super Admin to assign department or process permissions to your account.
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
