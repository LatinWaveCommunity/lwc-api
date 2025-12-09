<?php
session_start();
require_once __DIR__ . '/api/v7/config.php';

$error = '';
$success = false;
$valid_token = false;
$token = $_GET['token'] ?? '';

// Verificar token
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, email, full_name FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $valid_token = true;

            // Procesar cambio de contraseña
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = $_POST['password'] ?? '';
                $password_confirm = $_POST['password_confirm'] ?? '';

                if (empty($password)) {
                    $error = 'La contraseña es obligatoria.';
                } elseif (strlen($password) < 6) {
                    $error = 'La contraseña debe tener al menos 6 caracteres.';
                } elseif ($password !== $password_confirm) {
                    $error = 'Las contraseñas no coinciden.';
                } else {
                    // Actualizar contraseña
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE user_id = ?");
                    $stmt->execute([$password_hash, $user['user_id']]);

                    // Log
                    $log = date('Y-m-d H:i:s') . " - PASSWORD RESET SUCCESS - user_id: {$user['user_id']}, email: {$user['email']}\n";
                    file_put_contents('password_reset_log.txt', $log, FILE_APPEND);

                    $success = true;
                }
            }
        } else {
            $error = 'El enlace de recuperación es inválido o ha expirado.';
        }
    } catch (Exception $e) {
        $error = 'Error al procesar la solicitud.';
    }
} else {
    $error = 'Token de recuperación no proporcionado.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Latin Wave Community</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Source+Sans+Pro:wght@400;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Source Sans Pro', sans-serif;
            background: #0a0a0a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f2e6c9;
        }

        .video-background {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            z-index: 0;
            opacity: 0.4;
            object-fit: cover;
        }

        .container {
            background: rgba(15, 15, 15, 0.95);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            max-width: 450px;
            width: 90%;
            border: 1px solid rgba(180, 138, 100, 0.2);
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, rgba(180, 138, 100, 0.3), rgba(166, 124, 82, 0.2));
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(180, 138, 100, 0.2);
        }

        .logo {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: rgba(180, 138, 100, 0.2) url('https://i.imgur.com/Om6tGeX.png') center center;
            background-size: contain;
            background-repeat: no-repeat;
            margin: 0 auto 15px;
            border: 2px solid rgba(180, 138, 100, 0.4);
        }

        .title {
            font-family: 'Cinzel', serif;
            font-size: 1.5rem;
            color: #f2e6c9;
        }

        .form-section { padding: 30px; }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #b48a64;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            border: 2px solid rgba(180, 138, 100, 0.3);
            border-radius: 6px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.95);
            color: #1e1e1e;
        }

        .form-group input:focus {
            outline: none;
            border-color: #b48a64;
        }

        .submit-btn {
            background: linear-gradient(135deg, #b48a64, #a67c52);
            color: #0a0a0a;
            padding: 16px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(180, 138, 100, 0.4);
        }

        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success-message {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.5);
            color: #6bff6b;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-icon {
            font-size: 50px;
            margin-bottom: 15px;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #b48a64;
            text-decoration: none;
        }

        .back-link:hover { text-decoration: underline; }

        .btn-login {
            display: inline-block;
            background: linear-gradient(135deg, #b48a64, #a67c52);
            color: #0a0a0a;
            padding: 14px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <video class="video-background" autoplay muted loop playsinline>
        <source src="https://i.imgur.com/xmTbhn6.mp4" type="video/mp4">
    </video>

    <div class="container">
        <div class="header">
            <div class="logo"></div>
            <h1 class="title">Restablecer Contraseña</h1>
        </div>

        <div class="form-section">
            <?php if ($success): ?>
                <div style="text-align: center;">
                    <div class="success-icon">✅</div>
                    <div class="success-message">
                        ¡Tu contraseña ha sido actualizada exitosamente!
                    </div>
                    <p style="color: rgba(255,255,255,0.7); margin-bottom: 20px;">
                        Ya puedes iniciar sesión con tu nueva contraseña.
                    </p>
                    <a href="login.php" class="btn-login">Ir al Login</a>
                </div>
            <?php elseif ($valid_token): ?>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <p style="color: rgba(255,255,255,0.7); margin-bottom: 20px; font-size: 0.9rem;">
                    Ingresa tu nueva contraseña.
                </p>

                <form method="POST">
                    <div class="form-group">
                        <label for="password">Nueva Contraseña</label>
                        <input type="password" id="password" name="password" required placeholder="Mínimo 6 caracteres">
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Confirmar Contraseña</label>
                        <input type="password" id="password_confirm" name="password_confirm" required placeholder="Repite tu contraseña">
                    </div>

                    <button type="submit" class="submit-btn">Cambiar Contraseña</button>
                </form>
            <?php else: ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <p style="color: rgba(255,255,255,0.7); text-align: center;">
                    El enlace de recuperación ha expirado o es inválido.<br>
                    Solicita uno nuevo.
                </p>
                <a href="forgot-password.php" class="back-link">Solicitar nuevo enlace</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
