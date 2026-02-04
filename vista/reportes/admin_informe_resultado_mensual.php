<?php  
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

if (!isset($_SESSION['S_IDUSUARIO'])) {
    die('Acceso no autorizado');
}

$id_usuario = $_SESSION['S_IDUSUARIO'];

// Conexión y simulación de datos
$conexion = new conexion();
$conexion->conectar();

// Obtener informes guardados por mes
$sql = "SELECT LOWER(mes_informe) AS mes FROM tutoria_informe_resultados_coordinador_general WHERE guardado = 1";
$resultado = $conexion->conexion->query($sql);

// Inicializar estado por defecto
$informes_enviados = [
    'abril' => false,
    'mayo' => false,
    'junio' => false,
    'julio' => false,
    'septiembre' => false,
    'octubre' => false,
    'noviembre' => false,
    'diciembre' => false
];

// Marcar los meses guardados
if ($resultado) {
    while ($row = $resultado->fetch_assoc()) {
        $mes = strtolower(trim($row['mes']));
        if (array_key_exists($mes, $informes_enviados)) {
            $informes_enviados[$mes] = true;
        }
    }
}

$meses = ["Abril", "Mayo", "Junio", "Julio", "Septiembre", "Octubre", "Noviembre", "Diciembre"];


?>

<style>
    .contenedor-meses {
        max-width: 1200px;
        margin: 50px auto;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 5px;
    }

    .titulo {
        background-color: #2c3e50;
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        font-size: 20px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 30px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.8);
    }
   
    .fila-meses {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        justify-content: center;
    }

    .mes-card {
        background: #f9f9f9;
        border: 1px solid #ccc;
        padding: 15px 12px;
        border-radius: 12px;
        text-align: center;
        width: 120px;
        box-shadow: 1px 1px 6px rgba(0,0,0,0.1);
    }

    .mes-nombre {
        font-weight: bold;
        margin-bottom: 10px;
        font-size: 16px;
    }

    .acciones button {
        margin: 0 4px;
    }

    .boton-ver {
        background-color: rgb(161, 217, 243);
        color: rgb(42, 130, 231);
        border: 2px solid rgb(42, 130, 231);
    }

    .boton-ver:hover {
        background-color: rgb(42, 130, 231);
        color: white;
    }

    @media (max-width: 768px) {
        .fila-meses {
            flex-direction: column;
            align-items: center;
        }
    }
</style>


<div class="contenedor-meses">
    <div class="titulo">INFORMES DE RESULTADOS DE TUTORÍA MENSUALES</div>

    <div class="fila-meses">
        <?php foreach ($meses as $mes): 
            $mes_lower = strtolower($mes);
            $estado = isset($informes_enviados[$mes_lower]) && $informes_enviados[$mes_lower] === true;
        ?>
            <div class="mes-card">
                <div class="mes-nombre"><?= $mes ?></div>
                <div class="acciones">
                    <?php if (!$estado): ?>
                        <a href="index.php?pagina=reportes/admin_form_informe_mensual.php&mes=<?= $mes_lower ?>" 
                        class="btn btn-success btn-sm" title="Crear Informe">
                            <i class="fa fa-plus"></i>
                        </a>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                            <!-- Botón Ver -->
                            <button class="btn btn-sm boton-ver"
                                    onclick="abrirVentanaPopup('reportes/admin_vista_prev_informe_mensual.php?mes=<?= $mes_lower?>')"
                                    title="Ver Informe">
                                <i class="fa fa-eye"></i>
                            </button>

                            <!-- Botón Anexos -->
                            <button class="btn btn-sm btn-warning"
                                    onclick="abrirVentanaPopup('../../pdf_ge/admin_formato78_html.php?mes=<?= $mes_lower?>')"
                                    title="Anexos">
                                Anexos
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>


function abrirVentanaPopup(url) {
    const ancho = 900;
    const alto = 700;
    const izquierda = (screen.width - ancho) / 2;
    const arriba = (screen.height - alto) / 2;

    window.open(
        url,
        'VistaPrevInforme',
        `width=${ancho},height=${alto},top=${arriba},left=${izquierda},resizable=yes,scrollbars=yes`
    );
}
</script>
