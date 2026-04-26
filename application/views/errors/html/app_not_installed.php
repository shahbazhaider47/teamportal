<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Application Not Installed</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- App favicon -->
<link rel="shortcut icon" href="assets/images/404.png">

<style>
:root{
  --bg:#0f172a;
  --panel:#111827;
  --muted:#94a3b8;
  --text:#e5e7eb;
  --accent:#06b6d4;
  --accent-2:#22d3ee;
  --border:#1f2937;
  --chip:#0b1222;
}

*{box-sizing:border-box}

body{
  margin:0;
  background:linear-gradient(180deg,#0b1222,#0c141f 60%,#0b1222);
  color:var(--text);
  font:14px/1.6 system-ui,-apple-system,Segoe UI,Roboto;
}

.wrap{
  max-width:900px;
  margin:8vh auto;
  padding:0 20px;
}

.brand{
  display:flex;
  align-items:center;
  gap:14px;
}

.brand-logo{
  width:42px;
  height:42px;
  border-radius:10px;
  background:linear-gradient(145deg,var(--accent),var(--accent-2));
  box-shadow:0 6px 24px rgba(6,182,212,.45);
}

.brand h1{
  margin:0;
  font-size:20px;
  font-weight:700;
  letter-spacing:.3px;
}

.panel{
  margin-top:26px;
  background:rgba(17,24,39,.65);
  border:1px solid var(--border);
  border-radius:18px;
  overflow:hidden;
  box-shadow:0 12px 44px rgba(0,0,0,.4);
}

.panel__hero{
  padding:30px;
  background:radial-gradient(
    1200px 500px at 100% -50%,
    rgba(34,211,238,.14),
    transparent 60%
  );
}

.panel__hero h2{
  margin:0;
  font-size:28px;
}

.panel__hero p{
  margin:8px 0 0;
  color:var(--muted);
}

.panel__body{
  padding:26px 30px 30px;
}

.grid{
  display:grid;
  grid-template-columns:1.6fr 1fr;
  gap:22px;
}

@media (max-width:900px){
  .grid{grid-template-columns:1fr}
}

.card{
  background:rgba(15,23,42,.6);
  border:1px solid var(--border);
  border-radius:14px;
  padding:18px;
}

.card h3{
  margin:0 0 8px;
  font-size:15px;
}

.list{
  margin:8px 0 0;
  padding-left:18px;
}

.list li{
  margin:6px 0;
}

.chips{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top:8px;
}

.chip{
  padding:6px 10px;
  border-radius:999px;
  background:var(--chip);
  border:1px solid var(--border);
  font-size:12px;
  color:var(--muted);
}

.cta{
  display:flex;
  justify-content:flex-end;
  gap:12px;
  flex-wrap:wrap;
  margin-top:22px;
}

.btn{
  appearance:none;
  cursor:pointer;
  border-radius:10px;
  padding:10px 16px;
  font-weight:700;
  text-decoration:none;
  border:1px solid transparent;
}

.btn-primary{
  background:linear-gradient(145deg,var(--accent),var(--accent-2));
  color:#041016;
  box-shadow:0 8px 28px rgba(34,211,238,.35);
}

.notice{
  margin-top:18px;
  padding:14px 16px;
  border-radius:12px;
  background:rgba(239,68,68,.08);
  border:1px solid rgba(239,68,68,.35);
  color:#fecaca;
}

code{
  background:#0b1222;
  border:1px solid var(--border);
  border-radius:6px;
  padding:2px 6px;
}

.footer{
  margin:26px 0 10px;
  text-align:center;
  color:var(--muted);
  font-size:12px;
}
</style>
</head>

<body>

<div class="wrap">

  <div class="brand">
    <div class="brand-logo"></div>
    <h1>System Setup</h1>
  </div>

  <div class="panel">

    <div class="panel__hero">
      <h2>Application Not Installed</h2>
      <p>
        We couldn’t detect a completed configuration.
        Please complete the installation to continue.
      </p>
    </div>

    <div class="panel__body">

      <div class="grid">

        <div class="card">
          <h3>What you’ll need</h3>
          <ul class="list">
            <li>Database credentials (host, name, user, password)</li>
            <li>Writable directories:
              <code>application/config</code>,
              <code>application/logs</code>,
              <code>uploads/</code>
            </li>
            <li>PHP 8.1+ with required extensions enabled</li>
          </ul>
        </div>

        <div class="card">
          <h3>Best practices</h3>
          <div class="chips">
            <span class="chip">InnoDB + utf8mb4</span>
            <span class="chip">CREATE / ALTER privileges</span>
            <span class="chip">SSL-ready environment</span>
          </div>
          <p class="muted" style="margin-top:10px">
            The installer validates all steps and safely rolls back on failure.
          </p>
        </div>

      </div>

      <?php if ($installDirExists): ?>
        <div class="cta">
          <a class="btn btn-primary"
             href="<?= htmlspecialchars($installUrl, ENT_QUOTES, 'UTF-8'); ?>">
            Launch Installer →
          </a>
        </div>
      <?php else: ?>
        <div class="notice">
          <strong>Installer Missing</strong><br>
          The required <code>/install</code> directory was not found at the application root.
          Please re-upload the installer files to proceed.
        </div>
      <?php endif; ?>

    </div>
  </div>

  <div class="footer">
    If already installed, ensure
    <code>application/config/app-config.php</code> and
    <code>installed.lock</code> exist, and remove the
    <code>/install</code> directory.
  </div>

</div>

</body>
</html>
