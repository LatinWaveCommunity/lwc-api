<?php
session_start();
require_once __DIR__ . '/api/v7/config.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Por favor ingresa tu email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email inválido.';
    } else {
        try {
            // Buscar usuario por email
            $stmt = $pdo->prepare("SELECT user_id, full_name, email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Generar token de recuperación
                $reset_token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Guardar token en la base de datos
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE user_id = ?");
                $stmt->execute([$reset_token, $expires, $user['user_id']]);

                // Crear enlace de recuperación
                $reset_link = "https://latinwave.org/reset-password.php?token=" . $reset_token;

                // Enviar email
                $subject = "Recuperar contraseña - Latin Wave Community";
                $body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background: linear-gradient(135deg, #a67c52, #8b6914); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                        .button { display: inline-block; background: #a67c52; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                        .info-box { background: white; border-left: 4px solid #a67c52; padding: 15px; margin: 20px 0; }
                        .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Recuperar Contraseña</h1>
                        </div>
                        <div class='content'>
                            <p>Hola <strong>{$user['full_name']}</strong>,</p>

                            <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en Latin Wave Community.</p>

                            <p style='text-align: center;'>
                                <a href='$reset_link' class='button'>RESTABLECER CONTRASEÑA</a>
                            </p>

                            <div class='info-box'>
                                <strong>⚠️ Importante:</strong><br>
                                - Este enlace expira en 1 hora.<br>
                                - Si no solicitaste este cambio, ignora este email.
                            </div>

                            <div class='info-box'>
                                <strong>¿Problemas con el botón?</strong><br>
                                Copia y pega este enlace en tu navegador:<br>
                                $reset_link
                            </div>

                            <p>Saludos,<br><strong>El equipo de Latin Wave Community</strong></p>
                        </div>
                        <div class='footer'>
                            <p>© 2024 Latin Wave Community. Todos los derechos reservados.</p>
                        </div>
                    </div>
                </body>
                </html>";

                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                $headers .= "From: Latin Wave Community <noreply@latinwave.org>\r\n";

                mail($email, $subject, $body, $headers);

                // Log
                $log = date('Y-m-d H:i:s') . " - PASSWORD RESET REQUEST - email: $email\n";
                file_put_contents('password_reset_log.txt', $log, FILE_APPEND);
            }

            // Siempre mostrar éxito para no revelar si el email existe
            $success = true;

        } catch (Exception $e) {
            $error = 'Error al procesar la solicitud. Intenta nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Latin Wave Community</title>
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
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #b48a64;
            text-decoration: none;
        }

        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <video class="video-background" autoplay muted loop playsinline>
        <source src="https://i.imgur.com/xmTbhn6.mp4" type="video/mp4">
    </video>

    <div class="container">
        <div class="header">
            <div class="logo"></div>
            <h1 class="title">Recuperar Contraseña</h1>
        </div>

        <div class="form-section">
            <?php if ($success): ?>
                <div class="success-message">
                    Si el email existe en nuestro sistema, recibirás un enlace para restablecer tu contraseña.
                </div>
                <a href="login.php" class="back-link">← Volver al Login</a>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <p style="color: rgba(255,255,255,0.7); margin-bottom: 20px; font-size: 0.9rem;">
                    Ingresa tu email y te enviaremos un enlace para restablecer tu contraseña.
                </p>

                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required placeholder="tu@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="submit-btn">Enviar Enlace</button>
                </form>

                <a href="login.php" class="back-link">← Volver al Login</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
