<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Security Notice | Installer Still Present</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
:root{
  --bg:#0f172a;
  --panel:#111827;
  --muted:#94a3b8;
  --text:#e5e7eb;
  --accent:#06b6d4;
  --accent-2:#22d3ee;
  --danger:#ef4444;
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
  max-width:820px;
  margin:10vh auto;
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
  background:linear-gradient(145deg,var(--danger),#fb7185);
  box-shadow:0 6px 24px rgba(239,68,68,.45);
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
  box-shadow:0 12px 44px rgba(0,0,0,.45);
}

.panel__hero{
  padding:28px;
  background:radial-gradient(
    1200px 500px at 100% -50%,
    rgba(239,68,68,.14),
    transparent 60%
  );
}

.panel__hero h2{
  margin:0;
  font-size:26px;
}

.panel__hero p{
  margin:8px 0 0;
  color:var(--muted);
}

.panel__body{
  padding:24px 28px 28px;
}

.notice{
  display:flex;
  gap:14px;
  padding:16px 18px;
  border-radius:14px;
  background:rgba(239,68,68,.08);
  border:1px solid rgba(239,68,68,.35);
  color:#fecaca;
}

.notice strong{
  display:block;
  margin-bottom:4px;
  font-size:14px;
}

.path{
  margin-top:14px;
  padding:14px 16px;
  background:var(--chip);
  border:1px solid var(--border);
  border-radius:12px;
  font-family:ui-monospace, SFMono-Regular, Menlo, monospace;
  font-size:13px;
  word-break:break-all;
}

.cta{
  display:flex;
  justify-content:flex-end;
  margin-top:20px;
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

.footer{
  margin:26px 0 10px;
  text-align:center;
  color:var(--muted);
  font-size:12px;
}

code{
  background:#0b1222;
  border:1px solid var(--border);
  border-radius:6px;
  padding:2px 6px;
}
</style>
</head>

<body>

<div class="wrap">

  <div class="brand">
    <div class="brand-logo"></div>
    <h1>Security Protection</h1>
  </div>

  <div class="panel">

    <div class="panel__hero">
      <h2>Installer Folder Still Present</h2>
      <p>
        For security reasons, access to the application has been blocked.
      </p>
    </div>

    <div class="panel__body">

      <div class="notice">
        <div>
          <strong>Critical Security Risk</strong>
          Your application is already installed, but the
          <code>/install</code> directory still exists.
          Leaving this directory accessible can allow reinstallation or
          configuration tampering.
        </div>
      </div>

      <div class="path">
        <?= htmlspecialchars($installDirPath, ENT_QUOTES, 'UTF-8'); ?>
      </div>

      <div class="cta">
        <a class="btn btn-primary"
           href="<?= htmlspecialchars(
             ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
             . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
             . ($_SERVER['REQUEST_URI'] ?? '/'),
             ENT_QUOTES,
             'UTF-8'
           ); ?>">
          I’ve Removed It — Refresh
        </a>
      </div>

    </div>
  </div>

  <div class="footer">
    Delete the <code>/install</code> directory from your server to restore access.
  </div>

</div>

</body>
</html>
