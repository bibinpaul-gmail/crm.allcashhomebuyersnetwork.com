<?php
require __DIR__ . '/../bootstrap.php';
use App\Config\Config;
\App\Config\Config::load(dirname(__DIR__));
$logo = Config::string('LOGO_URL', '/logo.png');
$stripePk = Config::string('STRIPE_PUBLISHABLE_KEY', '');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Subscriptions (Admin)</title>
  <script>
    async function fetchJSON(url, opts={}){
      const res = await fetch(url, opts);
      if(!res.ok){ const t = await res.text(); throw new Error('HTTP '+res.status+' '+t); }
      const ct = res.headers.get('content-type')||'';
      return ct.includes('application/json') ? res.json() : {raw: await res.text()};
    }
    function tokenHeader(){ const t = localStorage.getItem('token')||''; return t?{Authorization:'Bearer '+t}:{ }; }
    function formatEST(input){
      if(input===undefined||input===null||input==='') return '';
      let d=null; if(typeof input==='number'){ const ms=input<1e12?input*1000:input; d=new Date(ms); }
      else if(typeof input==='string'){ const s=input.trim(); if(/^\d+$/.test(s)){ const n=parseInt(s,10); const ms=n<1e12?n*1000:n; d=new Date(ms);} else { const t=Date.parse(s); if(!isNaN(t)) d=new Date(t);} }
      else if(input instanceof Date){ d=input; }
      if(!d||isNaN(d.getTime())) return String(input);
      try{ return new Intl.DateTimeFormat('en-US',{ timeZone:'America/New_York', year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit', second:'2-digit' }).format(d); }
      catch(_){ return d.toLocaleString('en-US',{ timeZone:'America/New_York' }); }
    }
    async function loadAccounts(){
      const data = await fetchJSON('/api/index.php?route=/admin/accounts',{headers: tokenHeader()});
      const sel = document.getElementById('acc-select'); sel.innerHTML='';
      data.items.forEach(a=>{ const opt=document.createElement('option'); opt.value=a.id; opt.textContent=a.name+' ('+a.email+') - balance $'+(a.balance_cents/100).toFixed(2); sel.appendChild(opt); });
    }
    async function loadPrices(){
      const data = await fetchJSON('/api/index.php?route=/admin/stripe/prices',{headers: tokenHeader()});
      const sel = document.getElementById('price-select'); sel.innerHTML='';
      data.items.forEach(p=>{ const opt=document.createElement('option'); opt.value=p.id; opt.textContent=(p.product||p.nickname||p.id)+' — $'+(p.unit_amount/100).toFixed(2)+'/'+p.interval; sel.appendChild(opt); });
    }
    async function startSubscription(){
      const id = document.getElementById('acc-select').value;
      const price = document.getElementById('price-select').value;
      const qty = parseInt(document.getElementById('qty').value||'1',10)||1;
      if(!id||!price){ alert('Pick account and price'); return; }
      const res = await fetchJSON('/api/index.php?route=/admin/subscriptions/checkout',{
        method:'POST', headers:{'Content-Type':'application/json', ...tokenHeader()},
        body: JSON.stringify({account_id:id, price_id:price, quantity:qty})
      });
      if(res.url){ window.location.href = res.url; }
    }
    async function reconcileIfNeeded(){
      const usp = new URLSearchParams(location.search);
      const status = usp.get('status')||''; const session_id = usp.get('session_id')||'';
      if (status==='success' && session_id){
        try {
          await fetchJSON('/api/index.php?route=/admin/subscriptions/reconcile',{
            method:'POST', headers:{'Content-Type':'application/json', ...tokenHeader()}, body: JSON.stringify({ session_id })
          });
        } catch(_){}
        try { history.replaceState({}, '', location.pathname); } catch(_){ }
        const msg = document.getElementById('status-msg'); if (msg) msg.textContent = 'Subscription reconciled at '+formatEST(Date.now());
      }
    }
    window.addEventListener('DOMContentLoaded', async ()=>{
      await Promise.all([loadAccounts(), loadPrices()]);
      document.getElementById('btn-start').addEventListener('click', startSubscription);
      await reconcileIfNeeded();
    });
  </script>
  <style>
    body{font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji"; background:#f1f5f9; margin:0;}
    .wrap{max-width:900px;margin:20px auto;padding:20px;background:#fff;border:1px solid #e2e8f0;border-radius:8px}
    .row{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
    label{font-size:12px;color:#475569}
    input,select{padding:8px;border:1px solid #cbd5e1;border-radius:6px}
    button{padding:8px 12px;border-radius:6px;border:0;background:#0f766e;color:#fff;cursor:pointer}
    h1{font-size:18px;margin:8px 0}
    h2{font-size:16px;margin:16px 0 8px}
    small{color:#64748b}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px;border-bottom:1px solid #e2e8f0;font-size:14px}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="row" style="justify-content: space-between;">
      <div class="row" style="gap:8px"><img src="<?=htmlspecialchars($logo)?>" alt="logo" style="height:28px"/><h1>Subscriptions (Admin)</h1></div>
      <small>Stripe PK: <?= $stripePk ? 'configured' : 'not set' ?></small>
    </div>

    <h2>Create subscription for account</h2>
    <div class="row">
      <label>Account<br><select id="acc-select" style="min-width:320px"></select></label>
      <label>Plan Price<br><select id="price-select" style="min-width:320px"></select></label>
      <label>Qty<br><input id="qty" type="number" min="1" step="1" value="1" style="width:100px" /></label>
      <div style="align-self:flex-end"><button id="btn-start">Start Subscription</button></div>
    </div>
    <small>Uses your admin token from localStorage to call the API.</small>
    <div id="status-msg" style="margin-top:6px;color:#475569;font-size:12px"></div>
  </div>
</body>
</html>


