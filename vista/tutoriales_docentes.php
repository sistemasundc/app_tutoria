<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SISTECU – UNDC | Tutoriales y Manuales</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --primary:#3c8dbc;  /* azul institucional */
      --ink:#2c3e50; --ink-muted:#6b7280;
      --paper:#fff; --surface:#f4f6f9; --border:rgba(0,0,0,.08);
      --shadow:0 10px 25px rgba(0,0,0,.08);
      --radius:16px;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;background:var(--surface);color:var(--ink);line-height:1.55}
    a{color:inherit;text-decoration:none}
    .container{width:min(1200px,94vw);margin-inline:auto}

    /* Chips toolbar */
    .toolbar{
      display:flex; align-items:center; gap:10px; flex-wrap:wrap;
      padding:12px 14px;
      margin:12px auto 18px;        /* auto en los lados = centrada */
      width:fit-content;            /* se ajusta al contenido */
      max-width:100%;               /* no se desborda */
      border:1px solid var(--border);
      border-radius:16px;
      background:var(--paper);
      box-shadow:var(--shadow);
      justify-content:center;
    }
    @media (max-width: 900px){
      .toolbar{
        width:100%;
        justify-content:flex-start; /* o center si la prefieres centrada también en móvil */
      }
    }
    .chip{display:inline-flex;align-items:center;gap:.45rem;padding:9px 14px;
      border-radius:999px;border:1px solid var(--border);background:#fff;
      cursor:pointer;user-select:none;font-weight:700;color:var(--ink)}
    .chip i{opacity:.9}
    .chip.active{background:var(--primary);color:#fff;border-color:var(--primary)}

    /* Secciones */
    .section-head{display:flex;align-items:end;justify-content:space-between;margin:14px 0}
    .section-head h2{margin:0;font-size:1.1rem}
    .section-head .sub{color:var(--ink-muted);font-size:.95rem}

    /* Grid */
    .grid{display:grid;grid-template-columns:repeat(12,1fr);gap:18px}
    .col-6{grid-column:span 6}
    .col-12{grid-column:span 12}
    @media (max-width:1024px){.col-6{grid-column:span 12}}

    /* Tarjeta de video */
    .card{position:relative;border:1px solid var(--border);background:#fff;border-radius:16px;overflow:hidden;box-shadow:var(--shadow);transition:transform .15s}
    .card:hover{transform:translateY(-1px)}
    .card .thumb{position:relative;aspect-ratio:16/9;overflow:hidden;border-bottom:1px solid var(--border);background:#eef2f7}
    .card iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
    .card .body{padding:12px 14px;position:relative}
    .card h3{font-size:1rem;margin:0 0 6px}
    .btn{display:inline-flex;align-items:center;gap:.5rem;border:1px solid var(--border);background:#fff;color:var(--ink);padding:.55rem .85rem;border-radius:12px;cursor:pointer;font-weight:600}
    .btn:hover{transform:translateY(-1px);box-shadow:0 8px 18px rgba(0,0,0,.08)}
    .btn.primary{background:var(--primary);color:#fff;border-color:var(--primary)}
    .btn.icon{position:absolute;right:12px;top:12px}
    .badge{display:inline-flex;gap:.35rem;align-items:center;padding:.25rem .5rem;border:1px solid var(--border);border-radius:999px;font-size:.8rem;color:#fff}

    /* Manuales */
    .docs{display:grid;grid-template-columns:repeat(12,1fr);gap:18px}
    .doc-card{grid-column:span 4;border:1px dashed var(--border);border-radius:16px;background:rgba(255,255,255,.6);padding:18px;display:flex;align-items:center;gap:14px}
    .doc-icon{width:64px;height:64px;border-radius:14px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#ef4444,#f97316)}
    .doc-actions{display:flex;gap:8px;flex-wrap:wrap}

    /* Modal */
    .modal{position:fixed;inset:0;display:none;place-items:center;background:rgba(0,0,0,.55);z-index:9999}
    .modal.open{display:grid}
    .modal-dialog{width:min(1200px,96vw);height:min(90vh,900px);background:#fff;border:1px solid var(--border);border-radius:18px;overflow:hidden;box-shadow:var(--shadow);display:flex;flex-direction:column}
    .modal-header{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid var(--border)}
    .modal-header h5{margin:0;font-size:1rem}
    .modal-body{flex:1;position:relative}
    .modal-body iframe{position:absolute;inset:0;width:100%;height:100%;border:0}

    footer{border-top:1px solid var(--border);color:var(--ink-muted);background:#fff;padding:22px 0;margin-top:28px;box-shadow:var(--shadow)}
  </style>
</head>
<body>

  <!-- FRANJA DE BOTONES (chips) -->
  <div class="container toolbar" id="chipsBar">
    <button class="chip active" data-filter="all"><i class="fa-solid fa-grip"></i> Todo</button>
    <button class="chip" data-filter="plan"><i class="fa-solid fa-chalkboard-user"></i> Plan de Tutoría</button>
    <button class="chip" data-filter="informe"><i class="fa-solid fa-people-line"></i> Informe Mensual</button>
    <button class="chip" data-filter="sesiones"><i class="fa-solid fa-clipboard-list"></i> Registro de Sesiones</button>
    <button class="chip" data-filter="derivacion"><i class="fa-solid fa-file-pen"></i> Derivación</button>
    <button class="chip" data-filter="manuales"><i class="fa-solid fa-book"></i> Manual de Usuario</button>
  </div>

  <!-- VIDEOS -->
  <section id="videos" class="container">
      <div class="section-head">
        <span class="sub">Guías prácticas en menos de 5 minutos</span>
      </div>

    <div class="grid" id="videoGrid">
      <!-- 1: Plan -->
      <article class="card col-6 filterable" data-tags="plan">
        <div class="thumb">
          <iframe src="https://drive.google.com/file/d/1gNP_l9ZJ3c8Hw3qWSupQQP2EmIGxtMzu/preview"
                  loading="lazy" title="Plan de Tutoría" allow="autoplay"></iframe>
        </div>
        <div class="body">
          <button class="btn icon" data-open-newtab title="Abrir en pestaña"><i class="fa-solid fa-up-right-from-square"></i></button>
          <h3><i class="fa-solid fa-circle-play"></i> Plan de Tutoría</h3>
          <div class="badge"><i class="fa-regular fa-clock"></i> 2.18 min</div>
        </div>
      </article>

      <!-- 2: Informe -->
      <article class="card col-6 filterable" data-tags="informe">
        <div class="thumb">
          <iframe src="https://drive.google.com/file/d/1CG8N3fPXmE1B8GGIfkTcQQv7wYpRXLHn/preview"
                  loading="lazy" title="Informe Mensual" allow="autoplay"></iframe>
        </div>
        <div class="body">
          <button class="btn icon" data-open-newtab title="Abrir en pestaña"><i class="fa-solid fa-up-right-from-square"></i></button>
          <h3><i class="fa-solid fa-circle-play"></i> Informe Mensual</h3>
          <div class="badge"><i class="fa-regular fa-clock"></i> 3.40 min</div>
        </div>
      </article>

      <!-- 3: Sesiones -->
      <article class="card col-6 filterable" data-tags="sesiones">
        <div class="thumb">
          <iframe src="https://drive.google.com/file/d/1vl1aMxV2EeuJm6PuvvE3Y7RckayINaJi/preview"
                  loading="lazy" title="Registro de Sesiones de Tutoría" allow="autoplay"></iframe>
        </div>
        <div class="body">
          <button class="btn icon" data-open-newtab title="Abrir en pestaña"><i class="fa-solid fa-up-right-from-square"></i></button>
          <h3><i class="fa-solid fa-circle-play"></i> Registro de Sesiones de Tutoría</h3>
          <div class="badge"><i class="fa-regular fa-clock"></i> 3.40 min</div>
        </div>
      </article>

      <!-- 4: Derivación -->
      <article class="card col-6 filterable" data-tags="derivacion">
        <div class="thumb">
          <iframe src="https://drive.google.com/file/d/1x1mKx2GuV_6UKlFFWupSoKTw6dxB2zdE/preview"
                  loading="lazy" title="Derivación de Estudiantes a Servicios Especializados" allow="autoplay"></iframe>
        </div>
        <div class="body">
          <button class="btn icon" data-open-newtab title="Abrir en pestaña"><i class="fa-solid fa-up-right-from-square"></i></button>
          <h3><i class="fa-solid fa-circle-play"></i> Derivación de Estudiantes a Servicios Especializados</h3>
          <div class="badge"><i class="fa-regular fa-clock"></i> 1.17 min</div>
        </div>
      </article>
    </div>
 </section>
  <!-- MANUALES -->
  <section id="docs" class="container">
    <div class="section-head">
      <span class="sub">Formatos PDF con pasos detallados</span>
    </div>

    <div class="docs">
      <div class="doc-card filterable" data-tags="manuales">
        <div class="doc-icon" aria-hidden="true"><i class="fa-solid fa-file-pdf"></i></div>
        <div class="doc-info">
          <h4>Manual del Tutor de Aula</h4>
          <p style="margin:.2rem 0;color:var(--ink-muted)">Versión vigente · Descarga y consulta en línea</p>
          <div class="doc-actions">
            <button class="btn" 
                    data-open-modal 
                    data-title="Manual del Tutor de Aula" 
                    data-src="https://drive.google.com/file/d/1r1a5Q-z4kZydsY4nAQj5ZPv7gcPvpfIX/preview">
              <i class="fa-solid fa-eye"></i> Ver en línea
            </button>

            <!-- abrir en nueva pestaña para evitar bloqueos -->
            <a class="btn primary" 
              href="https://drive.google.com/uc?export=download&id=1r1a5Q-z4kZydsY4nAQj5ZPv7gcPvpfIX"
              target="_blank" rel="noopener">
              <i class="fa-solid fa-download"></i> Descargar
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- MODAL VISOR -->
  <div class="modal" id="viewer" role="dialog" aria-modal="true" aria-labelledby="viewerTitle">
    <div class="modal-dialog">
      <div class="modal-header">
        <h5 id="viewerTitle">Documento</h5>
        <div style="display:flex;gap:.5rem">
          <button class="btn" id="openNewTab" title="Abrir en pestaña"><i class="fa-solid fa-arrow-up-right-from-square"></i></button>
          <button class="btn" id="closeModal" title="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </div>
      <div class="modal-body">
        <iframe id="viewerFrame" src="about:blank" title="Visor de documento"></iframe>
      </div>
    </div>
  </div>

  
<script>
  // --------- FILTRO POR CHIPS ----------
  (function(){
    const chips = Array.from(document.querySelectorAll('#chipsBar .chip'));
    const items = Array.from(document.querySelectorAll('.filterable'));
    let active = 'all';

    function parseTags(el){
      return (el.dataset.tags || '')
        .toLowerCase()
        .split(',')
        .map(s => s.trim())
        .filter(Boolean);
    }

    function apply(){
      items.forEach(el=>{
        const tags = parseTags(el); // ["plan"], ["informe"], etc.
        const show = (active === 'all') || tags.includes(active);
        el.style.display = show ? '' : 'none';
      });
    }

    chips.forEach(ch=>{
      ch.addEventListener('click', ()=>{
        chips.forEach(c=>c.classList.remove('active'));
        ch.classList.add('active');
        active = (ch.dataset.filter || 'all').toLowerCase();
        apply();

        // scroll suave al inicio de los videos o manuales
        const anchor = document.getElementById('videoGrid') || document.getElementById('docs');
        if(anchor) anchor.scrollIntoView({behavior:'smooth', block:'start'});
      });
    });

    apply(); // estado inicial
  })();

  // --------- MODAL VISOR DE MANUAL ----------
  (function(){
    const modal   = document.getElementById('viewer');
    const frame   = document.getElementById('viewerFrame');
    const titleEl = document.getElementById('viewerTitle');
    const btnClose= document.getElementById('closeModal');
    const btnTab  = document.getElementById('openNewTab');
    let lastSrc   = '';

    document.querySelectorAll('[data-open-modal]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        titleEl.textContent = btn.dataset.title || 'Documento';
        lastSrc = btn.dataset.src || '';
        frame.src = lastSrc || 'about:blank';
        modal.classList.add('open');
      });
    });

    btnClose.addEventListener('click', ()=>{
      modal.classList.remove('open');
      frame.src = 'about:blank';
    });

    btnTab.addEventListener('click', ()=>{
      if(lastSrc) window.open(lastSrc, '_blank');
    });

    modal.addEventListener('click', e=>{
      if(e.target === modal) btnClose.click();
    });

    document.addEventListener('keydown', e=>{
      if(e.key === 'Escape' && modal.classList.contains('open')) btnClose.click();
    });
  })();

  // --------- ABRIR VIDEO EN NUEVA PESTAÑA ----------
  document.querySelectorAll('[data-open-newtab]').forEach(b=>{
    b.addEventListener('click', e=>{
      e.stopPropagation();
      const iframe = b.closest('.card')?.querySelector('iframe');
      if(iframe && iframe.src) window.open(iframe.src, '_blank');
    });
  });
</script>

</body>
</html>
