<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * Sirve archivos de /uploads/... cuando el estático falla (Hostinger:
 * archivo en otra carpeta, rewrite a index.php, CDN).
 */
class Uploads extends Controller
{
    /** Carpetas públicas permitidas */
    private array $allowedFolders = [
        'users',
        'banks',
        'covers',
        'videos',
        'system',
        'kyc',
        'vouchers',
    ];

    public function file(string $folder = '', string $filename = '')
    {
        helper('bingo');

        $folder = strtolower(trim($folder));
        if (! in_array($folder, $this->allowedFolders, true)) {
            return $this->missing();
        }

        $path = bingo_upload_resolve($folder, $filename);
        if ($path === '') {
            // Avatar: fallback en vez de 404 roto
            if ($folder === 'users') {
                $fallback = FCPATH . 'assets/img/avatar.jpg';
                if (is_file($fallback)) {
                    return $this->respondFile($fallback, 'image/jpeg');
                }
            }

            return $this->missing();
        }

        return $this->respondFile($path);
    }

    private function respondFile(string $path, ?string $mime = null)
    {
        if ($mime === null) {
            $mime = 'application/octet-stream';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detected = finfo_file($finfo, $path);
                    finfo_close($finfo);
                    if (is_string($detected) && $detected !== '') {
                        $mime = $detected;
                    }
                }
            } elseif (function_exists('mime_content_type')) {
                $detected = @mime_content_type($path);
                if (is_string($detected) && $detected !== '') {
                    $mime = $detected;
                }
            }

            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $map = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
            ];
            if (isset($map[$ext])) {
                $mime = $map[$ext];
            }
        }

        return $this->response
            ->setStatusCode(200)
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody((string) file_get_contents($path));
    }

    private function missing()
    {
        return $this->response->setStatusCode(404)->setBody('Not found');
    }
}
