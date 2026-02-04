<?php 
// Conexión
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . '/../../modelo/modelo_conexion.php');
$conexion = new conexion();
$cn = $conexion->conectar();

// Traer datos
$sql = "
SELECT id_car, LOWER(mes_informe) AS mes, COUNT(*) AS cantidad
FROM tutoria_informe_mensual
WHERE estado_envio=2
GROUP BY id_car, mes_informe
";
/* $sql = "
SELECT id_car, LOWER(mes_informe) AS mes, COUNT(*) AS cantidad
FROM tutoria_informe_mensual
WHERE estado_envio=2
GROUP BY id_car, mes_informe
UNION ALL
SELECT id_car, LOWER(mes_informe) AS mes, COUNT(*) AS cantidad
FROM tutoria_informe_mensual_curso
WHERE estado_envio=2
GROUP BY id_car, mes_informe
";
 */
$res = $cn->query($sql);

$data = [];
$meses_encontrados = [];
while ($row = $res->fetch_assoc()) {
    $mes = $row['mes'];
    $car = $row['id_car'];
    $cantidad = $row['cantidad'];

    if (!isset($data[$mes])) $data[$mes] = [];
    if (!isset($data[$mes][$car])) $data[$mes][$car] = 0;

    $data[$mes][$car] += $cantidad;

    if (!in_array($mes, $meses_encontrados)) {
        $meses_encontrados[] = $mes;
    }
}

// Ordenar meses reales en orden cronológico
$orden_meses = ['enero','febrero','marzo','abril','mayo','junio','julio','septiembre','octubre','noviembre','diciembre'];
$meses_ordenados = array_values(array_filter($orden_meses, fn($m) => in_array($m, $meses_encontrados)));
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
  font-size: 1.4rem;
}
.leyenda span {
  display: inline-block;
  width: 12px;
  height: 12px;
  margin-right: 5px;
  vertical-align: middle;
}
</style>
</head>
<body>

<div class="contenedor-principal">
  <div class="titulo">TENDENCIA DE CUMPLIMIENTO POR CARRERA - 2025</div>
  <div class="contenido">
    <div id="grafico" class="grafico"></div>

    <div class="leyenda">
      <div><span style="background:blue"></span> Ingeniería de Sistemas</div>
      <div><span style="background:red"></span> Contabilidad</div>
      <div><span style="background:orange"></span> Administración</div>
      <div><span style="background:purple"></span> Administración Turismo y Hotelería</div>
      <div><span style="background:green"></span> Agronomía</div>
    </div>
  </div>
</div>

<script>
// Espera a que el #grafico exista y lo dibuja
function esperarYdibujar() {
  const contenedor = document.getElementById("grafico");
  if (!contenedor) {
    setTimeout(esperarYdibujar, 100);
    return;
  }
  dibujarGrafico();
}

function dibujarGrafico() {
  const datos = <?php echo json_encode($data); ?>;
  const meses = <?php echo json_encode($meses_ordenados); ?>;
  const colores = {4:'blue',2:'red',1:'orange',3:'purple',5:'green'};
  const carreras = [4,2,1,3,5];

  const w = 800, h = 400, padding = 40;
  const maxY = Math.max(...Object.values(datos).flatMap(m => Object.values(m))) + 2;

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

  // ===== Separador de semestres (línea punteada + rótulos) =====
    const idxJulio = meses.indexOf('julio');
    const idxSept  = meses.indexOf('septiembre');

    // Solo si existen ambos meses en el arreglo
    if (idxJulio !== -1 && idxSept !== -1) {
      // X de julio y septiembre
      const xJulio = escX(idxJulio);
      const xSept  = escX(idxSept);

      // Punto medio para la línea separadora
      const xCorte = (xJulio + xSept) / 2;

      // Línea vertical punteada
      const separador = document.createElementNS(svgNS, "line");
      separador.setAttribute("x1", xCorte);
      separador.setAttribute("y1", padding);
      separador.setAttribute("x2", xCorte);
      separador.setAttribute("y2", h - padding);
      separador.setAttribute("stroke", "#666");
      separador.setAttribute("stroke-dasharray", "6 6");
      separador.setAttribute("stroke-width", 1);
      svg.appendChild(separador);

      // Rótulos de semestre debajo del eje X
      const yRotulo = (h - padding) + 22; // un poco debajo del eje
      const xRotuloI  = (padding + xCorte) / 2;        // centro del tramo izquierdo
      const xRotuloII = (xCorte + (w - padding)) / 2;  // centro del tramo derecho

      const rotuloI = document.createElementNS(svgNS, "text");
      rotuloI.setAttribute("x", xRotuloI);
      rotuloI.setAttribute("y", yRotulo);
      rotuloI.setAttribute("text-anchor", "middle");
      rotuloI.setAttribute("font-size", "10");
      rotuloI.textContent = "2025 - I";
      svg.appendChild(rotuloI); 

      const rotuloII = document.createElementNS(svgNS, "text");
      rotuloII.setAttribute("x", xRotuloII);
      rotuloII.setAttribute("y", yRotulo);
      rotuloII.setAttribute("text-anchor", "middle");
      rotuloII.setAttribute("font-size", "10");
      rotuloII.textContent = "2025 - II";
      svg.appendChild(rotuloII);
    }

  function escX(i) {
    return padding + i * ((w-2*padding)/(meses.length-1));
  }
  function escY(val) {
    return h-padding - (val*(h-2*padding)/maxY);
  }

  carreras.forEach(car => {
    const path = document.createElementNS(svgNS, "path");
    let d = "";

    meses.forEach((mes, i) => {
      const valor = (datos[mes] && datos[mes][car]) ? datos[mes][car] : 0;
      const x = escX(i);
      const y = escY(valor);
      d += (i == 0 ? "M" : "L") + x + " " + y + " ";

      //  Agrega el punto
      const punto = document.createElementNS(svgNS, "circle");
      punto.setAttribute("cx", x);
      punto.setAttribute("cy", y);
      punto.setAttribute("r", 3);
      punto.setAttribute("fill", colores[car]);
      punto.setAttribute("stroke", "#fff");
      punto.setAttribute("stroke-width", 1);
      svg.appendChild(punto);
    });

    path.setAttribute("d", d);
    path.setAttribute("fill", "none");
    path.setAttribute("stroke", colores[car]);
    path.setAttribute("stroke-width", 2);
    svg.appendChild(path);
  });

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

  const graficoDiv = document.getElementById("grafico");
  graficoDiv.innerHTML = ""; // limpia antes de redibujar
  graficoDiv.appendChild(svg);
}

// Ejecutar al cargar o reinsertar en el DOM
esperarYdibujar();
</script>


</body>
</html>
