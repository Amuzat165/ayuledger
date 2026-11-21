<style>
/* ============================================================
   AYUGLOBAL landing page — rebuilt from the Sage Accounting
   reference layout, recoloured to the AYUGLOBAL blue palette.
   Palette:
     --blue      #1E5BC6  (primary, replaces Sage green)
     --blue-dark #174A9F  (hover)
     --navy      #0F2A56  (hero / header / footer)
     --navy-deep #0B2145
     --bg-light  #F4F7FB  (section tint)
     --ink       #0F2A56  (headings)
     --body      #4A5A75  (body text)
     --line      #E3EAF4  (borders)
   ============================================================ */
:root{
  --blue:#1E5BC6;
  --blue-dark:#174A9F;
  --blue-tint:#E8F0FC;
  --blue-tint-2:#DCE8FA;
  --navy:#0F2A56;
  --navy-deep:#0B2145;
  --bg-light:#F4F7FB;
  --ink:#0F2A56;
  --body:#4A5A75;
  --line:#E3EAF4;
  --white:#ffffff;
  --green:#1FA463;          /* success / WhatsApp accent */
  --radius:18px;
  --shadow:0 12px 34px rgba(15,42,86,.10);
  --shadow-lg:0 24px 60px rgba(11,33,69,.28);
  --header-h:78px;
  --header-h-shrink:62px;
  --container:1200px;
  --font:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
}

/* ---------- Reset & base ---------- */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:var(--font);
  color:var(--body);
  background:var(--white);
  font-size:16px;
  line-height:1.65;
  -webkit-font-smoothing:antialiased;
  overflow-x:hidden;
}
img,svg{display:block;max-width:100%}
a{color:var(--blue);text-decoration:none}
ul{list-style:none}
button{font-family:inherit;cursor:pointer;border:none;background:none}
input,textarea,select{font-family:inherit;font-size:1rem}
::selection{background:var(--blue);color:#fff}

/* Focus states (accessibility) */
a:focus-visible,button:focus-visible,input:focus-visible,textarea:focus-visible,[tabindex]:focus-visible{
  outline:3px solid rgba(30,91,198,.55);outline-offset:2px;border-radius:6px;
}

.skip-link{
  position:absolute;left:-9999px;top:0;background:var(--blue);color:#fff;
  padding:10px 18px;z-index:2000;border-radius:0 0 10px 0;font-weight:600;
}
.skip-link:focus{left:0}

.container{max-width:var(--container);margin:0 auto;padding:0 24px}
section{scroll-margin-top:calc(var(--header-h) + 16px)}

/* ---------- Typography ---------- */
h1,h2,h3,h4{color:var(--ink);font-weight:800;letter-spacing:-.02em;line-height:1.12}
.eyebrow{
  display:inline-flex;align-items:center;gap:10px;
  font-size:.78rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;
  color:var(--blue);margin-bottom:18px;
}
.eyebrow::before{content:"";width:26px;height:2px;background:var(--blue);border-radius:2px}
.eyebrow.on-dark{color:#8FB6F5}
.eyebrow.on-dark::before{background:#8FB6F5}
.h2{font-size:clamp(1.9rem,3.4vw,2.75rem);margin-bottom:16px}
.lead{font-size:clamp(1rem,1.4vw,1.15rem);max-width:640px}
.section{padding:96px 0}
.section-head{max-width:720px;margin:0 auto 56px;text-align:center}
.section-head .lead{margin:0 auto}
.section-head .eyebrow::after{content:"";width:26px;height:2px;background:var(--blue);border-radius:2px}

/* ---------- Buttons ---------- */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:10px;
  padding:14px 28px;border-radius:999px;font-weight:600;font-size:.98rem;
  transition:transform .2s ease,box-shadow .2s ease,background .2s ease,color .2s ease,border-color .2s ease;
  white-space:nowrap;
}
.btn:active{transform:translateY(1px) scale(.99)}
.btn-primary{background:var(--blue);color:#fff;box-shadow:0 10px 24px rgba(30,91,198,.35)}
.btn-primary:hover{background:var(--blue-dark);transform:translateY(-2px);box-shadow:0 14px 30px rgba(30,91,198,.42)}
.btn-outline-light{border:2px solid rgba(255,255,255,.55);color:#fff;background:transparent}
.btn-outline-light:hover{border-color:#fff;background:rgba(255,255,255,.08);transform:translateY(-2px)}
.btn-outline-dark{border:2px solid var(--line);color:var(--ink);background:#fff}
.btn-outline-dark:hover{border-color:var(--blue);color:var(--blue);transform:translateY(-2px)}
.btn-ghost{color:#fff;padding:14px 10px;position:relative}
.btn-ghost::after{content:"";position:absolute;left:10px;right:10px;bottom:8px;height:2px;background:currentColor;opacity:.6;border-radius:2px;transition:opacity .2s}
.btn-ghost:hover::after{opacity:1}
.btn-ghost.on-light{color:var(--blue)}
.btn-sm{padding:10px 20px;font-size:.9rem}

/* ============================================================
   1. ANNOUNCEMENT BAR
   ============================================================ */
.announce{
  background:var(--blue-tint);
  color:var(--ink);
  font-size:.9rem;font-weight:500;
}
.announce .container{
  display:flex;align-items:center;justify-content:center;gap:18px;
  min-height:46px;padding-top:8px;padding-bottom:8px;flex-wrap:wrap;text-align:center;
}
.announce strong{font-weight:700}
.announce .btn{background:var(--navy);color:#fff;padding:8px 20px;font-size:.85rem}
.announce .btn:hover{background:var(--navy-deep);transform:translateY(-1px)}

/* ============================================================
   2. HEADER (sticky, shrinks on scroll)
   ============================================================ */
.site-header{
  position:sticky;top:0;z-index:1000;
  background:var(--navy);
  transition:box-shadow .3s ease,background .3s ease;
}
.site-header .container{
  display:flex;align-items:center;justify-content:space-between;gap:24px;
  height:var(--header-h);
  transition:height .3s ease;
}
.site-header.scrolled{box-shadow:0 8px 30px rgba(11,33,69,.45)}
.site-header.scrolled .container{height:var(--header-h-shrink)}

/* Logo — EDIT: replace with your real logo image file */
.logo{display:inline-flex;align-items:center;gap:11px;color:#fff;font-weight:800;letter-spacing:.06em;font-size:1.15rem}
.logo svg{width:34px;height:34px;flex:none}

.main-nav{display:flex;align-items:center;gap:6px}
.main-nav a.nav-link{
  color:rgba(255,255,255,.85);font-weight:500;font-size:.95rem;
  padding:9px 14px;border-radius:999px;transition:color .2s,background .2s;
}
.main-nav a.nav-link:hover{color:#fff;background:rgba(255,255,255,.10)}
.header-actions{display:flex;align-items:center;gap:14px}
.header-actions .btn-signin{
  border:2px solid rgba(255,255,255,.6);color:#fff;border-radius:999px;
  padding:9px 22px;font-weight:600;font-size:.92rem;transition:all .2s;
}
.header-actions .btn-signin:hover{background:#fff;color:var(--navy);border-color:#fff}
.header-actions .btn-get{
  background:var(--blue);color:#fff;border-radius:999px;
  padding:11px 24px;font-weight:600;font-size:.92rem;box-shadow:0 8px 20px rgba(30,91,198,.4);
  transition:all .2s;
}
.header-actions .btn-get:hover{background:var(--blue-dark);transform:translateY(-2px)}

/* Hamburger */
.hamburger{
  display:none;flex-direction:column;gap:5px;padding:10px;border-radius:10px;
}
.hamburger span{width:24px;height:2.5px;background:#fff;border-radius:2px;transition:transform .3s,opacity .3s}
.hamburger.open span:nth-child(1){transform:translateY(7.5px) rotate(45deg)}
.hamburger.open span:nth-child(2){opacity:0}
.hamburger.open span:nth-child(3){transform:translateY(-7.5px) rotate(-45deg)}

/* Mobile menu panel */
.mobile-menu{
  display:none;background:var(--navy-deep);border-top:1px solid rgba(255,255,255,.08);
  max-height:0;overflow:hidden;transition:max-height .35s ease;
}
.mobile-menu.open{max-height:420px}
.mobile-menu ul{padding:14px 24px 24px;display:flex;flex-direction:column;gap:4px}
.mobile-menu a{display:block;color:#fff;font-weight:600;padding:13px 10px;border-radius:10px;font-size:1.05rem}
.mobile-menu a:hover{background:rgba(255,255,255,.08)}
.mobile-menu .mm-actions{display:flex;gap:12px;margin-top:12px}
.mobile-menu .mm-actions a{flex:1;text-align:center}
.mobile-menu .mm-signin{border:2px solid rgba(255,255,255,.5)}
.mobile-menu .mm-get{background:var(--blue)}

/* ============================================================
   3. HERO SLIDER
   ============================================================ */
.hero{
  position:relative;background:var(--navy);color:#fff;overflow:hidden;
}
/* radial blue glow, like the reference hero */
.hero::before{
  content:"";position:absolute;top:-220px;right:-160px;width:760px;height:760px;
  background:radial-gradient(circle,rgba(30,91,198,.55) 0%,rgba(30,91,198,.18) 42%,transparent 68%);
  pointer-events:none;
}
.hero::after{
  content:"";position:absolute;bottom:-260px;left:-200px;width:620px;height:620px;
  background:radial-gradient(circle,rgba(30,91,198,.35) 0%,transparent 65%);
  pointer-events:none;
}
.hero-inner{position:relative;z-index:2}

.slider{position:relative}
.slide{
  display:grid;grid-template-columns:1.05fr .95fr;gap:56px;align-items:center;
  min-height:600px;padding:72px 0 96px;
  position:absolute;inset:0;opacity:0;visibility:hidden;
  transform:translateX(36px);
  transition:opacity .65s ease,transform .65s ease,visibility 0s linear .65s;
}
.slide.active{
  position:relative;opacity:1;visibility:visible;transform:translateX(0);
  transition:opacity .65s ease,transform .65s ease;
}
.slide-copy{max-width:600px}
.slide-copy .eyebrow{color:#8FB6F5}
.slide-copy .eyebrow::before{background:#8FB6F5}
.slide h1{
  color:#fff;font-size:clamp(2.5rem,5.4vw,4.3rem);
  font-weight:800;letter-spacing:-.03em;line-height:1.04;margin-bottom:22px;
}
.slide p.sub{
  color:rgba(255,255,255,.82);font-size:clamp(1.02rem,1.5vw,1.2rem);
  line-height:1.65;max-width:520px;margin-bottom:34px;
}
.slide-cta{display:flex;align-items:center;gap:14px;flex-wrap:wrap}

/* decorative circles (seen in reference screenshots) */
.hero-deco{position:absolute;left:40px;bottom:60px;z-index:1;opacity:.5;pointer-events:none}
.hero-deco span{position:absolute;border-radius:50%;background:rgba(255,255,255,.07)}
.hero-deco span:nth-child(1){width:110px;height:110px;left:0;bottom:70px}
.hero-deco span:nth-child(2){width:110px;height:110px;left:0;bottom:-40px}

/* ----- Product mockups (pure CSS/SVG, blue palette) ----- */
.mock-wrap{position:relative}
.mock-browser{
  background:#fff;border-radius:16px;overflow:hidden;
  box-shadow:var(--shadow-lg);
  border:1px solid rgba(255,255,255,.35);
  transform:rotate(1.2deg);
  transition:transform .5s ease;
}
.slide.active .mock-browser{transform:rotate(0deg)}
.mock-chrome{
  display:flex;align-items:center;gap:8px;
  background:#EDF2F9;padding:10px 14px;border-bottom:1px solid var(--line);
}
.mock-chrome i{width:10px;height:10px;border-radius:50%;display:block}
.mock-chrome i:nth-child(1){background:#F26D6D}
.mock-chrome i:nth-child(2){background:#F5C04E}
.mock-chrome i:nth-child(3){background:#4CC38A}
.mock-url{
  margin-left:8px;flex:1;background:#fff;border:1px solid var(--line);border-radius:999px;
  font-size:.68rem;color:#7A8AA5;padding:4px 12px;display:flex;align-items:center;gap:6px;
}
.mock-body{display:flex;background:#fff}
.mock-side{
  width:56px;background:var(--navy);padding:14px 0;display:flex;flex-direction:column;align-items:center;gap:12px;
}
.mock-side .dot-logo{width:26px;height:26px;border-radius:8px;background:var(--blue);margin-bottom:6px}
.mock-side .nav-pill{width:30px;height:8px;border-radius:99px;background:rgba(255,255,255,.22)}
.mock-side .nav-pill.on{background:var(--blue)}
.mock-main{flex:1;padding:18px 20px 20px;min-width:0}
.mock-title-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;gap:10px}
.mock-title{font-weight:800;color:var(--ink);font-size:1.02rem;letter-spacing:-.01em}
.chip{font-size:.66rem;font-weight:700;padding:4px 11px;border-radius:999px;white-space:nowrap}
.chip.paid{background:#E3F6EC;color:#177A47}
.chip.draft{background:var(--blue-tint);color:var(--blue)}
.chip.warn{background:#FFF3E0;color:#B26A00}
.mock-to{
  background:var(--bg-light);border:1px solid var(--line);border-radius:10px;
  padding:9px 12px;font-size:.72rem;color:var(--body);margin-bottom:12px;
}
.mock-to b{color:var(--ink)}
.mock-table{width:100%;border-collapse:collapse;font-size:.7rem}
.mock-table th{
  text-align:left;color:#8A99B5;font-weight:700;font-size:.62rem;text-transform:uppercase;
  letter-spacing:.06em;padding:6px 6px;border-bottom:2px solid var(--line);
}
.mock-table td{padding:8px 6px;border-bottom:1px solid #EEF2F8;color:var(--ink);font-weight:500}
.mock-table td:last-child,.mock-table th:last-child{text-align:right}
.mock-total{display:flex;justify-content:flex-end;gap:8px;font-size:.78rem;font-weight:800;color:var(--ink);padding-top:10px}
.mock-total span:last-child{color:var(--blue)}

/* KPI cards + charts (slide 2 & reports tab) */
.kpi-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:14px}
.kpi{background:var(--bg-light);border:1px solid var(--line);border-radius:10px;padding:10px 12px}
.kpi .k-label{font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#8A99B5}
.kpi .k-value{font-size:1rem;font-weight:800;color:var(--ink);letter-spacing:-.02em}
.kpi .k-delta{font-size:.62rem;font-weight:700;color:var(--green)}
.chart-card{background:var(--bg-light);border:1px solid var(--line);border-radius:10px;padding:12px}
.chart-card .c-title{font-size:.68rem;font-weight:700;color:var(--ink);margin-bottom:8px}

/* Kanban (slide 3) */
.kanban{display:grid;grid-template-columns:repeat(4,1fr);gap:9px}
.kcol{background:var(--bg-light);border:1px solid var(--line);border-radius:10px;padding:8px}
.kcol h5{font-size:.6rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--blue);margin-bottom:8px}
.kcard{background:#fff;border:1px solid var(--line);border-radius:8px;padding:8px;margin-bottom:8px;box-shadow:0 2px 6px rgba(15,42,86,.05)}
.kcard .bar{height:6px;border-radius:99px;background:var(--blue-tint-2);margin-bottom:6px}
.kcard .bar.w60{width:60%}
.kcard .bar.w80{width:80%}
.kcard .kmeta{display:flex;align-items:center;gap:5px}
.kcard .ava{width:16px;height:16px;border-radius:50%;background:var(--blue);color:#fff;font-size:.5rem;font-weight:800;display:flex;align-items:center;justify-content:center}
.kcard .kamt{margin-left:auto;font-size:.6rem;font-weight:800;color:var(--ink)}

/* floating status chips over mockups */
.float-chip{
  position:absolute;background:#fff;border-radius:14px;box-shadow:var(--shadow-lg);
  padding:12px 16px;border:1px solid var(--line);z-index:3;
  animation:floaty 5s ease-in-out infinite;
}
@keyframes floaty{0%,100%{transform:translateY(0)}50%{transform:translateY(-9px)}}
.fc-steps{top:-24px;right:-14px}
.fc-steps .steps{display:flex;gap:12px;margin-bottom:8px}
.fc-steps .step{display:flex;flex-direction:column;align-items:center;gap:4px;font-size:.58rem;font-weight:700;color:#8A99B5}
.fc-steps .step i{width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.55rem;background:var(--blue-tint);color:var(--blue)}
.fc-steps .step.done i{background:var(--green);color:#fff}
.fc-steps .amounts{display:flex;gap:18px;font-size:.68rem;color:#8A99B5}
.fc-steps .amounts b{display:block;color:var(--ink);font-size:.8rem}
.fc-note{bottom:-20px;left:-18px;display:flex;align-items:center;gap:10px;font-size:.74rem;font-weight:600;color:var(--ink)}
.fc-note .ic{width:32px;height:32px;border-radius:10px;background:var(--blue-tint);color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:.9rem}
.fc-note small{display:block;color:#8A99B5;font-weight:500}

/* slider controls */
.slider-controls{
  position:absolute;left:0;right:0;bottom:26px;z-index:5;
  display:flex;align-items:center;justify-content:center;gap:22px;
}
.slider-dots{display:flex;gap:10px}
.slider-dots button{
  width:11px;height:11px;border-radius:50%;background:rgba(255,255,255,.35);
  transition:all .3s;padding:0;
}
.slider-dots button.active{background:#fff;width:30px;border-radius:99px}
.slider-dots button:hover{background:rgba(255,255,255,.7)}
.slider-arrow{
  width:42px;height:42px;border-radius:50%;border:1.5px solid rgba(255,255,255,.4);
  color:#fff;display:flex;align-items:center;justify-content:center;font-size:.85rem;
  transition:all .25s;
}
.slider-arrow:hover{background:var(--blue);border-color:var(--blue)}
.slider-arrow.prev{position:absolute;left:18px;top:50%;transform:translateY(-50%)}
.slider-arrow.next{position:absolute;right:18px;top:50%;transform:translateY(-50%)}

/* ============================================================
   4. TRUST STRIP  — EDIT: replace placeholder logos with real client logos
   ============================================================ */
.trust{padding:44px 0;border-bottom:1px solid var(--line);background:#fff}
.trust p{
  text-align:center;font-size:.8rem;font-weight:700;letter-spacing:.14em;
  text-transform:uppercase;color:#8A99B5;margin-bottom:26px;
}
.trust-logos{
  display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:20px 56px;
}
.trust-logos span{
  font-weight:800;font-size:1.15rem;color:#B7C3D6;letter-spacing:.02em;
  display:inline-flex;align-items:center;gap:8px;transition:color .25s;
}
.trust-logos span:hover{color:var(--blue)}
.trust-logos i{font-size:1.05rem}

/* ============================================================
   5. FEATURE CARDS
   ============================================================ */
.features{background:#fff}
.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:26px}
.f-card{
  background:#fff;border:1px solid var(--line);border-radius:var(--radius);
  padding:36px 30px;box-shadow:0 4px 14px rgba(15,42,86,.04);
  transition:transform .3s ease,box-shadow .3s ease,border-color .3s ease;
}
.f-card:hover{transform:translateY(-8px);box-shadow:var(--shadow);border-color:var(--blue-tint-2)}
.f-icon{
  width:54px;height:54px;border-radius:15px;background:var(--blue-tint);
  color:var(--blue);display:flex;align-items:center;justify-content:center;
  font-size:1.3rem;margin-bottom:22px;transition:all .3s;
}
.f-card:hover .f-icon{background:var(--blue);color:#fff;transform:scale(1.06)}
.f-card h3{font-size:1.28rem;margin-bottom:10px}
.f-card p{font-size:.97rem}

/* ============================================================
   6. PRODUCT TABS (mirrors the reference "Easy to get started" section)
   ============================================================ */
.how{background:var(--bg-light)}
.tabs-wrap{display:grid;grid-template-columns:340px 1fr;gap:48px;align-items:start}
.tab-list{display:flex;flex-direction:column;gap:6px;position:relative}
.tab-list::before{
  content:"";position:absolute;left:0;top:8px;bottom:8px;width:4px;
  background:var(--line);border-radius:99px;
}
.tab-btn{
  position:relative;text-align:left;padding:16px 18px 16px 24px;border-radius:0 12px 12px 0;
  font-weight:600;font-size:1.02rem;color:var(--body);transition:all .25s;
}
.tab-btn::before{
  content:"";position:absolute;left:0;top:10px;bottom:10px;width:4px;border-radius:99px;
  background:transparent;transition:background .25s;
}
.tab-btn:hover{color:var(--ink);background:rgba(30,91,198,.06)}
.tab-btn.active{color:var(--blue);background:#fff;box-shadow:0 6px 18px rgba(15,42,86,.08)}
.tab-btn.active::before{background:var(--blue)}
.tab-panels{position:relative;min-height:460px}
.tab-panel{
  position:absolute;inset:0;opacity:0;visibility:hidden;transform:translateY(18px);
  transition:opacity .45s ease,transform .45s ease,visibility 0s linear .45s;
  display:grid;grid-template-columns:1fr 1.1fr;gap:40px;align-items:center;
}
.tab-panel.active{position:relative;opacity:1;visibility:visible;transform:none;transition:opacity .45s ease,transform .45s ease}
.tab-panel h3{font-size:clamp(1.5rem,2.4vw,2rem);margin-bottom:16px}
.tab-panel p{margin-bottom:14px;font-size:1rem}
.tab-panel .check-list{margin-top:20px}
.check-list li{display:flex;gap:12px;align-items:flex-start;padding:7px 0;font-weight:500;color:var(--ink);font-size:.96rem}
.check-list i{color:var(--blue);margin-top:4px;font-size:.85rem}

/* security mockup */
.sec-card{background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);padding:22px}
.sec-head{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.sec-head .shield{
  width:44px;height:44px;border-radius:12px;background:var(--blue-tint);color:var(--blue);
  display:flex;align-items:center;justify-content:center;font-size:1.2rem;
}
.sec-head b{color:var(--ink);font-size:1rem}
.sec-head small{display:block;color:#8A99B5}
.role-row{
  display:flex;align-items:center;justify-content:space-between;gap:10px;
  padding:11px 4px;border-bottom:1px solid #EEF2F8;font-size:.85rem;color:var(--ink);font-weight:600;
}
.role-row:last-child{border-bottom:none}
.role-row .perm{font-size:.66rem;font-weight:700;padding:4px 10px;border-radius:99px;background:var(--blue-tint);color:var(--blue)}
.role-row .perm.grey{background:#EEF2F8;color:#7A8AA5}

/* ============================================================
   7. STATS BAND — EDIT: replace placeholder numbers
   ============================================================ */
.stats{background:var(--navy);color:#fff;padding:72px 0;position:relative;overflow:hidden}
.stats::before{
  content:"";position:absolute;top:-180px;left:50%;transform:translateX(-50%);
  width:700px;height:400px;background:radial-gradient(ellipse,rgba(30,91,198,.5),transparent 65%);
}
.stats-grid{position:relative;display:grid;grid-template-columns:repeat(4,1fr);gap:32px;text-align:center}
.stat .num{font-size:clamp(2rem,3.4vw,2.9rem);font-weight:800;letter-spacing:-.03em;color:#fff}
.stat .num em{font-style:normal;color:#8FB6F5}
.stat .lbl{font-size:.9rem;color:rgba(255,255,255,.7);margin-top:6px}

/* ============================================================
   8. PRICING
   ============================================================ */
.pricing{background:var(--bg-light)}
.bill-toggle{
  display:flex;align-items:center;justify-content:center;gap:14px;margin:-18px 0 44px;
  font-weight:600;color:var(--body);
}
.bill-toggle .switch{
  width:56px;height:30px;border-radius:99px;background:var(--navy);position:relative;transition:background .3s;flex:none;
}
.bill-toggle .switch::after{
  content:"";position:absolute;top:4px;left:4px;width:22px;height:22px;border-radius:50%;
  background:var(--blue);transition:transform .3s ease;
}
.bill-toggle.annual .switch::after{transform:translateX(26px)}
.bill-toggle .opt{cursor:pointer;transition:color .2s}
.bill-toggle .opt.active{color:var(--ink);font-weight:700}
.bill-toggle .save-tag{
  font-size:.68rem;font-weight:800;background:#E3F6EC;color:#177A47;
  padding:4px 10px;border-radius:99px;
}
.price-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:26px;align-items:stretch}
.p-card{
  background:#fff;border:1px solid var(--line);border-radius:var(--radius);
  padding:38px 32px;display:flex;flex-direction:column;position:relative;
  transition:transform .3s ease,box-shadow .3s ease;
}
.p-card:hover{transform:translateY(-8px);box-shadow:var(--shadow)}
.p-card.featured{
  border:2px solid var(--blue);box-shadow:0 20px 50px rgba(30,91,198,.18);
}
.p-badge{
  position:absolute;top:-15px;left:50%;transform:translateX(-50%);
  background:var(--navy);color:#fff;font-size:.7rem;font-weight:800;
  letter-spacing:.1em;text-transform:uppercase;padding:7px 18px;border-radius:99px;white-space:nowrap;
}
.p-card h3{font-size:1.35rem;margin-bottom:8px}
.p-desc{font-size:.92rem;min-height:66px;margin-bottom:22px}
.p-price{font-size:clamp(2.2rem,3vw,2.7rem);font-weight:800;color:var(--ink);letter-spacing:-.03em;line-height:1}
.p-per{font-size:1.15rem;font-weight:700;color:#8A99B5;margin:8px 0 24px}
.p-card .check-list{border-top:1px solid var(--line);padding-top:20px;margin-bottom:30px;flex:1}
.p-card .btn{width:100%}

/* ============================================================
   9. TESTIMONIALS — EDIT: replace placeholder quotes/people
   ============================================================ */
.testimonials{background:#fff}
.t-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:26px}
.t-card{
  background:var(--bg-light);border:1px solid var(--line);border-radius:var(--radius);
  padding:32px 28px;display:flex;flex-direction:column;gap:18px;
  transition:transform .3s,box-shadow .3s;
}
.t-card:hover{transform:translateY(-6px);box-shadow:var(--shadow)}
.t-stars{color:#F5A623;font-size:.85rem;letter-spacing:3px}
.t-card blockquote{color:var(--ink);font-size:.99rem;line-height:1.6;font-weight:500;flex:1}
.t-person{display:flex;align-items:center;gap:13px}
.t-ava{
  width:46px;height:46px;border-radius:50%;flex:none;
  background:var(--blue);color:#fff;font-weight:800;font-size:.9rem;
  display:flex;align-items:center;justify-content:center;
}
.t-person b{display:block;color:var(--ink);font-size:.93rem}
.t-person small{color:#8A99B5;font-size:.8rem}

/* ============================================================
   10. FAQ accordion
   ============================================================ */
.faq{background:var(--bg-light)}
.faq-list{max-width:780px;margin:0 auto;display:flex;flex-direction:column;gap:14px}
.faq-item{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;transition:box-shadow .3s,border-color .3s}
.faq-item.open{border-color:var(--blue-tint-2);box-shadow:var(--shadow)}
.faq-q{
  width:100%;display:flex;align-items:center;justify-content:space-between;gap:18px;
  padding:20px 24px;text-align:left;font-weight:700;color:var(--ink);font-size:1.02rem;
}
.faq-q i{
  color:var(--blue);transition:transform .35s ease;flex:none;
  width:30px;height:30px;border-radius:50%;background:var(--blue-tint);
  display:flex;align-items:center;justify-content:center;font-size:.75rem;
}
.faq-item.open .faq-q i{transform:rotate(180deg);background:var(--blue);color:#fff}
.faq-a{max-height:0;overflow:hidden;transition:max-height .4s ease}
.faq-a p{padding:0 24px 22px;font-size:.96rem}

/* ============================================================
   11. CTA BAND
   ============================================================ */
.cta-band{padding:40px 0 96px;background:var(--bg-light)}
.cta-card{
  background:var(--navy);border-radius:26px;padding:72px 48px;text-align:center;
  position:relative;overflow:hidden;color:#fff;
  box-shadow:var(--shadow-lg);
}
.cta-card::before{
  content:"";position:absolute;top:-160px;right:-120px;width:440px;height:440px;
  background:radial-gradient(circle,rgba(30,91,198,.6),transparent 65%);
}
.cta-card::after{
  content:"";position:absolute;bottom:-180px;left:-100px;width:380px;height:380px;
  background:radial-gradient(circle,rgba(30,91,198,.4),transparent 65%);
}
.cta-card>*{position:relative;z-index:2}
.cta-card h2{color:#fff;font-size:clamp(1.8rem,3.4vw,2.6rem);margin-bottom:14px}
.cta-card p{color:rgba(255,255,255,.8);max-width:560px;margin:0 auto 32px;font-size:1.05rem}

/* ============================================================
   12. CONTACT
   ============================================================ */
.contact{background:#fff}
.contact-grid{display:grid;grid-template-columns:1fr 1.15fr;gap:56px;align-items:start}
.contact-info .h2{margin-bottom:14px}
.contact-info>p{margin-bottom:28px;max-width:420px}
.contact-lines{display:flex;flex-direction:column;gap:16px}
.contact-line{display:flex;align-items:center;gap:14px;font-weight:600;color:var(--ink)}
.contact-line .ic{
  width:46px;height:46px;border-radius:13px;background:var(--blue-tint);color:var(--blue);
  display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex:none;
}
.contact-line small{display:block;color:#8A99B5;font-weight:500;font-size:.8rem}
.contact-line a{color:var(--blue);font-weight:700}

.contact-form-card{
  background:#fff;border:1px solid var(--line);border-radius:22px;
  box-shadow:var(--shadow);padding:38px 36px;
}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.field{margin-bottom:18px}
.field label{display:block;font-weight:700;color:var(--ink);font-size:.88rem;margin-bottom:7px}
.field input,.field textarea{
  width:100%;border:1.5px solid var(--line);border-radius:11px;padding:13px 15px;
  color:var(--ink);background:#FBFCFE;transition:border-color .2s,box-shadow .2s;
}
.field textarea{min-height:130px;resize:vertical}
.field input:focus,.field textarea:focus{
  outline:none;border-color:var(--blue);box-shadow:0 0 0 4px rgba(30,91,198,.14);background:#fff;
}
.field.error input,.field.error textarea{border-color:#D64545;box-shadow:0 0 0 4px rgba(214,69,69,.12)}
.err-msg{display:none;color:#D64545;font-size:.78rem;font-weight:600;margin-top:6px}
.field.error .err-msg{display:block}
.form-success{
  display:none;text-align:center;padding:36px 12px;
}
.form-success.show{display:block;animation:popIn .45s ease}
@keyframes popIn{from{opacity:0;transform:scale(.94)}to{opacity:1;transform:scale(1)}}
.form-success .big-check{
  width:70px;height:70px;border-radius:50%;background:#E3F6EC;color:var(--green);
  display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 20px;
}
.form-success h3{font-size:1.3rem;margin-bottom:8px}

/* ============================================================
   13. FOOTER
   ============================================================ */
.site-footer{background:var(--navy-deep);color:rgba(255,255,255,.75);padding:72px 0 0}
.footer-grid{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:44px;padding-bottom:56px}
.footer-brand p{font-size:.92rem;max-width:300px;margin-top:16px;color:rgba(255,255,255,.6)}
.footer-col h4{color:#fff;font-size:.82rem;letter-spacing:.12em;text-transform:uppercase;margin-bottom:18px}
.footer-col ul{display:flex;flex-direction:column;gap:11px}
.footer-col a{color:rgba(255,255,255,.68);font-size:.93rem;transition:color .2s}
.footer-col a:hover{color:#fff}
.footer-bottom{
  border-top:1px solid rgba(255,255,255,.1);padding:22px 0;
  display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
  font-size:.86rem;
}
.footer-bottom .legal{display:flex;gap:22px}
.footer-bottom a{color:rgba(255,255,255,.6)}
.footer-bottom a:hover{color:#fff}

/* ============================================================
   14. FLOATING BUTTONS (WhatsApp + back-to-top)
   ============================================================ */
.whatsapp-float{
  position:fixed;right:22px;bottom:22px;z-index:1500;
  width:58px;height:58px;border-radius:50%;background:#25D366;color:#fff;
  display:flex;align-items:center;justify-content:center;font-size:1.7rem;
  box-shadow:0 10px 26px rgba(37,211,102,.45);
  transition:transform .25s ease,box-shadow .25s ease;
}
.whatsapp-float:hover{transform:scale(1.1) translateY(-3px);box-shadow:0 14px 32px rgba(37,211,102,.55)}
.whatsapp-float .pulse{
  position:absolute;inset:0;border-radius:50%;background:rgba(37,211,102,.5);
  animation:pulse 2.2s ease-out infinite;z-index:-1;
}
@keyframes pulse{0%{transform:scale(1);opacity:.7}100%{transform:scale(1.7);opacity:0}}

.back-top{
  position:fixed;right:26px;bottom:94px;z-index:1500;
  width:46px;height:46px;border-radius:50%;background:var(--blue);color:#fff;
  display:flex;align-items:center;justify-content:center;font-size:.9rem;
  box-shadow:0 10px 24px rgba(30,91,198,.4);
  opacity:0;visibility:hidden;transform:translateY(12px);
  transition:all .3s ease;
}
.back-top.show{opacity:1;visibility:visible;transform:translateY(0)}
.back-top:hover{background:var(--blue-dark);transform:translateY(-3px)}

/* ============================================================
   15. SCROLL REVEAL
   ============================================================ */
[data-reveal]{
  opacity:0;transform:translateY(28px);
  transition:opacity .7s ease,transform .7s ease;
  transition-delay:var(--reveal-delay,0s);
}
[data-reveal].revealed{opacity:1;transform:none}

/* ============================================================
   16. RESPONSIVE
   ============================================================ */
@media (max-width:1024px){
  .slide{grid-template-columns:1fr;gap:44px;min-height:auto;padding:56px 0 110px}
  .slide-copy{max-width:640px}
  .mock-wrap{max-width:620px}
  .tabs-wrap{grid-template-columns:1fr;gap:32px}
  .tab-list{flex-direction:row;overflow-x:auto;gap:8px;padding-bottom:8px}
  .tab-list::before{display:none}
  .tab-btn{white-space:nowrap;border-radius:12px;padding:12px 18px;background:#fff;border:1px solid var(--line)}
  .tab-btn::before{display:none}
  .tab-btn.active{background:var(--blue);color:#fff;border-color:var(--blue)}
  .tab-panel{grid-template-columns:1fr;min-height:auto}
  .tab-panels{min-height:auto}
  .tab-panel{position:absolute}
  .tab-panel.active{position:relative}
  .stats-grid{grid-template-columns:repeat(2,1fr)}
  .footer-grid{grid-template-columns:1fr 1fr}
}
@media (max-width:860px){
  .main-nav,.header-actions{display:none}
  .hamburger{display:flex}
  .mobile-menu{display:block}
  .feature-grid,.price-grid,.t-grid{grid-template-columns:1fr;max-width:520px;margin-left:auto;margin-right:auto}
  .p-card.featured{order:-1}
  .contact-grid{grid-template-columns:1fr;gap:40px}
  .section{padding:68px 0}
  .slider-arrow{display:none}
  .fc-steps{right:0}
  .fc-note{left:0}
  .kanban{grid-template-columns:repeat(2,1fr)}
  .form-row{grid-template-columns:1fr}
  .announce .container{gap:10px;font-size:.84rem}
}
@media (max-width:560px){
  .hero-deco{display:none}
  .slide{padding:44px 0 110px}
  .kpi-row{grid-template-columns:1fr 1fr}
  .kpi:last-child{grid-column:1/-1}
  .float-chip{padding:9px 12px}
  .fc-steps .steps{gap:7px}
  .footer-grid{grid-template-columns:1fr}
  .cta-card{padding:52px 26px}
  .contact-form-card{padding:28px 22px}
  .slider-controls{bottom:18px;gap:14px}
}

/* Reduced motion */
@media (prefers-reduced-motion:reduce){
  *,*::before,*::after{animation-duration:.01ms!important;transition-duration:.01ms!important}
  html{scroll-behavior:auto}
  [data-reveal]{opacity:1;transform:none}
}

</style>