//GRAFICO DE TENDENCIAS DE CIUMPLIEMNTO DOCENTE
function inicializarGraficoTendencia(datos, meses, color) {
    const contenedor = document.getElementById("grafico");
    contenedor.innerHTML = ""; 
    const tieneDatos = meses.some(m => (datos[m] ?? 0) > 0);
    const w = 800, h = 400, padding = 40;

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

    contenedor.appendChild(svg);
}