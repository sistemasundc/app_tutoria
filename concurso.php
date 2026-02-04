<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>Nota de Prensa - UNDC</title>

  <!-- Bootstrap 4.6 solo para la estructura general de la nota -->
  <link rel="stylesheet"
        href="https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/css/bootstrap.min.css">

  <!-- Splide CSS (slider) -->
  <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/css/splide.min.css"/>

  <style>
    body {
      background-color: #f4f6f9;
      font-family: "Inter", "Roboto", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color: #2c3e50;
      line-height: 1.5;
    }

    .press-wrapper {
      max-width: 900px;
      margin: 2rem auto 4rem auto;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 20px 40px rgba(0,0,0,.08);
      border: 1px solid rgba(0,0,0,.06);
      overflow: hidden;
    }

    /* ===== Encabezado institucional ===== */
    .press-header {
      background: linear-gradient(90deg, #002b5c 0%, #005b96 100%);
      color: #fff;
      padding: 1.5rem 2rem;
      display: flex;
      align-items: center;
    }
    .press-header-logo {
      width: 64px;
      height: 64px;
      border-radius: 8px;
      background: #fff;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .7rem;
      font-weight: 600;
      color: #002b5c;
      text-align: center;
      line-height: 1.1;
      margin-right: 1rem;
      overflow: hidden;
    }
    .press-header-logo img {
      max-width:100%;
      max-height:100%;
      object-fit:contain;
    }
    .press-header-text h2 {
      font-size: 20px;
      font-weight: 600;
      text-transform: uppercase;
      margin: 0;
      letter-spacing: .05em;
    }
    .press-header-text span {
      font-size: .75rem;
      opacity: .8;
    }

    .press-body {
      padding: 2rem;
    }

    /* ===== Slider estilo "acolchado lateral" ===== */
    .slider-shell {
      background: #1d2224;           /* bloque oscuro externo */
      border: 1px solid #2c3235;
      border-radius: 8px;
      box-shadow: 0 12px 32px rgba(0,0,0,.4);
      margin-bottom: 2rem;
      padding: 1rem 1rem 2.5rem;
      position: relative;
    }

    /* Área interna gris oscuro donde vive la imagen */
    .slider-frame {
      background: #2b3235;
      border-radius: 4px;
      min-height: 260px;
      max-height: 360px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
    }

    .slider-frame img {
      width: 100%;
      height: auto;
      max-height: 360px;
      object-fit: cover;
      object-position: center;
      display: block;
    }

    /* Ajustes Splide para que ocupe el frame */
    .splide__track {
      border-radius: 4px;
      overflow: hidden;
    }

    /* Botones anterior / siguiente */
    .splide__arrow {
      background: rgba(0,0,0,.6);
      border: 1px solid rgba(255,255,255,.25);
      width: 44px;
      height: 44px;
      border-radius: 4px;
      box-shadow: 0 8px 16px rgba(0,0,0,.6);
      transition: all .2s ease;
      color: #fff;
      opacity: 1;
    }

    .splide__arrow:hover {
      background: rgba(0,0,0,.8);
    }

    .splide__arrow svg {
      fill: #fff;
      filter: drop-shadow(0 0 3px rgba(0,0,0,.8));
      width: 20px;
      height: 20px;
    }

    /* Puntos indicadores (pagination) */
    .splide__pagination {
      position: absolute;
      bottom: .5rem;
      left: 0;
      right: 0;
      display: flex;
      justify-content: center;
    }

    .splide__pagination__page {
      background: #9ca3af;
      opacity: .6;
      width: 10px;
      height: 10px;
      margin: 0 4px;
      border-radius: 50%;
      border: none;
      transition: all .15s linear;
    }

    .splide__pagination__page.is-active {
      background: #c9ff2f; /* verde lima tipo referencia */
      opacity: 1;
      box-shadow: 0 0 8px rgba(201,255,47,.8);
      transform: scale(1.05);
    }

    /* ===== Texto de la nota ===== */
    .press-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: #1a2a41;
      text-transform: uppercase;
      line-height: 1.3;
      margin-bottom: 1rem;
    }

    .press-meta {
      font-size: .9rem;
      font-weight: 500;
      color: #005b96;
      background: rgba(0,91,150,.07);
      border-left: 4px solid #005b96;
      padding: .75rem 1rem;
      border-radius: 6px;
      margin-bottom: 1.5rem;

      /* borde azul abajo tipo subrayado institucional */
      border-bottom: 3px solid #005b96;
    }

    .press-content p {
      text-align: justify;
      font-size: 1rem;
      color: #2c3e50;
      margin-bottom: 1rem;
    }

    /* ===== Pie ===== */
    .press-footer {
      border-top: 1px solid rgba(0,0,0,.06);
      background: #fafafa;
      padding: 1rem 2rem;
      font-size: .8rem;
      color: #6b7280;
    }
    .press-footer .label {
      font-weight: 600;
      color: #2c3e50;
      font-size: .8rem;
      display: block;
      margin-bottom: .25rem;
    }

    /* Responsive básico */
    @media (max-width: 576px) {
      .press-header {
        flex-direction: row;
        padding: 1rem 1.25rem;
      }
      .press-header-logo {
        width: 56px;
        height: 56px;
        margin-right: .75rem;
      }
      .press-header-text h2 {
        font-size: .75rem;
      }
      .press-body {
        padding: 1.25rem;
      }
      .press-title {
        font-size: 1.2rem;
      }
      .slider-frame {
        max-height: 260px;
        min-height: 220px;
      }
      .slider-frame img {
        max-height: 260px;
      }
    }
  </style>
</head>
<body>

<div class="press-wrapper">

  <!-- Encabezado Institucional -->
  <div class="press-header">
    <div class="press-header-logo">
      <img src="imagen/logo-uni.png" alt="UNDC">
    </div>
    <div class="press-header-text">
      <h2>Universidad Nacional de Cañete</h2>
      <!-- <span>Nota de prensa institucional</span> -->
    </div>
  </div>

  <!-- Cuerpo -->
  <div class="press-body">


    <!-- Título de la nota -->
    <div class="press-title">
      LA UNIVERSIDAD NACIONAL DE CAÑETE PARTICIPÓ EN EL PRIMER CONCURSO NACIONAL DE BUENAS PRÁCTICAS EN CALIDAD DE SERVICIOS
    </div>

    <!-- Meta / Fecha y lugar -->
    <div class="press-meta">
      Lima, 23 de octubre de 2025 · Auditorio Mario Vargas Llosa, Biblioteca Nacional del Perú
    </div>

    <!-- Texto principal -->
    <div class="press-content">
      <p>
        La Universidad Nacional de Cañete formó parte del Primer Concurso Nacional de
        Buenas Prácticas en Calidad de Servicios, organizado por la Presidencia del Consejo de Ministros (PCM),
        a través de la Secretaría de Gestión Pública.
      </p>

      <p>
        El evento se desarrolló el 23 de octubre de 2025 en el Auditorio Mario Vargas Llosa de la Biblioteca
        Nacional del Perú, reuniendo a representantes de instituciones públicas de todo el país comprometidas
        con la mejora continua y la innovación en la atención al ciudadano.
      </p>

      <p>
        En esta primera edición, se presentaron más de 220 prácticas provenientes de diversas entidades públicas
        nacionales, regionales y locales, entre ellas la participación de la Universidad Nacional de Cañete,
        reafirmando su compromiso con la calidad del servicio público universitario y la gestión moderna orientada
        al ciudadano.
      </p>

      <p>
        La UNDC continuará fortaleciendo su participación en espacios que promuevan la excelencia y la
        transparencia institucional, contribuyendo desde la academia al desarrollo de un Estado más eficiente
        y cercano a las personas.
      </p>
    </div>

        <!-- Slider tipo "acolchado lateral" -->
    <div class="slider-shell">
      <div id="slider-undc" class="splide">
        <div class="splide__track">
          <ul class="splide__list">

            <!-- Slide 1 -->
            <li class="splide__slide">
              <div class="slider-frame">
                <img src="imagen/grupal1.png" alt="Participación de la UNDC en la ceremonia de premiación">
              </div>
            </li>

            <!-- Slide 2 -->
            <li class="splide__slide">
              <div class="slider-frame">
                <img src="imagen/auditorio.jpg" alt="Auditorio Mario Vargas Llosa, Biblioteca Nacional del Perú">
              </div>
            </li>

            <!-- Slide 3 -->
            <li class="splide__slide">
              <div class="slider-frame">
                <img src="imagen/grupal2.png" alt="Primer Concurso Nacional de Buenas Prácticas en Calidad de Servicios">
              </div>
            </li>

          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Pie institucional -->
  <div class="press-footer">
    <div class="label">Universidad Nacional de Cañete - 2025</div>
<!--     <div>Oficina de Comunicación e Imagen Institucional</div>
    <div>Nota de prensa · 2025</div> -->
  </div>

</div>

<!-- jQuery + Bootstrap (para la estructura general) -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/js/bootstrap.min.js"></script>

<!-- Splide JS (para el slider horizontal tipo referencia) -->
<script src="https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js"></script>

<script>
  document.addEventListener( 'DOMContentLoaded', function () {
    new Splide( '#slider-undc', {
      type     : 'loop',     // bucle infinito
      perPage  : 1,          // 1 imagen por vista
      gap      : '1rem',     // espacio interno entre imagen y borde oscuro
      padding  : '4rem',     // acolchado lateral (izq/der) tipo la referencia
      arrows   : true,       // flechas
      pagination: true,      // puntitos
      autoplay : true,       // pase automático
      interval : 5000,       // cada 5s
      pauseOnHover: true,
      pauseOnFocus: true,
    } ).mount();
  } );
</script>

</body>
</html>
