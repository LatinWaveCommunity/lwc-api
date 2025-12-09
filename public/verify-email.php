<?php
session_start();

// Conectar a MySQL
require_once __DIR__ . '/api/v7/config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = false;
$user_data = null;

if (!empty($token)) {
    try {
        // Buscar usuario por token de verificación
        $stmt = $pdo->prepare("SELECT user_id, email, full_name, email_verified FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['email_verified'] == 1) {
                // Ya verificado, redirigir a login
                header('Location: login.php?verified=already');
                exit;
            }

            // Marcar email como verificado
            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);

            // Guardar datos en sesión para la siguiente página
            $_SESSION['pending_user_id'] = $user['user_id'];
            $_SESSION['pending_email'] = $user['email'];
            $_SESSION['pending_name'] = $user['full_name'];

            $success = true;
            $user_data = $user;

            // Redirigir a selección de perfil después de 2 segundos
            header("Refresh: 2; url=select-profile.php");

        } else {
            $error = 'Token de verificación inválido o expirado.';
        }
    } catch (Exception $e) {
        $error = 'Error al verificar el email. Intenta nuevamente.';
    }
} else {
    $error = 'No se proporcionó token de verificación.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Email - Latin Wave Community</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Source+Sans+Pro:wght@400;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Source Sans Pro', sans-serif;
            background: #0a0a0a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f2e6c9;
            position: relative;
            overflow-x: hidden;
        }

        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 0;
            opacity: 0.4;
            object-fit: cover;
        }

        .container {
            background: rgba(15, 15, 15, 0.95);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), 0 0 60px rgba(180, 138, 100, 0.1);
            overflow: hidden;
            max-width: 500px;
            width: 90%;
            text-align: center;
            border: 1px solid rgba(180, 138, 100, 0.2);
            position: relative;
            z-index: 1;
            padding: 40px;
        }

        .logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(180, 138, 100, 0.2) url('https://i.imgur.com/Om6tGeX.png') center center;
            background-size: contain;
            background-repeat: no-repeat;
            margin: 0 auto 20px;
            display: block;
            border: 2px solid rgba(180, 138, 100, 0.4);
            box-shadow: 0 4px 20px rgba(180, 138, 100, 0.3);
        }

        .title {
            font-family: 'Cinzel', serif;
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #f2e6c9;
        }

        .success-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .error-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .message {
            font-size: 1.1rem;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .success-message {
            color: #10B981;
        }

        .error-message {
            color: #EF4444;
        }

        .redirect-notice {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            margin-top: 20px;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #b48a64 0%, #a67c52 100%);
            color: #0a0a0a;
            padding: 14px 30px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 20px;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(180, 138, 100, 0.4);
        }

        .loader {
            border: 3px solid rgba(255, 255, 255, 0.1);
            border-top: 3px solid #b48a64;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <video class="video-background" autoplay muted loop playsinline>
        <source src="https://i.imgur.com/xmTbhn6.mp4" type="video/mp4">
    </video>

    <div class="container">
        <div class="logo"></div>

        <?php if ($success): ?>
            <div class="success-icon">✅</div>
            <h1 class="title">¡Email Verificado!</h1>
            <p class="message success-message">
                Tu email ha sido verificado exitosamente.<br>
                Ahora configura tu perfil para continuar.
            </p>
            <div class="loader"></div>
            <p class="redirect-notice">Redirigiendo a selección de perfil...</p>
            <a href="select-profile.php" class="btn">Continuar Ahora</a>
        <?php else: ?>
            <div class="error-icon">❌</div>
            <h1 class="title">Error de Verificación</h1>
            <p class="message error-message"><?php echo htmlspecialchars($error); ?></p>
            <a href="login.php" class="btn">Ir al Login</a>
        <?php endif; ?>
    </div>
</body>
</html>
