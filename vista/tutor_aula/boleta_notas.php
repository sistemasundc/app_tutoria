<?php
session_start();

$semestree = (int)($_SESSION['S_SEMESTRE'] ?? 0);

if (!isset($_SESSION['S_IDUSUARIO'], $_SESSION['S_USER'], $_SESSION['S_ROL'])) {
    header('Location: ../../Login/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_estu'])) {
    $id_estu = (int)$_POST['id_estu'];

    require_once('../../modelo/modelo_conexion.php');
    $miConexion = new Conexion();
    $miConexion->conectar();

    // OJO: tomamos el ciclo de la CARGA LECTIVA (c.ciclo) y traemos también vez_a
    $sql = "
        SELECT 
            g.nom_asi,
            c.ciclo          AS ciclo_cl,     -- <- ciclo real por curso desde carga_lectiva
            a.vez_a,
            a.vez_a          AS vez,                          -- <- cuántas veces lleva el curso
            a.ntp1_per, a.ntp2_per, a.exa_par,
            a.ntp3_per, a.ntp4_per, a.exa_final,
            a.pf_per, a.profinal_record, a.exa_susti,
            e.apepa_estu, e.apema_estu, e.nom_estu
        FROM asignacion_estudiante a
        JOIN carga_lectiva c ON c.id_cargalectiva = a.id_cargalectiva
        JOIN asignatura g    ON g.id_asi         = a.id_asi
        JOIN estudiante e    ON e.id_estu        = a.id_estu
        WHERE a.id_estu = ? 
          AND a.id_semestre = ? 
          AND a.borrado <> 1
        ORDER BY 
          -- si tus ciclos son romanos, esto los ordena I..X; si son números, puedes cambiar por c.ciclo+0
          CASE c.ciclo
            WHEN 'I' THEN 1 WHEN 'II' THEN 2 WHEN 'III' THEN 3 WHEN 'IV' THEN 4
            WHEN 'V' THEN 5 WHEN 'VI' THEN 6 WHEN 'VII' THEN 7 WHEN 'VIII' THEN 8
            WHEN 'IX' THEN 9 WHEN 'X' THEN 10 ELSE 99 END,
          g.nom_asi
    ";

    if ($stmt = $miConexion->conexion->prepare($sql)) {
        $stmt->bind_param("ii", $id_estu, $semestree);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Armamos nombre una sola vez
            $primeraFila = $result->fetch_assoc();
            $nombreCompleto = trim($primeraFila['apepa_estu'].' '.$primeraFila['apema_estu'].' '.$primeraFila['nom_estu']);
            $result->data_seek(0); // reiniciamos el puntero

            // ====== ESTILOS + MODAL CON BOTÓN X ======
            echo '<style>
                .modal-notas{
                    position: fixed;
                    top: 5vh;
                    left: 50%;
                    transform: translateX(-50%);
                    width: min(1000px, 95vw);
                    background: #fff;
                    border-radius: 10px;
                    box-shadow: 0 20px 60px rgba(0,0,0,.25);
                    z-index: 99999;
                    overflow: hidden;
                }
                .modal-notas__header{
                    background: #295A2F;
                    color: #fff;
                    padding: 12px 16px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    font-weight: 700;
                }
                .modal-notas__close{
                    border: 0; background: transparent; color: #fff; 
                    font-size: 22px; line-height: 1; cursor: pointer;
                }
                .modal-notas__body{ padding: 12px 14px; }

                table { border-collapse: collapse; width: 100%; font-size: 13px; }
                th, td { border: 1px solid #ccc; padding: 6px; text-align: center; }
                th { background-color: rgb(41,122,66); color: #fff; }
                .red-text { color: #c00; font-weight: bold; }

                .badge-rep{
                    background: #d9534f; color: #fff; 
                    font-size: 11px; padding: 2px 6px; border-radius: 4px; margin-left: 6px;
                }
                tr.repitente{ background: #ffecec; }
            </style>';

            echo '<div id="modal-notas" class="modal-notas">';
            echo '  <div class="modal-notas__header">
                        <div>NOTAS DEL ESTUDIANTE — '.htmlspecialchars(mb_strtoupper($nombreCompleto,'UTF-8')).'</div>
                        <button class="modal-notas__close" onclick="(function(){var m=document.getElementById(\'modal-notas\'); if(m) m.remove();})();" title="Cerrar">×</button>
                    </div>';
            echo '  <div class="modal-notas__body">';

            echo "<table>";
            echo "<tr>
                    <th>Ciclo</th>
                    <th>Curso</th>
                    <th></th>
                    <th>P1</th><th>P2</th><th>Parcial</th>
                    <th>P3</th><th>P4</th><th>Final</th>
                    <th>Promedio</th><th>Acta</th><th>Susti</th>
                  </tr>";

            while ($row = $result->fetch_assoc()) {
                $rep = (int)$row['vez_a'] >= 2;

                // fila con color suave si es repitente
                echo '<tr'.($rep ? ' class="repitente"' : '').'>';

                // CICLO (desde carga_lectiva)
                echo '<td>'.htmlspecialchars($row["ciclo_cl"] ?? '-', ENT_QUOTES, "UTF-8").'</td>';

                // CURSO + insignia REPITENTE si corresponde
                $curso = htmlspecialchars($row["nom_asi"], ENT_QUOTES, "UTF-8");
                if ($rep) { $curso .= ' <span class="badge-rep">REPITENTE</span>'; }
                echo '<td style="text-align:left;">'.$curso.'</td>';
                echo '<td>'.htmlspecialchars($row["vez"] ?? '-', ENT_QUOTES, "UTF-8").'</td>';
                // resto de columnas de nota
                $campos = ['ntp1_per','ntp2_per','exa_par','ntp3_per','ntp4_per','exa_final','pf_per','profinal_record','exa_susti'];
                foreach ($campos as $campo) {
                    $nota  = $row[$campo];
                    $clase = (is_numeric($nota) && $nota < 11) ? " class=\"red-text\"" : "";
                    $valor = ($nota !== null && $nota !== '') ? htmlspecialchars($nota, ENT_QUOTES, "UTF-8") : "-";
                    echo "<td$clase>$valor</td>";
                }

                echo "</tr>";
            }

            echo "</table>";
            echo '  </div>'; // modal body
            echo '</div>';   // modal
        } else {
            echo "<div style='color: red;'>No se encontraron resultados.</div>";
        }

        $stmt->close();
    } else {
        echo "Error al preparar la consulta.";
    }

    $miConexion->cerrar();
} else {
    echo "Estudiante no existe.";
}
?>
