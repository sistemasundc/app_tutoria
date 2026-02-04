<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
ob_start(); 
require_once('../../modelo/modelo_conexion.php');

$conexion = new conexion();
$conexion->conectar();

$id_cargalectiva = $_POST['id_cargalectiva'] ?? null;
$id_doce = $_SESSION['S_IDUSUARIO'] ?? null;
$id_car  = $_SESSION['S_SCHOOL'] ?? null; 
$mes_informe = strtolower(trim($_POST['mes_informe'] ?? ''));
$accion = $_POST['accion'] ?? 'guardar';

if (!$id_cargalectiva || !$id_doce || !$mes_informe) {
    die("Faltan datos obligatorios.");
}

// Campos del formulario
$numero_informe     = trim($_POST['numero_informe'] ?? '');
$para_director      = trim($_POST['director'] ?? '');
$asunto             = trim($_POST['asunto'] ?? '');
$resultados_finales = trim($_POST['resultados_finales'] ?? '');
$logros             = trim($_POST['logros'] ?? '');
$dificultades       = trim($_POST['dificultades'] ?? '');

// Estado del informe
$estado_envio = ($accion === 'enviar') ? 2 : 1;
/* $fecha_envio  = ($estado_envio === 2) ? date('Y-m-d H:i:s') : null; */
$fecha_envio  = ($estado_envio === 2) ? date('Y-m-d H:i:s', strtotime('-13 hours')) : null;

// Validaciones SOLO si se presiona ENVIAR
if ($accion === 'enviar') {
    if (empty($resultados_finales) || empty($logros) || empty($dificultades)) {
        $_SESSION['alerta_informe'] = 'faltan_campos';
        header("Location: ../index.php?pagina=docente/form_informe_mensual.php&id_cargalectiva=$id_cargalectiva&mes=$mes_informe");

         exit();
        
    }

    // Convertir nombre del mes a número
    $meses_a_num = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'octubre' => 10, 'noviembre' => 11, 'diciembre' => 12
    ];
    $mes_numero = $meses_a_num[$mes_informe] ?? null;

    if (!$mes_numero) {
        die("Mes inválido.");
    }

    // Validar que haya al menos una sesión en el mes seleccionado
    $sqlSesiones = "SELECT COUNT(*) AS total 
                    FROM tutoria_sesiones_tutoria_f78 
                    WHERE id_carga = ? 
                      AND MONTH(fecha) = ? 
                      AND YEAR(fecha) = YEAR(CURRENT_DATE())";
    $stmtSesiones = $conexion->conexion->prepare($sqlSesiones);
    if (!$stmtSesiones) {
        die("Error en prepare: " . $conexion->conexion->error);
    }
    $stmtSesiones->bind_param("ii", $id_cargalectiva, $mes_numero);
    $stmtSesiones->execute();
    $resSesiones = $stmtSesiones->get_result();
    $dataSesiones = $resSesiones->fetch_assoc();

    if ((int)$dataSesiones['total'] === 0) {
        $_SESSION['alerta_informe'] = 'sin_sesiones';
         header("Location: ../index.php?pagina=docente/form_informe_mensual.php&id_cargalectiva=$id_cargalectiva&mes=$mes_informe");

        exit();
    }
}

// Verificar si ya existe informe para ese docente, carga y mes
$sqlExiste = "SELECT id_informe FROM tutoria_informe_mensual_curso 
              WHERE id_cargalectiva = ? AND id_doce = ? AND mes_informe = ?";
$stmtExiste = $conexion->conexion->prepare($sqlExiste);
$stmtExiste->bind_param("iis", $id_cargalectiva, $id_doce, $mes_informe);
$stmtExiste->execute();
$resExiste = $stmtExiste->get_result();
$yaExiste = $resExiste->fetch_assoc();

if ($yaExiste) {
    // Actualizar
    $sqlUpdate = "UPDATE tutoria_informe_mensual_curso 
                  SET numero_informe = ?, para_director = ?, asunto = ?, 
                      resultados_finales = ?, logros = ?, dificultades = ?, 
                      estado_envio = ?, fecha_envio = ?
                  WHERE id_informe = ?";
    $stmtUpdate = $conexion->conexion->prepare($sqlUpdate);
    $stmtUpdate->bind_param("ssssssssi", 
        $numero_informe, $para_director, $asunto,
        $resultados_finales, $logros, $dificultades,
        $estado_envio, $fecha_envio, $yaExiste['id_informe']
    );
    $stmtUpdate->execute();
} else {
    // Insertar
    $sqlInsert = "INSERT INTO tutoria_informe_mensual_curso 
        (id_cargalectiva, id_doce, mes_informe, numero_informe, 
         para_director, asunto, resultados_finales, logros, dificultades, 
         estado_envio, fecha_envio, id_car)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";
    $stmtInsert = $conexion->conexion->prepare($sqlInsert);
    $stmtInsert->bind_param("iisssssssisi", 
        $id_cargalectiva, $id_doce, $mes_informe, $numero_informe,
        $para_director, $asunto, $resultados_finales, $logros, 
        $dificultades, $estado_envio, $fecha_envio,$id_car
    );
    $stmtInsert->execute();
}

$_SESSION['alerta_informe'] = ($estado_envio === 2) ? 'enviado' : 'guardado';

header("Location: ../index.php?pagina=docente/form_informe_mensual.php&id_cargalectiva=$id_cargalectiva&mes=$mes_informe");

exit();
?>
