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

        helper('bingo');

        $modelUsers = new UsersModel();
        $modelContacts = new ContactsModel();
        $user = $modelUsers->find(session()->get('id'));



        $imagePath = bingo_user_image_url($user);

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

        helper('bingo');

        $modelUsers = new UsersModel();
        $user = $modelUsers->find(session()->get('id'));


        $front  = $this->request->getFile('kyc_front');
        $back   = $this->request->getFile('kyc_back');
        $selfie = $this->request->getFile('kyc_selfie');

        if (! $front || ! $front->isValid() || ! $back || ! $back->isValid() || ! $selfie || ! $selfie->isValid()) {
            return redirect()->back()->with('error', 'Debe subir las 3 imágenes: frente, reverso y selfie con el documento en la barbilla.');
        }

        $userId = (int) session()->get('id');
        $timestamp = time();
        $frontName  = 'front_' . $userId . '_' . $timestamp . '.' . $front->getExtension();
        $backName   = 'back_' . $userId . '_' . $timestamp . '.' . $back->getExtension();
        $selfieName = 'selfie_' . $userId . '_' . $timestamp . '.' . $selfie->getExtension();

        // Guardar en todas las rutas candidatas (public + writable) para evitar 404 en Hostinger
        $savedFront  = bingo_upload_store_file($front, 'kyc', $frontName);
        $savedBack   = bingo_upload_store_file($back, 'kyc', $backName);
        $savedSelfie = bingo_upload_store_file($selfie, 'kyc', $selfieName);

        if ($savedFront === '' || $savedBack === '' || $savedSelfie === '') {
            log_message('error', 'KYC upload falló user=' . $userId . ' front=' . $savedFront . ' back=' . $savedBack . ' selfie=' . $savedSelfie);
            return redirect()->back()->with('error', 'No se pudieron guardar las imágenes. Intenta de nuevo o contacta soporte.');
        }

        // Verificar que al menos se resuelven por la ruta de lectura
        if (
            bingo_upload_resolve('kyc', $savedFront) === ''
            || bingo_upload_resolve('kyc', $savedBack) === ''
            || bingo_upload_resolve('kyc', $savedSelfie) === ''
        ) {
            log_message('error', 'KYC guardado pero no resoluble user=' . $userId);
            return redirect()->back()->with('error', 'Las imágenes se subieron pero no son accesibles. Contacta soporte.');
        }

        $modelUsers->update($userId, [
            'kyc_front'        => $savedFront,
            'kyc_back'         => $savedBack,
            'kyc_selfie'       => $savedSelfie,
            'kyc_status'       => 'pending',
            'kyc_observations' => null,
        ]);

        return redirect()->back()->with('success', 'Documentos enviados correctamente. Pendiente de revisión.');
    }
}
