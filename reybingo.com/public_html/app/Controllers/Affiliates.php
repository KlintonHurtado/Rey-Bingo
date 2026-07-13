<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Affiliates extends Controller
{
    public function __construct()
    {
        helper(['form', 'url', 'cookie', 'text', 'bingo', 'affiliate_ggr']);
        session();
    }

    public function index()
    {
        return redirect()->to('/dashboard');
    }

    /** API: estadísticas GGR del PV u operador autenticado. */
    public function myStatsGet()
    {
        if (! session()->get('logged_in')) {
            return $this->response->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $beneficiaryId = (int) session()->get('id');
        $ggrRole = 'player';

        if (bingo_is_store() || (bingo_is_operator() && bingo_get_acting_store_id() > 0)) {
            $beneficiaryId = bingo_is_store() ? $beneficiaryId : bingo_get_acting_store_id();
            $ggrRole = 'store';
        } elseif (bingo_is_operator()) {
            $ggrRole = 'operator';
        } elseif ((int) session()->get('group') !== bingo_group_player()) {
            return $this->response->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $days = max(1, (int) ($this->request->getGet('days') ?? 30));

        return $this->response->setJSON([
            'success' => true,
            'stats'   => bingo_fetch_affiliate_ggr_dashboard($beneficiaryId, $ggrRole, $days),
        ]);
    }
}
