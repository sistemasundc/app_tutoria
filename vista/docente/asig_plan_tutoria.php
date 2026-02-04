<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

if (!isset($_SESSION['S_IDUSUARIO']) || $_SESSION['S_ROL'] !== 'TUTOR DE CURSO') {
    die('Acceso no autorizado');
}

$id_semestre = $_SESSION['S_SEMESTRE'];
$id_doce = $_SESSION['S_IDUSUARIO'];

$conexion = new conexion();
$conexion->conectar();

// Asignaturas
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
     OR (cl.id_cargalectiva = 4945 AND ? = 19)
  )
";
$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("ii", $id_doce, $id_doce);
$stmt->execute();
$result = $stmt->get_result();
$asignaturas = $result->fetch_all(MYSQLI_ASSOC);

// Informes enviados
$informes_enviados = [];
$query = "SELECT id_cargalectiva, LOWER(mes_informe) as mes FROM tutoria_informe_mensual_curso 
          WHERE id_doce = ? AND estado_envio = 2";
$stmt2 = $conexion->conexion->prepare($query);
$stmt2->bind_param("i", $id_doce);
$stmt2->execute();
$res_informes = $stmt2->get_result();

while ($row = $res_informes->fetch_assoc()) {
    $informes_enviados[$row['id_cargalectiva']][$row['mes']] = true;
}

/* $meses = ["Abril", "Mayo", "Junio", "Julio", "Agosto"]; */
$meses = ["Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
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
        padding: 15px 20px;
        margin-bottom: 20px;
        background-color: #ffffff;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .datos-asig {
        font-weight: bold;
        font-size: 15px;
        flex: 1 1 40%;
    }

    .fila-meses-horizontal {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        flex: 1 1 55%;
        justify-content: flex-end;
    }

    .mes-card {
        background: #f9f9f9;
        border: 1px solid #ccc;
        padding: 10px 12px;
        border-radius: 10px;
        text-align: center;
        width: 100px;
        box-shadow: 1px 1px 6px rgba(0,0,0,0.1);
    }

    .mes-nombre {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .mes-card .acciones button {
        margin: 0 3px;
    }
    .boton-ver-informe {
        border: 2px solidrgb(42, 130, 231) !important; /* Azul claro Bootstrap */
        background-color: rgb(161, 217, 243);;
        color:rgb(42, 130, 231);
        transition: all 0.3s ease;
    }

    .boton-ver-informe:hover {
        background-color:rgb(42, 130, 231);;
        color: white;
    }

    @media (max-width: 768px) {
        .card-asig {
            flex-direction: column;
            align-items: flex-start;
        }

        .fila-meses-horizontal {
            justify-content: flex-start;
            margin-top: 10px;
        }

        .datos-asig {
            margin-bottom: 10px;
        }
    }
</style>

<div style="display:flex; gap:20px; max-width:1400px; margin:auto; flex-wrap:wrap;">

<!-- IZQUIERDA -->
<div style="flex:3; min-width:600px;">
  <center><div class="titulo-asignaturas">INFORMES MENSUALES DE TUTORÍA POR ASIGNATURA</div></center>

<?php if (count($asignaturas) === 0): ?>
    <div class="alert alert-warning">No tienes asignaturas asignadas en este semestre.</div>
<?php else: ?>
    <?php foreach ($asignaturas as $asig): ?>
        <div class="card-asig">
            <div class="datos-asig">
                <?= strtoupper($asig['nom_asi']) ?> — Ciclo <?= $asig['ciclo'] ?> | Turno <?= $asig['turno'] ?> | Sección <?= $asig['seccion'] ?>
            </div>
            <div class="fila-meses-horizontal">
                <!-- <button class="btn btn-info btn-sm" ---Falta el inicio  php
                        onclick="cargarPendientes( //$asig['id_cargalectiva'] ?>)">
                    <i class="fa fa-users"></i> Pendientes
                </button>-->
                <?php foreach ($meses as $mes): 
                    $id_carga = $asig['id_cargalectiva'];
                    $mes_actual = strtolower($mes);
                    $estado = isset($informes_enviados[$id_carga][$mes_actual]) ? 2 : null;
                ?>
                <div class="mes-card">
                    <div class="mes-nombre"><?= $mes ?></div>
                            <div class="acciones">
                                <?php if ($estado === null): ?>
                                    <!-- No existe informe -->
                                    <a href="index.php?pagina=docente/form_informe_mensual.php&id_cargalectiva=<?= $id_carga ?>&mes=<?= $mes_actual ?>" class="btn btn-success btn-sm" title="Crear Informe">
                                        <i class="fa fa-plus"></i>
                                    </a>
                                <?php elseif ($estado == 1): ?>
                                    <!-- Informe guardado (no enviado) -->
                                    <a href="index.php?pagina=docente/form_informe_mensual.php&id_cargalectiva=<?= $id_carga ?>&mes=<?= $mes_actual ?>" class="btn btn-warning btn-sm" title="Editar Informe">
                                        <i class="fa fa-edit"></i>
                                    </a>
                                <?php elseif ($estado == 2): ?>
                                    <!-- Informe enviado -->
                                    <button class="btn btn-sm boton-ver-informe"
                                            onclick="abrirVentanaPopup('docente/vista_prev_informe_mensual.php?id_cargalectiva=<?= $id_carga ?>&mes=<?= $mes_actual ?>')"
                                            title="Ver Informe Enviado">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- DERECHA -->
<!-- <div style="flex:1; min-width:300px;">
  <div class="titulo-asignaturas">PENDIENTES DE ENCUESTA</div>
  <div id="pendientes-container">
      <p style="text-align:center; color:#777;">Selecciona un curso para ver los pendientes</p>
  </div>
</div> -->

</div>

<script>
function abrirVentanaPopup(url) {
  const ancho = 900;
  const alto = 700;
  const izquierda = (screen.width - ancho) / 2;
  const arriba = (screen.height - alto) / 2;

  window.open(
    url,
    'VistaPrevTutoria',
    `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes,toolbar=no,location=no,status=no,menubar=no`
  );
}

function cargarPendientes(id_carga) {
  const contenedor = document.getElementById('pendientes-container');
  contenedor.innerHTML = '<p style="text-align:center;">Cargando...</p>';

  fetch(`docente/pendientes_encuesta.php?id_cargalectiva=${id_carga}`)
    .then(res => res.text())
    .then(html => {
      contenedor.innerHTML = html;
    })
    .catch(err => {
      contenedor.innerHTML = '<p style="color:red;">Error al cargar los pendientes.</p>';
      console.error(err);
    });
}
</script>
