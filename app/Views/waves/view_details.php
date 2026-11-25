<?php if (!empty($record)): ?>
    <div style="display: flex; flex-direction: column; gap: 14px;">
        <!-- Header Info -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Transaction ID</span>
                <div style="font-size: 18px; font-weight: 700; color: #0f172a;">#<?= esc($record['in_out_id']) ?></div>
            </div>
            <div style="text-align: right;">
                <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600;">Date</span>
                <div style="font-size: 14px; font-weight: 600; color: #0f172a;">
                    <?= !empty($record['entry_date']) ? date('d-M-Y', strtotime($record['entry_date'])) : '—' ?>
                </div>
            </div>
        </div>

        <!-- Mode & Type Badges -->
        <div style="display: flex; gap: 8px; align-items: center;">
            <?php if ($record['transactional_mode'] === 'MT'): ?>
                <span class="sw-pill-badge" style="background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 12px; padding: 4px 10px;">
                    Mode: <strong>MT (Fine Gold)</strong>
                </span>
            <?php elseif ($record['transactional_mode'] === 'OR'): ?>
                <span class="sw-pill-badge" style="background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 12px; padding: 4px 10px;">
                    Mode: <strong>OR (Metal Weight)</strong>
                </span>
            <?php else: ?>
                <span class="sw-pill-badge" style="background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a; font-size: 12px; padding: 4px 10px;">
                    Mode: <strong>RK (Cash / Rupee)</strong>
                </span>
            <?php endif; ?>

            <?php if ($record['transactional_type'] === 'receipt'): ?>
                <span class="sw-pill-badge" style="background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 12px; padding: 4px 10px;">
                    <i class="icon-arrow-down5"></i> Receipt (Inward)
                </span>
            <?php else: ?>
                <span class="sw-pill-badge sw-badge-danger" style="font-size: 12px; padding: 4px 10px;">
                    <i class="icon-arrow-up5"></i> Issue (Outward)
                </span>
            <?php endif; ?>

            <?php if ($record['approved_data'] === '1'): ?>
                <span class="sw-pill-badge sw-badge-success" style="font-size: 11px; margin-left: auto;">Approved</span>
            <?php else: ?>
                <span class="sw-pill-badge sw-badge-warning" style="font-size: 11px; margin-left: auto;">Pending</span>
            <?php endif; ?>
        </div>

        <!-- Details Grid -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <div>
                <span style="font-size: 11px; color: #64748b; font-weight: 500;">Customer:</span>
                <div style="font-weight: 600; color: #0f172a;"><?= esc($record['cust_name'] ?? '—') ?></div>
            </div>
            <div>
                <span style="font-size: 11px; color: #64748b; font-weight: 500;">Account Type:</span>
                <div style="font-weight: 600; color: #0f172a;"><?= strtoupper(esc($record['cust_type'] ?? 'Customer')) ?></div>
            </div>

            <?php if ($record['transactional_mode'] !== 'RK'): ?>
                <div>
                    <span style="font-size: 11px; color: #64748b; font-weight: 500;">Gross Weight:</span>
                    <div style="font-weight: 600; color: #0f172a;"><?= view_round_up($record['gross_weight']) ?> g</div>
                </div>
                <div>
                    <span style="font-size: 11px; color: #64748b; font-weight: 500;">Net Weight:</span>
                    <div style="font-weight: 600; color: #0f172a;"><?= view_round_up($record['net_weight']) ?> g</div>
                </div>
                <div>
                    <span style="font-size: 11px; color: #64748b; font-weight: 500;">Touch / Purity:</span>
                    <div style="font-weight: 600; color: #0f172a;"><?= view_round_up($record['purity'], 2) ?> %</div>
                </div>
                <div>
                    <span style="font-size: 11px; color: #64748b; font-weight: 500;">Wastage:</span>
                    <div style="font-weight: 600; color: #0f172a;"><?= view_round_up($record['wastage'], 2) ?> %</div>
                </div>
                <div style="grid-column: span 2; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px; font-weight: 600; color: #1e40af;">Fine Gold Weight:</span>
                    <strong style="font-size: 16px; color: #2563eb;"><?= view_round_up($record['fine_weight']) ?> g</strong>
                </div>
            <?php else: ?>
                <div style="grid-column: span 2; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13px; font-weight: 600; color: #92400e;">Cash Amount:</span>
                    <strong style="font-size: 18px; color: #d97706;">&#8377; <?= number_format((float)$record['amount'], 2) ?></strong>
                </div>
            <?php endif; ?>

            <div style="grid-column: span 2;">
                <span style="font-size: 11px; color: #64748b; font-weight: 500;">Remark:</span>
                <div style="font-weight: 500; color: #0f172a; margin-top: 2px;"><?= esc($record['remark'] ?: '—') ?></div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
            <span style="font-size: 11px; color: #94a3b8; font-family: monospace;">Key: <?= esc($record['private_key']) ?></span>
            <a href="<?= base_url('waves/edit_in_out/' . $record['private_key']) ?>" class="sw-btn-primary" style="height: 36px; padding: 0 14px; font-size: 12px;">
                <i class="icon-pencil5"></i>
                <span>Edit Record</span>
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">No transaction record found.</div>
<?php endif; ?>
