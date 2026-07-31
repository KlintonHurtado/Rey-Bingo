<?php

namespace App\Controllers;

use App\Models\ContactsModel;
use App\Models\UsersModel;
use CodeIgniter\Controller;

class KycAdmin extends Controller
{
    public function index()
    {
        if (! session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $modelUsers = new UsersModel();
        $modelContacts = new ContactsModel();
        $user = $modelUsers->find(session()->get('id'));
        $imagePath = ! empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

        $pending = $modelUsers->where('kyc_status', 'pending')
            ->where('group', bingo_group_player())
            ->where('kyc_front !=', '')
            ->where('kyc_front IS NOT NULL')
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        $verified = $modelUsers->where('kyc_status', 'verified')
            ->where('group', bingo_group_player())
            ->where('kyc_front !=', '')
            ->where('kyc_front IS NOT NULL')
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        $rejected = $modelUsers->where('kyc_status', 'rejected')
            ->where('group', bingo_group_player())
            ->where('kyc_front !=', '')
            ->where('kyc_front IS NOT NULL')
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        $data = [
            'page' => [
                'title' => 'Revisión KYC',
            ],
            'user'       => $user,
            'validation' => \Config\Services::validation(),
            'contentPage' => view('users/kyc_admin_list', [
                'pending'   => $pending,
                'verified'  => $verified,
                'rejected'  => $rejected,
                'user'      => $user,
                'contacts'  => $modelContacts->findAll(),
                'imagePath' => $imagePath,
            ]),
        ];

        return view('layout/index', $data);
    }

    public function review(int $id)
    {
        if (! session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $action = $this->request->getPost('action');
        $observations = $this->request->getPost('kyc_observations');

        if (! in_array($action, ['verified', 'rejected'], true)) {
            return redirect()->back()->with('error', 'Acción no válida.');
        }

        $modelUsers = new UsersModel();
        $user = $modelUsers->find($id);
        if (! $user) {
            return redirect()->back()->with('error', translate('user not found'));
        }

        $previousStatus = (string) ($user['kyc_status'] ?? 'pending');

        $modelUsers->update($id, [
            'kyc_status'       => $action,
            'kyc_observations' => $observations,
        ]);

        if ($action === 'verified' && $previousStatus !== 'verified') {
            helper('bingo');
            bingo_activate_roulette_on_kyc_verified($id);
        }

        // Si se rechaza y aún no giró, quitar el giro pendiente.
        if ($action === 'rejected' && (int) ($user['roulette'] ?? 1) === 0) {
            $alreadyClaimed = (new \App\Models\RoulettesModel())
                ->where('user', $id)
                ->countAllResults() > 0;
            if (! $alreadyClaimed) {
                $modelUsers->update($id, ['roulette' => 1]);
            }
        }

        return redirect()->to('/kycAdmin')->with('success', 'KYC actualizado correctamente.');
    }

    public function revoke(int $id)
    {
        if (! session()->get('logged_in') || session()->get('group') != 1) {
            return redirect()->to('/signin');
        }

        $observations = trim((string) $this->request->getPost('kyc_observations'));
        $modelUsers = new UsersModel();
        $user = $modelUsers->find($id);
        if (! $user) {
            return redirect()->back()->with('error', translate('user not found'));
        }

        $modelUsers->update($id, [
            'kyc_status' => 'pending',
            'kyc_front' => null,
            'kyc_back' => null,
            'kyc_selfie' => null,
            'kyc_observations' => $observations !== ''
                ? $observations
                : translate('kyc revoked by admin'),
        ]);

        // Sin KYC no puede usar el Ruletazo pendiente.
        if ((int) ($user['roulette'] ?? 1) === 0) {
            $alreadyClaimed = (new \App\Models\RoulettesModel())
                ->where('user', $id)
                ->countAllResults() > 0;
            if (! $alreadyClaimed) {
                $modelUsers->update($id, ['roulette' => 1]);
            }
        }

        return redirect()->to('/kycAdmin')->with('success', translate('kyc verification removed'));
    }
}
