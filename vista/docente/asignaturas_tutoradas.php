<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once("../../modelo/modelo_conexion.php");

if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'TUTOR DE CURSO') {
    die('Acceso no autorizado');
}

$conexion = new conexion();
$conexion->conectar();
$id_semestre = $_SESSION['S_SEMESTRE'] ?? 0;
$id_doce = $_SESSION['S_IDUSUARIO'];

// Consulta de asignaturas ERA 19 MAGALLNAES
$sql = "
SELECT  
    a.nom_asi,
    cl.ciclo,
    cl.turno,
    cl.seccion,
    cl.id_cargalectiva
FROM carga_lectiva cl
JOIN asignatura a ON cl.id_asi = a.id_asi
WHERE cl.id_semestre = $id_semestre
  AND cl.tipo = 'M'
  AND (
        cl.id_doce = ?
     OR (cl.id_cargalectiva = 4945 AND ? = 19 )
  )
";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("ii", $id_doce, $id_doce);
$stmt->execute();
$result = $stmt->get_result();
$asignaturas = $result->fetch_all(MYSQLI_ASSOC);
?>

<style>
    .contenedor-asignaturas {
        max-width: 1100px;
        margin: 40px auto;
        padding: 10px 25px;
    }
    .titulo-asignaturas {
        background-color: #0b57d0;
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 25px;
    }
    .card-asig {
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 20px 25px;
        margin-bottom: 15px;
        background-color: #ffffff;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .card-asig .datos {
        font-weight: 500;
        font-size: 14px;
        color: #333;
    }
    .card-asig .acciones button {
        margin-left: 10px;
    }
</style>

<div class="contenedor-asignaturas">
    <div class="titulo-asignaturas">ASIGNATURAS TUTORADAS</div>

    <?php if (count($asignaturas) === 0): ?>
        <div class="alert alert-warning">No tienes asignaturas asignadas en este semestre.</div>
    <?php else: ?>
        <?php foreach ($asignaturas as $asig): ?>
            <div class="card-asig">
                <div class="datos">
                    <?= strtoupper($asig['nom_asi']) ?> — Ciclo <?= $asig['ciclo'] ?> | Turno <?= $asig['turno'] ?> |  <?= $asig['seccion'] ?>
                </div>
                <div class="acciones">
                    <button class="btn btn-success btn-sm" onclick="cargar_contenido('contenido_principal', 'docente/vista_alumnos_xasig.php?id_cargalectiva=<?= $asig['id_cargalectiva'] ?>')">
                        <i class="fa fa-plus"></i> ESTUDIANTES
                    </button>
                    <!--<button class="btn btn-outline-info btn-sm" disabled>
                        <i class="fa fa-eye"></i> Visualizar
                    </button>-->
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

