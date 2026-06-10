<?php

namespace App\Controllers;

use App\Models\ContactsModel;
use App\Models\UsersModel;
use CodeIgniter\Controller;

class Kyc extends Controller
{
    public function index()
    {
        if (! session()->get('logged_in')) {
            return redirect()->to('/signin');
        }

        $modelUsers = new UsersModel();
        $modelContacts = new ContactsModel();
        $user = $modelUsers->find(session()->get('id'));
        $imagePath = ! empty($user['image']) ? site_url('uploads/users/' . $user['image']) : site_url('assets/img/avatar.jpg');

        $data = [
            'page' => [
                'title' => 'Verificación KYC',
            ],
            'validation' => \Config\Services::validation(),
            'contentPage' => view('users/kyc_page', [
                'user'      => $user,
                'contacts'  => $modelContacts->findAll(),
                'imagePath' => $imagePath,
            ]),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setBody($data['contentPage']);
        }

        return view('layout/index', $data);
    }

    public function submit()
    {
        if (! session()->get('logged_in')) {
            return redirect()->to('/signin');
        }

        $front  = $this->request->getFile('kyc_front');
        $back   = $this->request->getFile('kyc_back');
        $selfie = $this->request->getFile('kyc_selfie');

        if (! $front || ! $front->isValid() || ! $back || ! $back->isValid() || ! $selfie || ! $selfie->isValid()) {
            return redirect()->to('/kyc')->with('error', 'Debe subir las 3 imágenes: frente, reverso y selfie con el documento en la barbilla.');
        }

        $uploadPath = FCPATH . 'uploads/kyc';
        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $userId = session()->get('id');
        $timestamp = time();
        $frontName  = 'front_' . $userId . '_' . $timestamp . '.' . $front->getExtension();
        $backName   = 'back_' . $userId . '_' . $timestamp . '.' . $back->getExtension();
        $selfieName = 'selfie_' . $userId . '_' . $timestamp . '.' . $selfie->getExtension();

        $front->move($uploadPath, $frontName);
        $back->move($uploadPath, $backName);
        $selfie->move($uploadPath, $selfieName);

        $modelUsers = new UsersModel();
        $modelUsers->update($userId, [
            'kyc_front'        => $frontName,
            'kyc_back'         => $backName,
            'kyc_selfie'       => $selfieName,
            'kyc_status'       => 'pending',
            'kyc_observations' => null,
        ]);

        return redirect()->to('/kyc')->with('success', 'Documentos enviados (frente, reverso y selfie). Pendiente de revisión.');
    }
}
