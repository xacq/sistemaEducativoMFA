<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Iniciar una transacción para asegurar que todas las actualizaciones se realicen o ninguna.
    $mysqli->begin_transaction();
    
    try {
        // Preparar la sentencia de actualización una sola vez.
        $stmt = $mysqli->prepare("UPDATE configuracion SET valor = ? WHERE llave = ?");
        
        // 1. Procesar los campos de texto y select
        if (isset($_POST['config']) && is_array($_POST['config'])) {
            foreach ($_POST['config'] as $llave => $valor) {
                // bind_param dentro del bucle para asignar nuevos valores en cada iteración
                $stmt->bind_param('ss', $valor, $llave);
                $stmt->execute();
            }
        }
        
        $stmt->close(); // Cerrar la sentencia preparada
        
        // 2. Procesar la subida del logo (si se subió uno nuevo)
        if (isset($_FILES['schoolLogo']) && $_FILES['schoolLogo']['error'] == UPLOAD_ERR_OK) {
            $directorio_subidas = '../uploads/logos/';
            if (!is_dir($directorio_subidas)) {
                mkdir($directorio_subidas, 0777, true);
            }
            
            // Generar un nombre de archivo único para evitar sobreescribir
            $nombre_archivo_logo = 'logo-' . uniqid() . '.' . pathinfo($_FILES['schoolLogo']['name'], PATHINFO_EXTENSION);
            $ruta_completa = $directorio_subidas . $nombre_archivo_logo;
            
            if (move_uploaded_file($_FILES['schoolLogo']['tmp_name'], $ruta_completa)) {
                // Guardar solo la ruta relativa en la base de datos
                $ruta_relativa = 'uploads/logos/' . $nombre_archivo_logo;
                $stmt_logo = $mysqli->prepare("UPDATE configuracion SET valor = ? WHERE llave = 'schoolLogo'");
                $stmt_logo->bind_param('s', $ruta_relativa);
                $stmt_logo->execute();
                $stmt_logo->close();
            } else {
                throw new Exception("Error al subir el nuevo logo.");
            }
        }
        
        // Si todo fue bien, confirmar los cambios
        $mysqli->commit();
        $_SESSION['config_message'] = "¡Configuración guardada exitosamente!";

    } catch (Exception $e) {
        // Si algo falla, revertir todos los cambios
        $mysqli->rollback();
        $_SESSION['config_message'] = "Error al guardar la configuración: " . $e->getMessage();
    }
    
    // Redirigir de vuelta a la página de configuración
    header('Location: director_configuracion.php');
    exit;
}
?>