<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlace no disponible - REY BINGO</title>
    <link rel="icon" type="image/png" href="<?= site_url('assets/img/logo.png') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at 50% 30%, #1e1045 0%, #0d0622 70%, #070313 100%);
            min-height: 100vh;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 40px 30px;
            max-width: 480px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }
        .error-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(249, 115, 22, 0.2));
            border: 2px solid rgba(239, 68, 68, 0.4);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #f87171;
            margin-bottom: 24px;
        }
        .btn-bingo {
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: #ffffff;
            border: none;
            padding: 12px 28px;
            font-weight: 700;
            border-radius: 50px;
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.35);
            transition: all 0.25s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-bingo:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(139, 92, 246, 0.5);
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="fa-solid fa-gamepad"></i>
        </div>
        <h3 class="fw-bold mb-2">Partida no disponible</h3>
        <p class="text-white-50 mb-4" style="font-size: 0.95rem;">
            <?= esc($message ?? 'El enlace de espectador que intentas abrir ha caducado o no es válido.') ?>
        </p>
        <div class="d-flex flex-column gap-2 justify-content-center">
            <a href="<?= site_url('/') ?>" class="btn btn-bingo">
                <i class="fa-solid fa-crown"></i> Ir a REY BINGO
            </a>
            <a href="<?= site_url('signup') ?>" class="btn btn-outline-light rounded-pill py-2" style="font-weight: 600; font-size: 0.9rem;">
                Crear una cuenta gratis
            </a>
        </div>
    </div>
</body>
</html>
