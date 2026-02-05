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

$anio_actual = (int)date('Y');
$anio_sel = isset($_GET['anio']) ? (int)$_GET['anio'] : $anio_actual;
if ($anio_sel < 2000 || $anio_sel > ($anio_actual + 1)) $anio_sel = $anio_actual;

$meses_ordenados = ['abril', 'mayo', 'junio', 'julio', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

// nombre carrera
$resCar = $cn->prepare("SELECT nom_car FROM carrera WHERE id_car = ?");
$resCar->bind_param("i", $id_car_sesion);
$resCar->execute();
$rCar = $resCar->get_result()->fetch_assoc();
$nombre_carrera = $rCar['nom_car'] ?? 'Mi Carrera';
$resCar->close();

// color carrera
$colores_carrera = [1=>'orange',2=>'red',3=>'purple',4=>'#2377e4ff',5=>'green'];
$color_actual = $colores_carrera[$id_car_sesion] ?? 'blue';

/**
 * YEAR_EXPR:
 * - si fecha_envio existe => YEAR(fecha_envio)
 * - si no => extrae 20xx desde numero_informe (MySQL 8+)
 */
$YEAR_EXPR = "
CASE
  WHEN fecha_envio IS NOT NULL THEN YEAR(fecha_envio)
  ELSE CAST(REGEXP_SUBSTR(numero_informe, '20[0-9]{2}') AS UNSIGNED)
END
";

function obtenerDataPorAnio(mysqli $cn, int $id_car, int $anio, string $YEAR_EXPR): array {
  $sql = "
    SELECT LOWER(mes_informe) AS mes, COUNT(*) AS cantidad
    FROM tutoria_informe_mensual
    WHERE estado_envio = 2
      AND id_car = ?
      AND ($YEAR_EXPR) = ?
    GROUP BY LOWER(mes_informe)
  ";
  $stmt = $cn->prepare($sql);
  if (!$stmt) return [];
  $stmt->bind_param("ii", $id_car, $anio);
  $stmt->execute();
  $res = $stmt->get_result();

  $data = [];
  while ($row = $res->fetch_assoc()) {
    $data[$row['mes']] = (int)$row['cantidad'];
  }
  $stmt->close();
  return $data;
}

/** años disponibles */
$anios = [];
$sqlYears = "
  SELECT DISTINCT ($YEAR_EXPR) AS anio
  FROM tutoria_informe_mensual
  WHERE estado_envio = 2
    AND id_car = ?
    AND ($YEAR_EXPR) IS NOT NULL
  ORDER BY anio DESC
";
$stmtYears = $cn->prepare($sqlYears);
$stmtYears->bind_param("i", $id_car_sesion);
$stmtYears->execute();
$resYears = $stmtYears->get_result();
while ($r = $resYears->fetch_assoc()) {
  if (!empty($r['anio'])) $anios[] = (int)$r['anio'];
}
$stmtYears->close();

// siempre incluir actual
$anios[] = $anio_actual;
$anios = array_values(array_unique($anios));
rsort($anios);

// data inicial
$data_inicial = obtenerDataPorAnio($cn, (int)$id_car_sesion, (int)$anio_sel, $YEAR_EXPR);

// endpoint ajax fijo (mismo folder)
$ajax_endpoint = '/vista/direccion_escuela/tendencia_cumplimiento_ajax.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Tendencia de Cumplimiento</title>
<style>
  body { margin:0; background:#f1f1f1; }
  .contenedor-principal{ width:95%; max-width:1000px; margin:20px auto; background:#fff; border-radius:6px; box-shadow:0 0 5px rgba(0,0,0,.2); overflow:hidden; font-family:Arial,sans-serif; }
  .titulo{ background:#2c3e50; color:#fff; padding:10px 16px; font-weight:bold; text-align:center; font-size:18px; position:relative; }
  .filtro-anio{ position:absolute; right:12px; top:50%; transform:translateY(-50%); display:flex; gap:8px; align-items:center; }
  .filtro-anio label{ font-weight:normal; font-size:12px; color:#fff; opacity:.95; }
  .filtro-anio select{ padding:4px 10px; border-radius:6px; border:1px solid rgba(255,255,255,.6); outline:none; font-size:12px; background:#fff; color:#111; min-width:90px; cursor:pointer; }
  .contenido{ padding:20px; text-align:center; background:#fff; }
  .grafico{ width:100%; aspect-ratio:2/1; margin:0 auto; border:1px solid #ccc; background:#fff; }
  .leyenda{ margin-top:20px; text-align:center; flex-wrap:wrap; }
  .leyenda div{ display:inline-block; margin:5px 10px; font-size:1rem; }
  .leyenda span{ display:inline-block; width:12px; height:12px; margin-right:5px; vertical-align:middle; }
  #estado{ margin-top:10px; font-size:13px; color:#444; display:none; }
  #sinDatos{ margin-top:10px; font-size:13px; color:#777; display:none; }
</style>
</head>
<body>

<div class="contenedor-principal">
  <div class="titulo">
    TENDENCIA DE CUMPLIMIENTO - TUTORES DE AULA - <?= htmlspecialchars($nombre_carrera) ?>

    <div class="filtro-anio">
      <label for="anio">Año:</label>
      <select id="anio">
        <?php foreach ($anios as $a): ?>
          <option value="<?= (int)$a ?>" <?= ($a === $anio_sel ? 'selected' : '') ?>><?= (int)$a ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="contenido">
    <div id="grafico" class="grafico"></div>
    <div id="estado"><strong>Cargando...</strong></div>
    <div id="sinDatos">No hay registros para el año seleccionado.</div>

    <div class="leyenda">
      <div><span style="background:<?= $color_actual ?>"></span><strong><?= htmlspecialchars($nombre_carrera) ?></strong></div>
    </div>
  </div>
</div>

<script>
(function(){
  const meses = <?php echo json_encode($meses_ordenados, JSON_UNESCAPED_UNICODE); ?>;
  const color = "<?php echo $color_actual; ?>";
  let datos = <?php echo json_encode($data_inicial, JSON_UNESCAPED_UNICODE); ?>;
  let anio  = <?php echo (int)$anio_sel; ?>;

  const sel = document.getElementById('anio');
  const estado = document.getElementById('estado');
  const sinDatos = document.getElementById('sinDatos');

  // endpoint fijo y correcto
  const ENDPOINT = new URL("<?php echo $ajax_endpoint; ?>", window.location.href).toString();

  function escX(i, w, p){ return p + i * ((w - 2*p) / (meses.length - 1)); }
  function escY(val, h, p, maxY){ return h - p - (val * (h - 2*p) / maxY); }

  function hayRegistros(obj){
    if (!obj) return false;
    for (const k in obj) {
      if ((obj[k] ?? 0) > 0) return true;
    }
    return false;
  }

  function dibujarGrafico() {
    const w = 800, h = 400, p = 40;
    const cont = document.getElementById("grafico");
    cont.innerHTML = "";

    sinDatos.style.display = hayRegistros(datos) ? "none" : "block";

    const maxData = Math.max(...meses.map(m => (datos[m] ?? 0)));
    const maxY = Math.max(maxData, 30);

    const svgNS = "http://www.w3.org/2000/svg";
    const svg = document.createElementNS(svgNS, "svg");
    svg.setAttribute("viewBox", `0 0 ${w} ${h}`);
    svg.setAttribute("preserveAspectRatio", "xMidYMid meet");
    svg.style.width = "100%";
    svg.style.height = "100%";

    // ejes
    const xAxis = document.createElementNS(svgNS, "line");
    xAxis.setAttribute("x1", p); xAxis.setAttribute("y1", h-p);
    xAxis.setAttribute("x2", w-p); xAxis.setAttribute("y2", h-p);
    xAxis.setAttribute("stroke", "#000");
    svg.appendChild(xAxis);

    const yAxis = document.createElementNS(svgNS, "line");
    yAxis.setAttribute("x1", p); yAxis.setAttribute("y1", p);
    yAxis.setAttribute("x2", p); yAxis.setAttribute("y2", h-p);
    yAxis.setAttribute("stroke", "#000");
    svg.appendChild(yAxis);

    // separador semestres
    const idxJulio = meses.indexOf('julio');
    const idxSept  = meses.indexOf('septiembre');
    if (idxJulio !== -1 && idxSept !== -1) {
      const xCorte = (escX(idxJulio, w, p) + escX(idxSept, w, p)) / 2;

      const sep = document.createElementNS(svgNS, "line");
      sep.setAttribute("x1", xCorte); sep.setAttribute("y1", p);
      sep.setAttribute("x2", xCorte); sep.setAttribute("y2", h-p);
      sep.setAttribute("stroke", "#666");
      sep.setAttribute("stroke-dasharray", "6 6");
      svg.appendChild(sep);

      const yRot = (h - p) + 22;

      const t1 = document.createElementNS(svgNS, "text");
      t1.setAttribute("x", (p + xCorte)/2);
      t1.setAttribute("y", yRot);
      t1.setAttribute("text-anchor", "middle");
      t1.setAttribute("font-size", "10");
      t1.textContent = anio + " - I";
      svg.appendChild(t1);

      const t2 = document.createElementNS(svgNS, "text");
      t2.setAttribute("x", (xCorte + (w-p))/2);
      t2.setAttribute("y", yRot);
      t2.setAttribute("text-anchor", "middle");
      t2.setAttribute("font-size", "10");
      t2.textContent = anio + " - II";
      svg.appendChild(t2);
    }

    // línea
    const path = document.createElementNS(svgNS, "path");
    let d = "";
    meses.forEach((mes, i) => {
      const x = escX(i, w, p);
      const y = escY((datos[mes] ?? 0), h, p, maxY);
      d += (i===0 ? "M":"L") + x + " " + y + " ";
    });
    path.setAttribute("d", d);
    path.setAttribute("fill", "none");
    path.setAttribute("stroke", color);
    path.setAttribute("stroke-width", 2);
    svg.appendChild(path);

    // puntos + valores
    meses.forEach((mes, i) => {
      const x = escX(i, w, p);
      const val = datos[mes] ?? 0;
      const y = escY(val, h, p, maxY);

      const c = document.createElementNS(svgNS, "circle");
      c.setAttribute("cx", x); c.setAttribute("cy", y);
      c.setAttribute("r", 3); c.setAttribute("fill", color);
      svg.appendChild(c);

      const t = document.createElementNS(svgNS, "text");
      t.setAttribute("x", x); t.setAttribute("y", y-5);
      t.setAttribute("text-anchor", "middle");
      t.setAttribute("font-size", "10");
      t.textContent = val;
      svg.appendChild(t);
    });

    // meses
    meses.forEach((mes, i) => {
      const t = document.createElementNS(svgNS, "text");
      t.setAttribute("x", escX(i, w, p));
      t.setAttribute("y", h-5);
      t.setAttribute("text-anchor", "middle");
      t.setAttribute("font-size", "10");
      t.textContent = mes;
      svg.appendChild(t);
    });

    // escala Y
    for (let i=0; i<=maxY; i+=Math.ceil(maxY/10)) {
      const t = document.createElementNS(svgNS, "text");
      t.setAttribute("x", p-5);
      t.setAttribute("y", escY(i, h, p, maxY)+4);
      t.setAttribute("text-anchor", "end");
      t.setAttribute("font-size", "10");
      t.textContent = i;
      svg.appendChild(t);
    }

    cont.appendChild(svg);
  }

  async function cargarAnio(nuevoAnio){
    estado.style.display = 'block';
    sinDatos.style.display = 'none';

    try {
      const url = new URL(ENDPOINT);
      url.searchParams.set("anio", String(nuevoAnio));

      const resp = await fetch(url.toString(), { method:'GET', headers: { 'Accept': 'application/json' } });
      if (!resp.ok) throw new Error('HTTP ' + resp.status);

      const json = await resp.json();
      if (!json || json.ok !== true) throw new Error('JSON inválido');

      anio  = Number(json.anio);
      datos = json.data || {};

      dibujarGrafico();
    } catch (e) {
      console.error("Error AJAX:", e);
      // Si falla, vuelve a dibujar (mantiene el último estado) pero quita "Cargando..."
      dibujarGrafico();
      alert("No se pudo cargar la información del año seleccionado. Revisa consola (F12) y la ruta del endpoint.");
    } finally {
      estado.style.display = 'none';
    }
  }

  sel.addEventListener('change', (e) => cargarAnio(e.target.value));

  dibujarGrafico();
})();
</script>

</body>
</html>
