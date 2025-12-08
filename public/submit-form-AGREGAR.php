<?php
/**
 * CODIGO PARA AGREGAR A submit-form.php
 * =====================================
 *
 * Este archivo contiene el codigo que FALTA en tu submit-form.php actual
 * para que:
 * 1. Se guarden los referidos en la tabla 'referrals'
 * 2. Se envie email de notificacion al patrocinador
 *
 * INSTRUCCIONES:
 * - Busca en tu submit-form.php el lugar donde se hace el INSERT en la tabla 'users'
 * - DESPUES de ese INSERT (despues de verificar que fue exitoso), agrega el codigo de abajo
 */

// =========================================================================
// PASO 1: AGREGAR DESPUES DEL INSERT EN 'users' (despues de insertar el nuevo usuario)
// =========================================================================

// Obtener el ID del usuario recien insertado
$nuevo_usuario_id = $conn->insert_id;

// Formatear el CORE LINK ID del nuevo usuario
$nuevo_core_link_id = 'LWC52' . str_pad($nuevo_usuario_id, 7, '0', STR_PAD_LEFT);

// Verificar si tiene patrocinador (el campo viene del formulario)
$patrocinador = isset($_POST['patrocinador']) ? trim($_POST['patrocinador']) : '';

if (!empty($patrocinador) && $patrocinador !== 'N/A' && $patrocinador !== 'Directo') {

    // El patrocinador puede venir como LWC520000000 o como numero
    if (strpos($patrocinador, 'LWC') === 0) {
        // Ya viene en formato LWC, extraer el numero
        $sponsor_core_id = $patrocinador;
        // Extraer numero del final (quitar LWC52)
        $sponsor_numero = intval(substr($patrocinador, 5));
    } else {
        // Es un numero directo
        $sponsor_numero = intval($patrocinador);
        $sponsor_core_id = 'LWC52' . str_pad($sponsor_numero, 7, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    // GUARDAR EN TABLA 'referrals'
    // =========================================================================
    $sql_referral = "INSERT INTO referrals (sponsor_id, referral_id, created_at) VALUES (?, ?, NOW())";
    $stmt_referral = $conn->prepare($sql_referral);
    $stmt_referral->bind_param("ii", $sponsor_numero, $nuevo_usuario_id);
    $stmt_referral->execute();
    $stmt_referral->close();

    // =========================================================================
    // OBTENER EMAIL DEL PATROCINADOR PARA NOTIFICARLE
    // =========================================================================
    $sql_sponsor = "SELECT email, name FROM users WHERE id = ?";
    $stmt_sponsor = $conn->prepare($sql_sponsor);
    $stmt_sponsor->bind_param("i", $sponsor_numero);
    $stmt_sponsor->execute();
    $result_sponsor = $stmt_sponsor->get_result();

    if ($row_sponsor = $result_sponsor->fetch_assoc()) {
        $sponsor_email = $row_sponsor['email'];
        $sponsor_name = $row_sponsor['name'];

        // Datos del nuevo usuario (vienen del formulario)
        $nuevo_nombre = isset($_POST['name']) ? $_POST['name'] : 'Usuario';
        $nuevo_email = isset($_POST['email']) ? $_POST['email'] : '';
        $nuevo_telefono = isset($_POST['phone']) ? $_POST['phone'] : '';

        // =========================================================================
        // ENVIAR EMAIL DE NOTIFICACION AL PATROCINADOR
        // =========================================================================
        $to = $sponsor_email;
        $subject = "Nuevo Referido Registrado en Latin Wave Community";

        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #1a1a2e; color: #ffffff; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background-color: #16213e; padding: 30px; border-radius: 0 0 10px 10px; }
                .highlight { color: #00d4ff; font-weight: bold; }
                .data-box { background-color: #0f3460; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .footer { text-align: center; padding: 20px; color: #888; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Latin Wave Community</h1>
                    <p>Notificacion de Nuevo Referido</p>
                </div>
                <div class='content'>
                    <p>Hola <span class='highlight'>{$sponsor_name}</span>,</p>
                    <p>Una nueva persona se ha registrado usando tu CORE LINK. Aqui estan sus datos:</p>

                    <div class='data-box'>
                        <p><strong>Nombre:</strong> {$nuevo_nombre}</p>
                        <p><strong>Email:</strong> {$nuevo_email}</p>
                        <p><strong>Telefono:</strong> {$nuevo_telefono}</p>
                        <p><strong>CORE LINK ID:</strong> <span class='highlight'>{$nuevo_core_link_id}</span></p>
                        <p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>
                    </div>

                    <p>Te recomendamos contactar a tu nuevo referido para darle la bienvenida y ayudarle a comenzar.</p>

                    <p>Ingresa a tu dashboard para ver todos tus referidos:</p>
                    <p><a href='https://latinwave.org/dashboard' style='color: #00d4ff;'>https://latinwave.org/dashboard</a></p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automatico de Latin Wave Community</p>
                    <p>No responder a este correo</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Latin Wave Community <noreply@latinwave.org>" . "\r\n";

        mail($to, $subject, $message, $headers);
    }

    $stmt_sponsor->close();
}

// =========================================================================
// NOTA IMPORTANTE SOBRE LA TABLA 'referrals'
// =========================================================================
/*
 * Tu tabla 'referrals' debe tener al menos estas columnas:
 * - id (INT, AUTO_INCREMENT, PRIMARY KEY)
 * - sponsor_id (INT) - el ID del patrocinador
 * - referral_id (INT) - el ID del nuevo usuario
 * - created_at (DATETIME) - fecha de registro
 *
 * Si tu tabla tiene nombres de columnas diferentes, ajusta el codigo de arriba.
 *
 * Para verificar la estructura de tu tabla, ejecuta en phpMyAdmin:
 * DESCRIBE referrals;
 */

?>
