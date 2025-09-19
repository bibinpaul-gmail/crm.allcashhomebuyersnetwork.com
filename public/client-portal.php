<?php
require __DIR__ . '/../bootstrap.php';
use App\Config\Config;
\App\Config\Config::load(dirname(__DIR__));
$logo = Config::string('LOGO_URL', '/logo.png');
$token = isset($_GET['token']) ? (string)$_GET['token'] : '';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Client Portal</title>
  <style>
    body{font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; background:#f1f5f9; margin:0}
    .wrap{max-width:780px;margin:20px auto;padding:20px;background:#fff;border:1px solid #e2e8f0;border-radius:8px}
    .row{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
    input,button{padding:8px;border-radius:6px;border:1px solid #cbd5e1}
    button{background:#0f766e;color:#fff;border:0;cursor:pointer}
    h1{font-size:18px;margin:8px 0}
    h2{font-size:16px;margin:16px 0 8px}
    table{width:100%;border-collapse:collapse}
    th,td{border:1px solid #e2e8f0;padding:8px;text-align:left}
  </style>
  <script>
    async function fetchJSON(url, opts={}){
      const res = await fetch(url, opts);
      if(!res.ok){ throw new Error('HTTP '+res.status); }
      return res.json();
    }
    async function loadPortal(){
      const token = (document.getElementById('portal-token').value||'').trim();
      if(!token){ alert('Enter portal token'); return; }
      const data = await fetchJSON('/api/index.php?route=/accounts/portal&token='+encodeURIComponent(token));
      const info = document.getElementById('acct-info');
      info.innerHTML = `<div><b>${data.account.name}</b> &lt;${data.account.email}&gt; — Balance $${(data.account.balance_cents/100).toFixed(2)}</div>`;
      const tbody = document.getElementById('pay-body');
      tbody.innerHTML = (data.payments||[]).map(p=>`<tr><td>$${(p.amount_cents/100).toFixed(2)}</td><td>${p.currency}</td><td>${p.ts||''}</td></tr>`).join('');
      document.getElementById('checkout-btn').onclick = async ()=>{
        const dollars = parseFloat((document.getElementById('amount')||{}).value||'0');
        if(!(dollars>0)) { alert('Enter amount'); return; }
        const res = await fetchJSON('/api/index.php?route=/payments/checkout',{
          method:'POST', headers:{'Content-Type':'application/json'},
          body: JSON.stringify({portal_token: token, amount_cents: Math.round(dollars*100)})
        });
        if(res.url){ window.location.href = res.url; }
      };
    }
    window.addEventListener('DOMContentLoaded', ()=>{
      const inp = document.getElementById('portal-token');
      const preset = '<?= htmlspecialchars($token, ENT_QUOTES) ?>';
      if(preset){ inp.value = preset; }
      document.getElementById('load-btn').addEventListener('click', loadPortal);
      if (preset) { loadPortal(); }
    });
  </script>
</head>
<body>
  <div class="wrap">
    <div class="row" style="justify-content: space-between;">
      <div class="row" style="gap:8px"><img src="<?=htmlspecialchars($logo)?>" alt="logo" style="height:28px"/><h1>Client Portal</h1></div>
      <div class="row"><input id="portal-token" placeholder="Portal token" /><button id="load-btn">Load</button></div>
    </div>
    <div id="acct-info" style="margin:8px 0 16px"></div>
    <div class="row" style="margin-bottom:16px">
      <input id="amount" type="number" min="1" step="1" placeholder="Amount ($)" />
      <button id="checkout-btn">Add Funds</button>
    </div>
    <h2>Payments</h2>
    <table>
      <thead><tr><th>Amount</th><th>Currency</th><th>Date</th></tr></thead>
      <tbody id="pay-body"></tbody>
    </table>
  </div>
</body>
</html>


