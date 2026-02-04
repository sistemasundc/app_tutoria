<?php   
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once(__DIR__ . '/../../modelo/modelo_conexion.php');
$conexion = new conexion();
$cn = $conexion->conectar();

$id_car_sesion = $_SESSION['S_SCHOOL'] ?? null;
if (!$id_car_sesion) {
    die('<p style="color:red;text-align:center;">No se detectó la carrera. Vuelva a iniciar sesión.</p>');
}

$sql = "
SELECT id_car, LOWER(mes_informe) AS mes, COUNT(*) AS cantidad
FROM tutoria_informe_mensual
WHERE estado_envio=2 AND id_car = $id_car_sesion
GROUP BY id_car, mes_informe
UNION ALL
SELECT id_car, LOWER(mes_informe) AS mes, COUNT(*) AS cantidad
FROM tutoria_informe_mensual_curso
WHERE estado_envio=2 AND id_car = $id_car_sesion
GROUP BY id_car, mes_informe
";

$res = $cn->query($sql);

$data = [];
while ($row = $res->fetch_assoc()) {
    $mes = $row['mes'];
    $cantidad = $row['cantidad'];
    if (!isset($data[$mes])) $data[$mes] = 0;
    $data[$mes] += $cantidad;
}

// Orden fijo de meses abril → julio
$meses_ordenados = ['abril', 'mayo', 'junio', 'julio','agosto','septiembre','octubre','noviembre','diciembre'];

// nombre carrera
$resCar = $cn->query("SELECT nom_car FROM carrera WHERE id_car = $id_car_sesion");
$nombre_carrera = $resCar->fetch_assoc()['nom_car'] ?? 'Mi Carrera';

// color carrera
$colores_carrera = [
    1 => 'orange',
    2 => 'red',
    3 => 'purple',
    4 => 'turquoise',
    5 => 'green'
];
$color_actual = $colores_carrera[$id_car_sesion] ?? 'blue';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Tendencia de Cumplimiento</title>
<style>
body {
  margin: 0;
  background: #f1f1f1;
}
.contenedor-principal {
  width: 95%;
  max-width: 1000px;
  margin: 20px auto;
  background: #fff;
  border-radius: 6px;
  box-shadow: 0 0 5px rgba(0,0,0,0.2);
  overflow: hidden;
  font-family: Arial, sans-serif;
}
.titulo {
  background: #003366;
  color: #fff;
  padding: 10px 20px;
  font-weight: bold;
  text-align: center;
  font-size: 18px;
}
.contenido {
  padding: 20px;
  text-align: center;
}
.grafico {
  width: 100%;
  aspect-ratio: 2 / 1;
  margin: 0 auto;
  border: 1px solid #ccc;
}
.leyenda {
  margin-top: 20px;
  text-align: center;
  flex-wrap: wrap;
}
.leyenda div {
  display: inline-block;
  margin: 5px 10px;
  font-size: 1rem;
}
.leyenda span {
  display: inline-block;
  width: 12px;
  height: 12px;
  margin-right: 5px;
  vertical-align: middle;
}
.mensaje {
  color: red;
  font-size: 16px;
  font-weight: bold;
  text-align: center;
  margin-top: 40px;
}
</style>
</head>
<body>

<div class="contenedor-principal">
  <div class="titulo">TENDENCIA DE CUMPLIMIENTO - <?= htmlspecialchars($nombre_carrera) ?> - 2025</div>
  <div class="contenido">
    <div id="grafico" class="grafico"></div>

    <div class="leyenda">
      <div><span style="background:<?= $color_actual ?>"></span> <?= htmlspecialchars($nombre_carrera) ?></div>
    </div>
  </div>
</div>

<script>
(function(){
const datos = <?php echo json_encode($data); ?>;
const meses = <?php echo json_encode($meses_ordenados); ?>;
const color = "<?php echo $color_actual; ?>";

function dibujarGrafico() {
  const w = 800, h = 400, padding = 40;

  const contenedor = document.getElementById("grafico");
  if (!contenedor) return;

  const tieneDatos = meses.some(m => (datos[m] ?? 0) > 0);
  contenedor.innerHTML = ""; 

  if (!tieneDatos) {
      const msg = document.createElement("div");
      msg.className = "mensaje";
      msg.textContent = "No hay datos disponibles para mostrar.";
      contenedor.appendChild(msg);
      return;
  }

  const maxY = Math.max(...Object.values(datos), 80) + 2;

  const svgNS = "http://www.w3.org/2000/svg";
  const svg = document.createElementNS(svgNS, "svg");
  svg.setAttribute("viewBox", `0 0 ${w} ${h}`);
  svg.setAttribute("preserveAspectRatio", "xMidYMid meet");
  svg.style.width = "100%";
  svg.style.height = "100%";

  const ejeX = document.createElementNS(svgNS, "line");
  ejeX.setAttribute("x1", padding);
  ejeX.setAttribute("y1", h-padding);
  ejeX.setAttribute("x2", w-padding);
  ejeX.setAttribute("y2", h-padding);
  ejeX.setAttribute("stroke", "#000");
  svg.appendChild(ejeX);

  const ejeY = document.createElementNS(svgNS, "line");
  ejeY.setAttribute("x1", padding);
  ejeY.setAttribute("y1", padding);
  ejeY.setAttribute("x2", padding);
  ejeY.setAttribute("y2", h-padding);
  ejeY.setAttribute("stroke", "#000");
  svg.appendChild(ejeY);

  function escX(i) {
    return padding + i * ((w-2*padding)/(meses.length-1));
  }
  function escY(val) {
    return h-padding - (val*(h-2*padding)/maxY);
  }

  const path = document.createElementNS(svgNS, "path");
  let d = "";
  meses.forEach((mes, i) => {
    const x = escX(i);
    const y = escY((datos[mes]) ? datos[mes] : 0);
    d += (i==0?"M":"L") + x + " " + y + " ";
  });
  path.setAttribute("d", d);
  path.setAttribute("fill", "none");
  path.setAttribute("stroke", color);
  path.setAttribute("stroke-width", 2);
  svg.appendChild(path);

  meses.forEach((mes, i) => {
    const x = escX(i);
    const y = escY((datos[mes]) ? datos[mes] : 0);
    const valor = (datos[mes]) ? datos[mes] : 0;

    const circle = document.createElementNS(svgNS, "circle");
    circle.setAttribute("cx", x);
    circle.setAttribute("cy", y);
    circle.setAttribute("r", 3);
    circle.setAttribute("fill", color);
    svg.appendChild(circle);

    const valueText = document.createElementNS(svgNS, "text");
    valueText.setAttribute("x", x);
    valueText.setAttribute("y", y - 5);
    valueText.setAttribute("text-anchor", "middle");
    valueText.setAttribute("font-size", "10");
    valueText.setAttribute("fill", "black");
    valueText.textContent = valor;
    svg.appendChild(valueText);
  });

  const objetivoY = escY(80);
  const objetivoLine = document.createElementNS(svgNS, "line");
  objetivoLine.setAttribute("x1", padding);
  objetivoLine.setAttribute("y1", objetivoY);
  objetivoLine.setAttribute("x2", w - padding);
  objetivoLine.setAttribute("y2", objetivoY);
  objetivoLine.setAttribute("stroke", "gray");
  objetivoLine.setAttribute("stroke-dasharray", "4");
  svg.appendChild(objetivoLine);

  meses.forEach((mes, i) => {
    const text = document.createElementNS(svgNS, "text");
    text.setAttribute("x", escX(i));
    text.setAttribute("y", h-5);
    text.setAttribute("text-anchor", "middle");
    text.setAttribute("font-size", "10");
    text.textContent = mes;
    svg.appendChild(text);
  });

  for (let i=0; i<=maxY; i+=Math.ceil(maxY/10)) {
    const text = document.createElementNS(svgNS, "text");
    text.setAttribute("x", padding-5);
    text.setAttribute("y", escY(i)+4);
    text.setAttribute("text-anchor", "end");
    text.setAttribute("font-size", "10");
    text.textContent = i;
    svg.appendChild(text);
  }

  contenedor.appendChild(svg);
}

// intenta ejecutarse siempre
setTimeout(dibujarGrafico, 0);

})();
</script>

</body>
</html>
