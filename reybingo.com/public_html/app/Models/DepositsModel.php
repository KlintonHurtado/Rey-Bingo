<?php

namespace App\Models;

use CodeIgniter\Model;

class DepositsModel extends Model {
    protected $table = 'deposits'; // Nombre de la tabla
    protected $primaryKey = 'id'; // Llave primaria

    // Campos permitidos para insert/update
    protected $allowedFields = ['user', 'account', 'method', 'bank', 'document', 'phone', 'reference', 'amount', 'date', 'voucher', 'observation', 'created_at', 'updated_at', 'status'];

    // Desactivar timestamps automáticos
    protected $useTimestamps = true;

    public function paypalPayment($paymentID, $paymentToken, $payerID, $paypalClientID, $paypalSecret) {
        $paypalCredentials = paypalCredentials();
        $paypalEnv = $paypalCredentials['env'];

        $paypalURL = ($paypalEnv == 'sandbox') ? 'https://api-m.sandbox.paypal.com/v1/' : 'https://api-m.paypal.com/v1/';

        // Obtener token de acceso
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $paypalURL . 'oauth2/token');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $paypalClientID . ":" . $paypalSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

        $response = curl_exec($ch);
        curl_close($ch);

        if (empty($response)) {
            return false;
        }

        $jsonData = json_decode($response);
        if (!isset($jsonData->access_token)) {
            return false;
        }

        // Obtener detalles del pago
        $curl = curl_init($paypalURL . 'payments/payment/' . $paymentID);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $jsonData->access_token,
            'Accept: application/json',
            'Content-Type: application/json'
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        // Transaction data
        $result = json_decode($response);

        // Verificar si la transacción fue aprobada
        if ($result && isset($result->status) && $result->status == 'approved' || $result->status == 'COMPLETED') {
            return [
                'reference' => $result->id, // ID de pago
                'date'      => date('Y-m-d', strtotime($result->create_time)), // Fecha de creación del pago
            ];
        }

        return false;
    }
}   