<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SISTECU – UNDC | Capacitación para Docentes Tutores</title>

  <!-- Iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <!-- Tipografías -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg:#0f172a;            /* fondo oscuro */
      --card:#0b1224;          /* tarjetas oscuro */
      --txt:#e5e7eb;           /* texto base */
      --muted:#9ca3af;         /* texto suave */
      --brand:#22c55e;         /* verde UNDC moderno */
      --brand-2:#00a3ff;       /* acento azul */
      --ring:rgba(34,197,94,.35);
      --ring-2:rgba(0,163,255,.35);
      --border:rgba(255,255,255,.12);
      --paper:#ffffff;         /* fondo claro */
      --ink:#1f2937;           /* texto oscuro */
      --ink-muted:#6b7280;
      --surface:#f8fafc;       /* superficies claras */
      --shadow:0 10px 30px rgba(2,8,23,.35);
      --radius:16px;
    }
    /* Reset/estructura */
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0; font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial,sans-serif;
      color:var(--txt); background:radial-gradient(1200px 600px at 10% -10%, rgba(0,163,255,.25), transparent 60%),
                        radial-gradient(900px 500px at 100% 0%, rgba(34,197,94,.18), transparent 50%),
                        var(--bg);
      line-height:1.55;
    }
    a{color:inherit; text-decoration:none}
    img{max-width:100%; display:block}
    .container{width:min(1200px, 92vw); margin-inline:auto}

    /* Header */
    header{
      position:sticky; top:0; z-index:40; backdrop-filter:saturate(120%) blur(10px);
      background:linear-gradient(to right, rgba(11,18,36,.85), rgba(15,23,42,.65));
      border-bottom:1px solid var(--border);
    }
    .nav{display:flex; align-items:center; justify-content:space-between; padding:.9rem 0}
    .brand{display:flex; align-items:center; gap:.75rem}
    .brand-badge{height:40px; width:40px; border-radius:12px; display:grid; place-items:center; color:#fff;
      background:linear-gradient(135deg, var(--brand), var(--brand-2)); box-shadow:var(--shadow)}
    .brand h1{font-size:1.05rem; margin:0; font-weight:700; letter-spacing:.3px}
    .nav-actions{display:flex; align-items:center; gap:.6rem}
    .btn{
      display:inline-flex; align-items:center; gap:.5rem; border:1px solid var(--border);
      background:rgba(255,255,255,.02); color:var(--txt); padding:.6rem .9rem; border-radius:12px;
      transition:.25s ease; cursor:pointer; font-weight:600; font-size:.95rem
    }
    .btn:hover{transform:translateY(-1px); box-shadow:0 8px 18px rgba(0,0,0,.25); border-color:rgba(255,255,255,.22)}
    .btn.primary{background:linear-gradient(135deg, var(--brand), var(--brand-2)); border-color:transparent}

    /* Hero */
    .hero{padding:48px 0 28px}
    .hero-wrap{display:grid; grid-template-columns:1.2fr .8fr; gap:28px; align-items:center}
    .kicker{display:inline-flex; gap:.6rem; align-items:center; background:rgba(34,197,94,.08); color:#c7f9de; border:1px solid var(--ring);
      padding:.35rem .65rem; border-radius:999px; font-weight:700; font-size:.8rem; letter-spacing:.4px; text-transform:uppercase}
    .title{font-size:clamp(1.6rem, 3vw + .6rem, 2.4rem); margin:.6rem 0 .6rem; font-weight:800}
    .lead{color:var(--muted); font-size:1.02rem; margin:.2rem 0 1rem}
    .hero-cta{display:flex; gap:.6rem; flex-wrap:wrap}
    .stats{display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-top:18px}
    .stat{border:1px solid var(--border); background:linear-gradient(180deg, rgba(255,255,255,.04), transparent);
      border-radius:14px; padding:14px}
    .stat .n{font-size:1.15rem; font-weight:800}
    .stat .l{color:var(--muted); font-size:.9rem}
    .hero-card{border:1px solid var(--border); background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
      border-radius:18px; padding:16px; box-shadow:var(--shadow)}
    .hero-card .thumb{aspect-ratio:16/9; border-radius:12px; overflow:hidden; position:relative; border:1px solid var(--border)}
    .hero-card iframe{position:absolute; inset:0; width:100%; height:100%; border:0}

    /* Controles */
    .toolbar{display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:16px; margin:6px 0 18px;
      border:1px solid var(--border); border-radius:14px; background:rgba(255,255,255,.03)}
    .field{flex:1; min-width:240px; position:relative}
    .field input{
      width:100%; background:#0a1020; border:1px solid var(--border); color:var(--txt); padding:12px 40px 12px 42px; border-radius:12px;
      outline:none; transition:border .2s ease
    }
    .field input:focus{border-color:var(--ring-2); box-shadow:0 0 0 4px var(--ring-2)}
    .field .ico{position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#8aa0b6}
    .chip{display:inline-flex; align-items:center; gap:.4rem; padding:8px 12px; border-radius:999px; border:1px solid var(--border);
      background:rgba(255,255,255,.04); cursor:pointer; user-select:none; font-weight:600}
    .chip.active{background:linear-gradient(135deg, var(--brand), var(--brand-2)); border-color:transparent}

    /* Secciones */
    section{padding:8px 0 36px}
    .section-head{display:flex; align-items:end; justify-content:space-between; margin:14px 0 14px}
    .section-head h2{margin:0; font-size:1.2rem}
    .section-head .sub{color:var(--muted); font-size:.95rem}

    /* Grid tarjetas */
    .grid{display:grid; grid-template-columns:repeat(12,1fr); gap:18px}
    .col-4{grid-column:span 4}
    .col-6{grid-column:span 6}
    .col-12{grid-column:span 12}
    @media (max-width:1024px){.col-4{grid-column:span 6}}
    @media (max-width:720px){.col-4,.col-6{grid-column:span 12}}

    .card{position:relative; border:1px solid var(--border); background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.02));
      border-radius:16px; overflow:hidden; transition:transform .2s ease, box-shadow .2s ease}
    .card:hover{transform:translateY(-2px); box-shadow:var(--shadow)}
    .card .thumb{position:relative; aspect-ratio:16/9; overflow:hidden; border-bottom:1px solid var(--border)}
    .card iframe{position:absolute; inset:0; width:100%; height:100%; border:0}
    .card .body{padding:12px 14px}
    .card h3{font-size:1rem; margin:0 0 6px}
    .meta{display:flex; gap:10px; align-items:center; color:var(--muted); font-size:.86rem}
    .badge{display:inline-flex; gap:.35rem; align-items:center; padding:.25rem .5rem; border:1px solid var(--border); border-radius:999px; font-size:.8rem}

    /* Manuales (estilo documento) */
    .docs{display:grid; grid-template-columns:repeat(12,1fr); gap:18px}
    .doc-card{grid-column:span 4; border:1px dashed var(--border); border-radius:16px; background:rgba(255,255,255,.03);
      padding:18px; display:flex; align-items:center; gap:14px}
    .doc-icon{width:64px; height:64px; border-radius:14px; display:grid; place-items:center; color:#fff;
      background:linear-gradient(135deg, #ef4444, #f97316)}
    .doc-info{flex:1}
    .doc-info h4{margin:0 0 6px}
    .doc-actions{display:flex; gap:8px; flex-wrap:wrap}

    /* Modal */
    .modal{position:fixed; inset:0; display:none; place-items:center; background:rgba(0,0,0,.55); z-index:60}
    .modal.open{display:grid}
    .modal-dialog{width:min(1200px, 96vw); height:min(90vh, 900px); background:#0c1327; border:1px solid var(--border);
      border-radius:18px; overflow:hidden; box-shadow:var(--shadow); display:flex; flex-direction:column}
    .modal-header{display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-bottom:1px solid var(--border)}
    .modal-header h5{margin:0; font-size:1rem}
    .modal-body{flex:1; position:relative}
    .modal-body iframe{position:absolute; inset:0; width:100%; height:100%; border:0}

    /* Pie */
    footer{border-top:1px solid var(--border); color:var(--muted); padding:22px 0; margin-top:28px}

    /* Modo claro opcional */
    .light body{}
    .toggle{display:inline-flex; align-items:center; gap:.5rem}
  </style>
</head>
<body>
  <header>
    <div class="container nav">
      <div class="brand">
        <div class="brand-badge" aria-hidden="true"><i class="fa-solid fa-graduation-cap"></i></div>
        <h1>SISTECU · UNDC</h1>
      </div>
      <div class="nav-actions">
      <button class="btn" id="toggleTheme" title="Cambiar tema"><i class="fa-solid fa-circle-half-stroke"></i><span>Modo</span></button>
      <a class="btn primary" href="#docs"><i class="fa-solid fa-book"></i><span>Manuales</span></a>
      </div>
    </div>
  </header>

  <main>
    <!-- Hero -->
    <section class="hero">
      <div class="container hero-wrap">
        <div>
          <span class="kicker"><i class="fa-solid fa-shield-check"></i> Capacitación Oficial</span>
          <h2 class="title">Portal de Tutoriales y Manuales para Docentes Tutores</h2>
          <p class="lead">Encuentra videos paso a paso y manuales descargables del Sistema de Tutoría y Consejería Universitaria. Todo centralizado, actualizado y listo para usar en tus actividades mensuales.</p>
          <div class="hero-cta">
            <a class="btn primary" href="#videos"><i class="fa-solid fa-play"></i> Ver videos</a>
            <a class="btn" href="#docs"><i class="fa-solid fa-file-lines"></i> Abrir manuales</a>
          </div>
          <div class="stats" aria-hidden="true">
            <div class="stat"><div class="n">4</div><div class="l">videos clave</div></div>
            <div class="stat"><div class="n">1</div><div class="l">manual disponible</div></div>
            <div class="stat"><div class="n">24/7</div><div class="l">acceso</div></div>
          </div>
        </div>
        <div class="hero-card" aria-label="Vista previa">
          <div class="thumb">
            <iframe src="https://drive.google.com/file/d/1XbgWqjNQ7dMCIzqHt-pytc4CpvsnnB5V/preview" allow="autoplay" loading="lazy" title="Registro de Planes de Tutoría"></iframe>
          </div>
          <div class="meta" style="padding-top:10px">
            <span class="badge"><i class="fa-solid fa-sparkles"></i> Recomendado</span>
            <span class="badge"><i class="fa-regular fa-clock"></i> 8 min</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Barra de herramientas -->
    <div class="container toolbar">
      <div class="field">
        <i class="fa-solid fa-magnifying-glass ico"></i>
        <input id="search" type="search" placeholder="Buscar: plan, informe, aula, curso…" aria-label="Buscar contenidos" />
      </div>
      <button class="chip active" data-filter="all"><i class="fa-solid fa-grid-2"></i> Todo</button>
      <button class="chip" data-filter="aula"><i class="fa-solid fa-chalkboard-user"></i> Tutor de Aula</button>
      <button class="chip" data-filter="curso"><i class="fa-solid fa-people-line"></i> Tutor de Curso</button>
      <button class="chip" data-filter="planes"><i class="fa-solid fa-clipboard-list"></i> Planes</button>
      <button class="chip" data-filter="informes"><i class="fa-solid fa-file-pen"></i> Informes</button>
    </div>

    <!-- Videos -->
    <section id="videos" class="container">
      <div class="section-head">
        <h2><i class="fa-solid fa-video"></i> Videos Tutoriales</h2>
        <span class="sub">Guías prácticas en menos de 10 minutos</span>
      </div>
      <div class="grid" id="videoGrid">
        <!-- Video 1: Planes -->
        <article class="card col-6" data-tags="planes aula curso">
          <div class="thumb">
            <iframe src="https://drive.google.com/file/d/1XbgWqjNQ7dMCIzqHt-pytc4CpvsnnB5V/preview" allow="autoplay" loading="lazy" title="Registro de Planes de Tutoría"></iframe>
          </div>
          <div class="body">
            <h3><i class="fa-solid fa-circle-play"></i> Registro de Planes de Tutoría</h3>
            <div class="meta"><span><i class="fa-regular fa-clock"></i> 8 min</span> · <span><i class="fa-solid fa-sitemap"></i> Planes</span></div>
          </div>
        </article>

        <!-- Video 2: Informes Aula -->
        <article class="card col-6" data-tags="informes aula">
          <div class="thumb">
            <iframe src="https://drive.google.com/file/d/1WQ1s03MFhZ5cvAyiVwwY2HWn0lun3JG4/preview" allow="autoplay" loading="lazy" title="Informes Mensuales - Tutor de Aula"></iframe>
          </div>
          <div class="body">
            <h3><i class="fa-solid fa-circle-play"></i> Informes Mensuales – Tutor de Aula</h3>
            <div class="meta"><span><i class="fa-regular fa-clock"></i> 9 min</span> · <span><i class="fa-solid fa-file-pen"></i> Informes</span></div>
          </div>
        </article>

        <!-- Video 3: Informes Curso -->
        <article class="card col-6" data-tags="informes curso">
          <div class="thumb">
            <iframe src="https://drive.google.com/file/d/1Pl4xh31cH1wHyQw6HepzCcd7WKzYmVvU/preview" allow="autoplay" loading="lazy" title="Informes Mensuales - Tutor de Curso"></iframe>
          </div>
          <div class="body">
            <h3><i class="fa-solid fa-circle-play"></i> Informes Mensuales – Tutor de Curso</h3>
            <div class="meta"><span><i class="fa-regular fa-clock"></i> 7 min</span> · <span><i class="fa-solid fa-file-pen"></i> Informes</span></div>
          </div>
        </article>

        <!-- Video 4: Sesiones Aula -->
        <article class="card col-6" data-tags="aula sesiones">
          <div class="thumb">
            <iframe src="https://drive.google.com/file/d/15ZAhadmL45eZ18uKSeJsk-gn8cpddqMQ/preview" allow="autoplay" loading="lazy" title="Registro de Sesiones - Tutor de Aula"></iframe>
          </div>
          <div class="body">
            <h3><i class="fa-solid fa-circle-play"></i> Registro de Sesiones – Tutor de Aula</h3>
            <div class="meta"><span><i class="fa-regular fa-clock"></i> 6 min</span> · <span><i class="fa-solid fa-chalkboard-user"></i> Aula</span></div>
          </div>
        </article>
      </div>
    </section>

    <!-- Manuales -->
    <section id="docs" class="container">
      <div class="section-head">
        <h2><i class="fa-solid fa-book"></i> Manuales de Usuario</h2>
        <span class="sub">Formatos PDF con pasos detallados</span>
      </div>
      <div class="docs">
        <!-- Manual Tutor de Aula -->
        <div class="doc-card">
          <div class="doc-icon" aria-hidden="true"><i class="fa-solid fa-file-pdf"></i></div>
          <div class="doc-info">
            <h4>Manual del Tutor de Aula</h4>
            <p style="margin:.2rem 0; color:var(--muted)">Versión vigente · Descarga y consulta en línea</p>
            <div class="doc-actions">
              <button class="btn" data-open-modal data-title="Manual del Tutor de Aula" data-src="https://drive.google.com/file/d/1r1a5Q-z4kZydsY4nAQj5ZPv7gcPvpfIX/preview"><i class="fa-solid fa-eye"></i> Ver en línea</button>
              <a class="btn primary" href="https://drive.google.com/uc?export=download&id=1r1a5Q-z4kZydsY4nAQj5ZPv7gcPvpfIX"><i class="fa-solid fa-download"></i> Descargar</a>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Modal visor -->
  <div class="modal" id="viewer" role="dialog" aria-modal="true" aria-labelledby="viewerTitle">
    <div class="modal-dialog">
      <div class="modal-header">
        <h5 id="viewerTitle">Documento</h5>
        <div style="display:flex; gap:.5rem">
          <button class="btn" id="openNewTab" title="Abrir en pestaña"><i class="fa-solid fa-arrow-up-right-from-square"></i></button>
          <button class="btn" id="closeModal" title="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </div>
      <div class="modal-body">
        <iframe id="viewerFrame" src="about:blank" title="Visor de documento"></iframe>
      </div>
    </div>
  </div>

  <footer>
    <div class="container" style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap">
      <div>© <span id="y"></span> SISTECU – UNDC · Coordinación General de Tutoría</div>
      <div style="display:flex; gap:10px; color:var(--muted)">
        <span><i class="fa-solid fa-circle-check"></i> Contenidos oficiales</span>
        <span><i class="fa-solid fa-shield"></i> Acceso seguro</span>
      </div>
    </div>
  </footer>

  <script>
    // Año dinámico
    document.getElementById('y').textContent = new Date().getFullYear();

    // Filtro por chips + búsqueda
    const chips = Array.from(document.querySelectorAll('.chip'));
    const search = document.getElementById('search');
    const cards = Array.from(document.querySelectorAll('#videoGrid .card'));

    let active = 'all';

    function applyFilters(){
      const q = search.value.toLowerCase().trim();
      cards.forEach(card=>{
        const tag = (card.dataset.tags||'').toLowerCase();
        const text = card.innerText.toLowerCase();
        const matchChip = active==='all' || tag.includes(active);
        const matchText = !q || text.includes(q);
        card.style.display = (matchChip && matchText) ? '' : 'none';
      });
    }

    chips.forEach(ch=>{
      ch.addEventListener('click',()=>{
        chips.forEach(c=>c.classList.remove('active'));
        ch.classList.add('active');
        active = ch.dataset.filter;
        applyFilters();
      })
    });
    search.addEventListener('input', applyFilters);

    // Modal visor
    const modal = document.getElementById('viewer');
    const frame = document.getElementById('viewerFrame');
    const title = document.getElementById('viewerTitle');
    const btnClose = document.getElementById('closeModal');
    const btnNewTab = document.getElementById('openNewTab');
    let lastSrc = '';

    document.querySelectorAll('[data-open-modal]').forEach(btn=>{
      btn.addEventListener('click',()=>{
        title.textContent = btn.dataset.title || 'Documento';
        lastSrc = btn.dataset.src;
        frame.src = lastSrc;
        modal.classList.add('open');
      })
    })

    btnClose.addEventListener('click', ()=>{
      modal.classList.remove('open');
      frame.src = 'about:blank';
    })
    btnNewTab.addEventListener('click', ()=>{
      if(lastSrc) window.open(lastSrc, '_blank');
    })

    modal.addEventListener('click', (e)=>{
      if(e.target === modal){ btnClose.click(); }
    })
    document.addEventListener('keydown',(e)=>{
      if(e.key==='Escape' && modal.classList.contains('open')) btnClose.click();
    })

    // Modo claro/oscuro (persistente)
    const toggle = document.getElementById('toggleTheme');
    const prefers = window.matchMedia('(prefers-color-scheme: light)').matches;
    let mode = localStorage.getItem('sistecu-theme') || (prefers ? 'light' : 'dark');

    function setTheme(m){
      if(m==='light'){
        document.body.style.color = 'var(--ink)';
        document.body.style.background = 'var(--surface)';
        document.querySelectorAll('header, .toolbar, .card, .hero-card, .doc-card, .modal-dialog, footer')
          .forEach(el=>{el.style.background=''; el.style.borderColor='';});
        document.documentElement.style.setProperty('--border', 'rgba(2,6,23,.12)');
      }else{
        document.location.reload(); // recarga para volver al diseño oscuro original (más consistente con gradientes)
      }
      localStorage.setItem('sistecu-theme', m);
    }
    toggle.addEventListener('click',()=>{
      mode = mode==='dark' ? 'light' : 'dark';
      setTheme(mode);
    })
  </script>
</body>
</html>
