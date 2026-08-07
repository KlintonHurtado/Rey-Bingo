<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\AffiliateGgrCommissionsModel;
use App\Models\ContactsModel;
use App\Models\GamesModel;
use CodeIgniter\Controller;

class Ggr extends Controller
{
    public function __construct()
    {
        helper(['form', 'url', 'cookie', 'text', 'wallet', 'bingo', 'affiliate_ggr']);
        session();
    }

    private function requireAdmin()
    {
        if (! session()->get('logged_in') || ! bingo_is_admin()) {
            return redirect()->to('/signin');
        }

        return null;
    }



    public function commissionsListGet()
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $model = new AffiliateGgrCommissionsModel();
        $modelUsers = new UsersModel();
        $modelGames = new GamesModel();
        $status = $this->request->getGet('status');

        $builder = $model->orderBy('created_at', 'DESC');
        if ($status !== null && $status !== '' && $status !== 'all') {
            $builder->where('status', (int) $status);
        }

        $rows = $builder->findAll(100);
        $currency = systemGet('currency');
        $html = '';

        foreach ($rows as $row) {
            $beneficiary = $modelUsers->find((int) $row['affiliate_id']);
            $player = $modelUsers->find((int) $row['player_id']);
            $game = $modelGames->find((int) $row['game_id']);
            $beneficiaryName = bingo_store_display_name($beneficiary ?: [])
                ?: trim(($beneficiary['firstname'] ?? '') . ' ' . ($beneficiary['lastname'] ?? ''));
            $playerName = trim(($player['firstname'] ?? '') . ' ' . ($player['lastname'] ?? ''));
            $gameLabel = $game['description'] ?? ('#' . ($row['game_id'] ?? ''));
            $roleLabel = match ((string) ($row['affiliate_type'] ?? '')) {
                'store'    => translate('point of sale'),
                'operator' => translate('operator'),
                default    => (string) ($row['affiliate_type'] ?? ''),
            };

            $statusLabel = match ((int) ($row['status'] ?? 0)) {
                2 => '<span class="badge bg-success">' . translate('paid') . '</span>',
                3 => '<span class="badge bg-danger">' . translate('rejected') . '</span>',
                default => '<span class="badge bg-warning text-dark">' . translate('pending') . '</span>',
            };

            $actions = '';
            if ((int) ($row['status'] ?? 0) === 0) {
                $actions = '<button type="button" class="btn btn-sm btn-success me-1" onclick="approveGgrCommission(' . (int) $row['id'] . ');"><i class="fa-duotone fa-check"></i></button>'
                    . '<button type="button" class="btn btn-sm btn-danger" onclick="rejectGgrCommission(' . (int) $row['id'] . ');"><i class="fa-duotone fa-xmark"></i></button>';
            }

            $html .= '<tr>'
                . '<td>' . esc($beneficiaryName) . '<br><small class="text-muted">' . esc($roleLabel) . '</small></td>'
                . '<td>' . esc($playerName) . '</td>'
                . '<td>' . esc($gameLabel) . '</td>'
                . '<td>' . $currency . ' ' . number_format((float) ($row['total_stake'] ?? 0), 2) . '</td>'
                . '<td>' . $currency . ' ' . number_format((float) ($row['total_payout'] ?? 0), 2) . '</td>'
                . '<td><strong>' . $currency . ' ' . number_format((float) ($row['ggr_amount'] ?? 0), 2) . '</strong></td>'
                . '<td>' . number_format((float) ($row['commission_rate'] ?? 0) * 100, 2) . '%</td>'
                . '<td>' . $currency . ' ' . number_format((float) ($row['commission_amount'] ?? 0), 2) . '</td>'
                . '<td>' . $statusLabel . '</td>'
                . '<td>' . esc(date('d/m/Y H:i', strtotime($row['created_at'] ?? 'now'))) . '</td>'
                . '<td>' . $actions . '</td>'
                . '</tr>';
        }

        if ($html === '') {
            $html = '<tr><td colspan="11" class="text-center text-muted py-4">' . translate('no records found') . '</td></tr>';
        }

        return $this->response->setBody($html);
    }

    public function statsGet()
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $days = max(1, (int) ($this->request->getGet('days') ?? 30));

        return $this->response->setJSON([
            'success' => true,
            'stats'   => bingo_fetch_global_ggr_stats($days),
        ]);
    }

    public function approveCommission()
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $id = (int) $this->request->getPost('id');
        $result = bingo_approve_ggr_commission($id, (int) session()->get('id'));

        return $this->response->setJSON($result);
    }

    public function rejectCommission()
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $id = (int) $this->request->getPost('id');
        $result = bingo_reject_ggr_commission($id);

        return $this->response->setJSON($result);
    }

    public function ggrSettingsSubmit()
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $validation = \Config\Services::validation();
        if (! $validation->run($this->request->getPost(), [
            'rateStoreGgrCommission' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
            'rateOperatorCommission' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[100]',
            'ggrSettlementMode'      => 'required|in_list[monthly,immediate,daily,weekly]',
        ])) {
            return $this->response->setJSON([
                'success' => false,
                'errors'  => $validation->getErrors(),
            ]);
        }

        $modelSystem = new \App\Models\SystemModel();
        $settlementMode = strtolower(trim((string) ($this->request->getPost('ggrSettlementMode') ?: 'monthly')));
        if ($settlementMode === 'immediate') {
            $settlementMode = 'daily';
        }
        if (! in_array($settlementMode, ['daily', 'weekly', 'monthly'], true)) {
            $settlementMode = 'monthly';
        }
        $settings = [
            'rateStoreGgrCommission'    => ((float) ($this->request->getPost('rateStoreGgrCommission') ?: 0)) / 100,
            'rateOperatorCommission'    => ((float) ($this->request->getPost('rateOperatorCommission') ?: 0)) / 100,
            'ggrSettlementMode'         => $settlementMode,
            'autoApproveGgrCommissions' => $settlementMode === 'daily' ? '1' : '0',
        ];

        foreach ($settings as $key => $value) {
            $existing = $modelSystem->where('key', $key)->first();
            if ($existing) {
                $modelSystem->update($existing['id'], ['value' => $value]);
            } else {
                $modelSystem->insert(['key' => $key, 'value' => $value]);
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => translate('settings updated successfully'),
        ]);
    }

    public function updateGgrRate()
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $userId = (int) $this->request->getPost('user_id');
        $ggrRole = (string) ($this->request->getPost('ggr_role') ?: $this->request->getPost('affiliate_type'));
        $rateInput = $this->request->getPost('ggr_rate');

        $model = new UsersModel();
        $user = $model->find($userId);
        if (! $user) {
            return $this->response->setJSON(['success' => false, 'message' => translate('user not found')]);
        }

        if ($ggrRole === 'operator') {
            if ((int) ($user['group'] ?? -1) !== bingo_group_operator()) {
                return $this->response->setJSON(['success' => false, 'message' => translate('invalid request')]);
            }

            $rate = ($rateInput === null || $rateInput === '')
                ? null
                : bingo_parse_store_commission_rate_post($rateInput);

            $model->update($userId, ['operator_commission_rate' => $rate]);
        } elseif ($ggrRole === 'store') {
            if ((int) ($user['group'] ?? -1) !== bingo_group_store()) {
                return $this->response->setJSON(['success' => false, 'message' => translate('invalid request')]);
            }

            $validation = bingo_validate_store_ggr_rate($rateInput, $user);
            if (! $validation['valid']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $validation['message'] ?? translate('invalid request'),
                ]);
            }

            $model->update($userId, ['ggr_commission_rate' => $validation['rate']]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => translate('invalid request')]);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => translate('settings updated successfully'),
        ]);
    }

    public function settleMonthlyGgr()
    {
        if ($redirect = $this->requireAdmin()) {
            return $redirect;
        }

        $yearMonth = trim((string) $this->request->getPost('period'));
        if ($yearMonth === '') {
            $yearMonth = null;
        }

        $result = bingo_settle_monthly_ggr_commissions($yearMonth, (int) session()->get('id'));

        return $this->response->setJSON($result);
    }
}
