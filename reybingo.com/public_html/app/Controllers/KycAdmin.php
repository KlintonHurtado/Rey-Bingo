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
            ->where('kyc_front IS NOT NULL', null, false)
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        $data = [
            'page' => [
                'title' => 'Revisión KYC',
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('users/kyc_admin_list', [
                'pending'   => $pending,
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
        $modelUsers->update($id, [
            'kyc_status'       => $action,
            'kyc_observations' => $observations,
        ]);

        return redirect()->to('/kycAdmin')->with('success', 'KYC actualizado correctamente.');
    }
}
