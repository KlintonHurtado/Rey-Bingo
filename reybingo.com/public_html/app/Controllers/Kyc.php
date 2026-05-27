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

        $front = $this->request->getFile('kyc_front');
        $back  = $this->request->getFile('kyc_back');

        if (! $front || ! $front->isValid() || ! $back || ! $back->isValid()) {
            return redirect()->to('/kyc')->with('error', 'Debe subir ambas imágenes (frente y reverso).');
        }

        $uploadPath = FCPATH . 'uploads/kyc';
        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $userId = session()->get('id');
        $frontName = 'front_' . $userId . '_' . time() . '.' . $front->getExtension();
        $backName  = 'back_' . $userId . '_' . time() . '.' . $back->getExtension();

        $front->move($uploadPath, $frontName);
        $back->move($uploadPath, $backName);

        $modelUsers = new UsersModel();
        $modelUsers->update($userId, [
            'kyc_front'        => $frontName,
            'kyc_back'         => $backName,
            'kyc_status'       => 'pending',
            'kyc_observations' => null,
        ]);

        return redirect()->to('/kyc')->with('success', 'Documentos enviados. Pendiente de revisión.');
    }
}
