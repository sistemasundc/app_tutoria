<?php
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');

if (!isset($_GET['id_cargalectiva'])) die('<p style="color:red;">ID no válido</p>');
$id_cargalectiva = intval($_GET['id_cargalectiva']);

$conexion = new conexion();
$conexion->conectar();

$sql = "
SELECT 
    e.id_estu,
    CONCAT(e.apepa_estu, ' ', e.apema_estu, ' ', e.nom_estu) AS nombre_completo
FROM asignacion_estudiante ae
INNER JOIN estudiante e ON e.id_estu = ae.id_estu
WHERE ae.id_cargalectiva = ?
  AND ae.borrado = 0
  AND e.id_estu NOT IN (
      SELECT id_estu
      FROM tutoria_encuesta_satisfaccion
  )
GROUP BY e.id_estu
ORDER BY nombre_completo ASC
";

$stmt = $conexion->conexion->prepare($sql);
$stmt->bind_param("i", $id_cargalectiva);
$stmt->execute();
$res = $stmt->get_result();
?>

<style>
.card-pendientes {
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 15px 20px;
    margin-bottom: 20px;
    background-color: #ffffff;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
}

.tabla-bordes {
    border-collapse: collapse;
    width: 100%;
}

.tabla-bordes th,
.tabla-bordes td {
    border: 1px solid #ccc;
    padding: 3px 6px; /* aquí se redujo: antes era 6px 8px */
    font-size: 12px; /* opcional: también puedes hacerla más pequeña */
}

.tabla-bordes th {
    background-color: #007BFF;
    color: white;
    text-align: center;
}

.tabla-bordes td:first-child {
    text-align: center;
}

.tabla-bordes tr:nth-child(odd) {
    background-color: #f9f9f9;
}

.tabla-bordes tr:hover {
    background-color: #e6f7ff;
}
</style>


<div class="card-pendientes">
  <table class="tabla-bordes">
  <thead>
  <tr>
  <th>ID</th>
  <th>Estudiante</th>
  </tr>
  </thead>
  <tbody>
  <?php while ($row = $res->fetch_assoc()): ?>
  <tr>
  <td><?= htmlspecialchars($row['id_estu']) ?></td>
  <td><?= htmlspecialchars($row['nombre_completo']) ?></td>
  </tr>
  <?php endwhile; ?>
  </tbody>
  </table>
</div>
