<?php 
session_start();

$semestree = $_SESSION['S_SEMESTRE'];
$fecha_semestre = $_SESSION['S_SEMESTRE_FECHA'];

if (!isset($_SESSION['S_IDUSUARIO']) || !isset($_SESSION['S_USER']) || !isset($_SESSION['S_ROL'])) {
    header('Location: ../../Login/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_estu']) && isset($_POST['id_cargalectiva'])) {
    $id_estu = intval($_POST['id_estu']);
    $id_cargalectiva = intval($_POST['id_cargalectiva']);

    require_once('../../modelo/modelo_conexion.php');
    $miConexion = new Conexion();
    $miConexion->conectar();

    $sql = "SELECT 
                g.nom_asi, 
                a.ntp1_per, a.ntp2_per, a.exa_par, 
                a.ntp3_per, a.ntp4_per, a.exa_final,
                e.apepa_estu, e.apema_estu, e.nom_estu
            FROM asignacion_estudiante a
            JOIN carga_lectiva c ON c.id_cargalectiva = a.id_cargalectiva
            JOIN asignatura g ON g.id_asi = a.id_asi
            JOIN estudiante e ON e.id_estu = a.id_estu
            WHERE a.id_estu = ? AND a.id_cargalectiva = ? AND a.id_semestre = ? AND a.borrado != 1";

    if ($stmt = $miConexion->conexion->prepare($sql)) {
        $stmt->bind_param("iii", $id_estu, $id_cargalectiva, $semestree);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $primeraFila = $result->fetch_assoc();
            $nombreCompleto = $primeraFila['apepa_estu'] . ' ' . $primeraFila['apema_estu'] . ' ' . $primeraFila['nom_estu'];
            $result->data_seek(0); // Reiniciar puntero

            echo "<style>
                    table { border-collapse: collapse; width: 100%; font-size: 13px; }
                    th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
                    th { background-color:#066da7; color: white; }
                    .red-text { color: red; font-weight: bold; }
                  </style>";

            echo "<table>";
            echo "<tr><th colspan='7'>NOTAS DEL ESTUDIANTE - <span style='text-transform: uppercase;'>$nombreCompleto</span> | SEMESTRE $fecha_semestre</th></tr>";
            echo "<tr>
                    <th>Curso</th>
                    <th>P1</th><th>P2</th><th>Parcial</th>
                    <th>P3</th><th>P4</th><th>Final</th>
                  </tr>";

            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["nom_asi"], ENT_QUOTES, 'UTF-8') . "</td>";

                $campos = ['ntp1_per','ntp2_per','exa_par','ntp3_per','ntp4_per','exa_final'];
                foreach ($campos as $campo) {
                    $nota = $row[$campo];
                    $clase = (is_numeric($nota) && $nota < 11) ? " class='red-text'" : "";
                    $valor = $nota !== null ? htmlspecialchars($nota, ENT_QUOTES, 'UTF-8') : "-";
                    echo "<td$clase>$valor</td>";
                }

                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "<div style='color: red;'>No se encontraron resultados.</div>";
        }

        $stmt->close();
    } else {
        echo "Error al preparar la consulta.";
    }

    $miConexion->cerrar();
} else {
    echo "Parámetros inválidos o estudiante no existe.";
}
?>
