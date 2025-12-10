<?php
session_start();
header('Content-Type: application/json');

// Conectar a MySQL
require_once __DIR__ . '/api/v7/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recibir datos del formulario
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $sponsor = trim($_POST['patrocinador'] ?? '');
        $tracker_id = trim($_POST['tracker_id'] ?? '');
        $sponsor_detected = $_POST['sponsorDetected'] ?? 'false';
        $sponsor_source = $_POST['sponsorSource'] ?? 'manual_entry';

        // SISTEMA DE ASIGNACIÓN DE FUNDADORES
        $founders = [
            1 => [
                'id' => 'LWC520000000', // TheVoiceMan - México
                'name' => 'Rafael Briceño Avila',
                'alias' => 'TheVoiceMan'
            ],
            2 => [
                'id' => 'LWC520000001', // Panda - México
                'name' => 'Carlos Salas Gutierres',
                'alias' => 'Panda'
            ],
            3 => [
                'id' => 'LWC10000002', // Nekane - USA
                'name' => 'Nekane de Leniz',
                'alias' => 'Nekane'
            ],
            4 => [
                'id' => 'LWC520000003', // Coco - México
                'name' => 'Maria del Socorro Barrera Gómez',
                'alias' => 'Coco'
            ]
        ];

        // Verificar si necesita asignación automática de fundador
        if ($sponsor === 'N/A' || empty($sponsor)) {
            // Obtener contador de fundadores
            $founder_counter_file = 'founder_counter.txt';
            $founder_counter = 1;

            if (file_exists($founder_counter_file)) {
                $founder_counter = (int)file_get_contents($founder_counter_file);
            }

            // Asignar fundador en rotación (1-4)
            $founder_index = (($founder_counter - 1) % 4) + 1;
            $assigned_founder = $founders[$founder_index];

            // Actualizar sponsor con fundador asignado
            $sponsor = $assigned_founder['id'];
            $sponsor_detected = 'false';
            $sponsor_source = 'founder_assignment';

            // Incrementar contador para próxima asignación
            $founder_counter++;
            file_put_contents($founder_counter_file, $founder_counter);

            // Log de asignación
            $assignment_log = date('Y-m-d H:i:s') . " - Lead sin sponsor asignado a: " . $assigned_founder['alias'] . " (" . $assigned_founder['id'] . ")\n";
            file_put_contents('founder_assignments.log', $assignment_log, FILE_APPEND);
        }

        // Validaciones básicas
        if (empty($nombre) || empty($email) || empty($whatsapp) || empty($sponsor)) {
            throw new Exception('Todos los campos son obligatorios');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }

        // Verificar email duplicado en MySQL
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Este email ya está registrado');
        }

        // Extraer código de país del número de WhatsApp
        $country_code = '52'; // Default: México
        if (preg_match('/^\+(\d{1,3})/', $whatsapp, $matches)) {
            $country_code = $matches[1];
        }

        // Generar LWC ID secuencial con código de país
        $counter_file = 'lwc_counter.txt';
        $counter = 1;

        if (file_exists($counter_file)) {
            $counter = (int)file_get_contents($counter_file);
        }

        $lwc_id = 'LWC' . $country_code . str_pad($counter, 7, '0', STR_PAD_LEFT);

        // Incrementar contador
        file_put_contents($counter_file, $counter + 1);

        // Generar token de verificación
        $verification_token = bin2hex(random_bytes(32));

        // Buscar sponsor_id en MySQL
        $sponsor_id = null;
        $sponsor_email = null;
        $sponsor_name = null;
        if (!empty($sponsor) && $sponsor !== 'N/A') {
            $stmt = $pdo->prepare("SELECT user_id, email, full_name FROM users WHERE lwc_id = ?");
            $stmt->execute([$sponsor]);
            $sponsor_row = $stmt->fetch();
            if ($sponsor_row) {
                $sponsor_id = $sponsor_row['user_id'];
                $sponsor_email = $sponsor_row['email'];
                $sponsor_name = $sponsor_row['full_name'];
            }
        }

        // ============================================
        // GUARDAR EN MYSQL (FUENTE PRINCIPAL)
        // ============================================
        $stmt = $pdo->prepare("
            INSERT INTO users (
                lwc_id,
                email,
                full_name,
                phone,
                password_hash,
                user_type,
                sponsor_id,
                registration_date,
                is_active,
                email_verified,
                verification_token
            ) VALUES (?, ?, ?, ?, '', 'pending', ?, NOW(), 1, 0, ?)
        ");
        $stmt->execute([$lwc_id, $email, $nombre, $whatsapp, $sponsor_id, $verification_token]);
        $mysql_user_id = $pdo->lastInsertId();

        // Log MySQL success
        $mysql_log = date('Y-m-d H:i:s') . " - MySQL INSERT OK - user_id: $mysql_user_id, lwc_id: $lwc_id, email: $email\n";
        file_put_contents('mysql_log.txt', $mysql_log, FILE_APPEND);

        // ============================================
        // GUARDAR EN TABLA REFERRALS
        // ============================================
        if ($sponsor_id) {
            $stmt_referral = $pdo->prepare("
                INSERT INTO referrals (
                    referrer_id,
                    referred_id,
                    referral_date,
                    referral_status
                ) VALUES (?, ?, NOW(), 'pending')
            ");
            $stmt_referral->execute([$sponsor_id, $mysql_user_id]);

            // Log referral
            $referral_log = date('Y-m-d H:i:s') . " - REFERRAL SAVED - sponsor_id: $sponsor_id, new_user_id: $mysql_user_id, lwc_id: $lwc_id\n";
            file_put_contents('referral_log.txt', $referral_log, FILE_APPEND);
        }

        // ============================================
        // ENVIAR EMAIL AL PATROCINADOR
        // ============================================
        $sponsor_notified = false;
        if ($sponsor_email && $sponsor_name) {
            $sponsor_subject = "🎉 Nuevo Referido en Latin Wave Community";

            $sponsor_body = "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                    .info-box { background: white; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0; border-radius: 0 5px 5px 0; }
                    .highlight { color: #667eea; font-weight: bold; }
                    .button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                    .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🎉 ¡Felicidades!</h1>
                        <p>Tienes un nuevo referido</p>
                    </div>
                    <div class='content'>
                        <p>Hola <strong>$sponsor_name</strong>,</p>

                        <p>¡Excelentes noticias! Una nueva persona se ha registrado en Latin Wave Community usando tu CORE LINK.</p>

                        <div class='info-box'>
                            <strong>📋 Datos del nuevo referido:</strong><br><br>
                            <strong>Nombre:</strong> $nombre<br>
                            <strong>Email:</strong> $email<br>
                            <strong>WhatsApp:</strong> $whatsapp<br>
                            <strong>CORE LINK ID:</strong> <span class='highlight'>$lwc_id</span><br>
                            <strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "
                        </div>

                        <p><strong>💡 Recomendación:</strong> Contacta a tu nuevo referido para darle la bienvenida y ayudarle a comenzar con éxito en la comunidad.</p>

                        <p style='text-align: center;'>
                            <a href='https://latinwave.org/login.php' class='button'>VER MI DASHBOARD</a>
                        </p>

                        <p>¡Sigue compartiendo tu CORE LINK para hacer crecer tu red!</p>

                        <p>Saludos,<br>
                        <strong>El equipo de Latin Wave Community</strong></p>
                    </div>
                    <div class='footer'>
                        <p>© 2024 Latin Wave Community. Todos los derechos reservados.</p>
                        <p>Este es un mensaje automático, no responder a este correo.</p>
                    </div>
                </div>
            </body>
            </html>";

            $sponsor_headers = "MIME-Version: 1.0" . "\r\n";
            $sponsor_headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $sponsor_headers .= "From: Latin Wave Community <noreply@latinwave.org>" . "\r\n";

            if (mail($sponsor_email, $sponsor_subject, $sponsor_body, $sponsor_headers)) {
                $sponsor_notified = true;
            }

            // Log notificación al sponsor
            $sponsor_log = date('Y-m-d H:i:s') . " - SPONSOR NOTIFICATION - sponsor: $sponsor_email | new_user: $email | sent: " . ($sponsor_notified ? 'YES' : 'NO') . "\n";
            file_put_contents('sponsor_notification_log.txt', $sponsor_log, FILE_APPEND);
        }

        // ============================================
        // GUARDAR EN JSON (BACKUP + DATOS EXTRA)
        // ============================================
        $lead_data = [
            'lwc_id' => $lwc_id,
            'mysql_user_id' => $mysql_user_id,
            'nombre' => $nombre,
            'email' => $email,
            'whatsapp' => $whatsapp,
            'sponsor' => $sponsor,
            'tracker_id' => $tracker_id,
            'sponsor_detected' => $sponsor_detected,
            'sponsor_source' => $sponsor_source,
            'verification_token' => $verification_token,
            'verified' => false,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        $leads_file = 'leads.json';
        $leads = [];

        if (file_exists($leads_file)) {
            $leads = json_decode(file_get_contents($leads_file), true) ?? [];
        }

        $leads[] = $lead_data;
        file_put_contents($leads_file, json_encode($leads, JSON_PRETTY_PRINT));

        // Crear enlace de verificación
        $verification_link = "https://latinwave.org/verify-email.php?token=" . $verification_token;

        // Preparar email HTML
        $email_subject = "✅ Confirma tu registro en Latin Wave Community - ID: $lwc_id";

        $email_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #a67c52, #8b6914); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .logo { max-width: 200px; margin-bottom: 20px; }
                .button { display: inline-block; background: #a67c52; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .info-box { background: white; border-left: 4px solid #a67c52; padding: 15px; margin: 20px 0; }
                .footer { text-align: center; color: #666; font-size: 12px; margin-top: 30px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>¡Bienvenido a Latin Wave Community!</h1>
                    <p>Tu registro está casi completo</p>
                </div>
                <div class='content'>
                    <p>Hola <strong>$nombre</strong>,</p>

                    <p>¡Gracias por unirte a Latin Wave Community! Tu registro ha sido procesado exitosamente.</p>

                    <div class='info-box'>
                        <strong>📋 Detalles de tu registro:</strong><br>
                        <strong>ID LWC:</strong> $lwc_id<br>
                        <strong>Nombre:</strong> $nombre<br>
                        <strong>Email:</strong> $email<br>
                        <strong>WhatsApp:</strong> $whatsapp<br>
                        <strong>Patrocinador:</strong> $sponsor
                    </div>

                    <p><strong>🔥 Último paso:</strong> Confirma tu email haciendo clic en el botón siguiente:</p>

                    <p style='text-align: center;'>
                        <a href='$verification_link' class='button'>CONFIRMAR MI REGISTRO</a>
                    </p>

                    <p><strong>⚠️ Importante:</strong> Este enlace expira en 24 horas. Si no confirmas tu registro, no podrás acceder a tu cuenta.</p>

                    <p>Después de confirmar tu email, podrás:</p>
                    <ul>
                        <li>✅ Acceder a tu dashboard personalizado</li>
                        <li>🎯 Configurar tu perfil de negocio</li>
                        <li>🚀 Comenzar a generar ingresos</li>
                        <li>🤝 Conectar con tu red de afiliados</li>
                    </ul>

                    <div class='info-box'>
                        <strong>🔗 ¿Problemas con el botón?</strong><br>
                        Copia y pega este enlace en tu navegador:<br>
                        $verification_link
                    </div>

                    <p>¡Estamos emocionados de tenerte en nuestra comunidad!</p>

                    <p>Saludos,<br>
                    <strong>El equipo de Latin Wave Community</strong></p>
                </div>
                <div class='footer'>
                    <p>© 2024 Latin Wave Community. Todos los derechos reservados.</p>
                    <p>Si no solicitaste este registro, puedes ignorar este email.</p>
                </div>
            </div>
        </body>
        </html>";

        // Headers para email HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Latin Wave Community <noreply@latinwave.org>" . "\r\n";
        $headers .= "Reply-To: support@latinwave.org" . "\r\n";

        // Enviar email
        $email_sent = false;

        // Intentar con mail() de SiteGround
        if (mail($email, $email_subject, $email_body, $headers)) {
            $email_sent = true;
            $email_method = 'siteground_mail';
        }

        // Log del envío
        $log_entry = date('Y-m-d H:i:s') . " - LWC ID: $lwc_id | Email: $email | Sent: " . ($email_sent ? 'YES' : 'NO') . " | Method: " . ($email_method ?? 'failed') . " | MySQL: YES | Referral: " . ($sponsor_id ? 'YES' : 'NO') . " | Sponsor Notified: " . ($sponsor_notified ? 'YES' : 'NO') . "\n";
        file_put_contents('email_log.txt', $log_entry, FILE_APPEND);

        // Respuesta exitosa
        $response = [
            'success' => true,
            'message' => 'Registro completado exitosamente',
            'lwc_id' => $lwc_id,
            'email_sent' => $email_sent,
            'verification_required' => true,
            'mysql_saved' => true,
            'referral_saved' => $sponsor_id ? true : false,
            'sponsor_notified' => $sponsor_notified
        ];

        // Si se asignó fundador, incluir en respuesta
        if (isset($assigned_founder)) {
            $response['sponsor_assigned'] = $assigned_founder['alias'];
            $response['sponsor_id'] = $assigned_founder['id'];
        }

        echo json_encode($response);

    } catch (Exception $e) {
        // Log de errores
        $error_log = date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        file_put_contents('error_log.txt', $error_log, FILE_APPEND);

        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>
