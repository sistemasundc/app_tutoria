<?php
// vista/tutor_aula/salon_asig_tutoria.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Seguridad básica (ajusta el rol si tu sistema usa otro literal)
if (!isset($_SESSION['S_IDUSUARIO'])) {
  die('Acceso no autorizado');
}

// Rutas absolutas para evitar includes rotos
require_once __DIR__ . '/../../modelo/modelo_conexion.php';
require_once __DIR__ . '/../../modelo/modelo_docente.php';

$CN = new conexion();
$CN->conectar();
$MD = new Docente();

$id_doc     = (int)$_SESSION['S_IDUSUARIO'];
$id_semestre= (int)($_SESSION['S_SEMESTRE'] ?? 0);

// === Aulas/cargas asignadas al TUTOR DE AULA en el semestre actual ===
$aulas = [];
$rs = $MD->aulas_del_docente_TA($id_doc, $id_semestre);
if ($rs instanceof mysqli_result) {
  while ($r = $rs->fetch_assoc()) { $aulas[] = $r; }
}

// Helper para generar URL al router (index.php) manteniendo el esquema del sistema
$INDEX_BASE = '../index.php'; // porque este archivo está en vista/tutor_aula/*
function url_estudiantes_por_carga($id_carga) {
  global $INDEX_BASE;
  $qs = [
    'pagina'     => 'tutor_aula/vista_alumnos_asignados',
    'f_id_carga' => (int)$id_carga
  ];
  return htmlspecialchars($INDEX_BASE . '?' . http_build_query($qs), ENT_QUOTES, 'UTF-8');
}
?>
<style>
  .contenedor-asignaturas{max-width:1100px;margin:40px auto;padding:10px 25px;}
  .titulo-asignaturas{background: rgb(65, 117, 196);color:#fff;padding:15px 25px;border-radius:10px;font-size:18px;font-weight:700;margin-bottom:25px}
  .card-asig{border:1px solid #dee2e6;border-radius:12px;padding:18px 22px;margin-bottom:14px;background:#fff;
             box-shadow:0 3px 10px rgba(0,0,0,.05);display:flex;justify-content:space-between;align-items:center}
  .card-asig .datos{font-weight:600;font-size:14px;color:#333}
  .card-asig .acciones a{margin-left:8px}
</style>

<div class="contenedor-asignaturas">
  <div class="titulo-asignaturas">AULAS TUTORADAS - TUTOR DE AULA</div>

  <?php if (empty($aulas)): ?>
    <div class="alert alert-warning">No tienes aulas asignadas en este semestre.</div>
  <?php else: ?>
    <?php foreach ($aulas as $a): 
      $texto = sprintf(
        'CICLO %s — TURNO %s — %s',
        $a['ciclo'],
        $a['turno'],
        $a['seccion']
      );
    ?>
      <div class="card-asig">
        <div class="datos">
          <?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <div class="acciones">
        <button class="btn btn-success btn-sm"
                onclick="return ver_estudiantes(<?= (int)$a['id_carga'] ?>)">
            <i class="fa fa-users"></i> ESTUDIANTES
        </button>
        </div>

        <script>
        function ver_estudiantes(idCarga) {
            // carga la vista de alumnos de esa carga lectiva dentro de #contenido_principal
            cargar_contenido('contenido_principal',
            'tutor_aula/vista_alumnos_asignados.php?id_cargalectiva=' + encodeURIComponent(idCarga)
            );
            return false;
        }
        </script>

      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
