<?php
require __DIR__ . '/../bootstrap.php';
use App\Config\Config;
\App\Config\Config::load(dirname(__DIR__));
$logo = Config::string('LOGO_URL', '/logo.png');
$token = isset($_GET['token']) ? (string)$_GET['token'] : '';
$magic = isset($_GET['magic']) ? (string)$_GET['magic'] : '';
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
      if (!opts.credentials) { opts.credentials = 'same-origin'; }
      const res = await fetch(url, opts);
      if(!res.ok){ throw new Error('HTTP '+res.status); }
      return res.json();
    }
    async function tryMagic(){
      const magic = (new URLSearchParams(location.search)).get('magic');
      if(!magic) return;
      try{
        await fetchJSON('/api/index.php?route=/client/magic/verify&token='+encodeURIComponent(magic));
      }catch(e){
        // Ignore expired/invalid magic link
      }
      try{ history.replaceState({}, '', location.pathname); }catch(_){ }
    }
    function formatEST(input){
      if(!input) return '';
      const d = new Date(input);
      if (isNaN(d.getTime())) return String(input);
      try{
        return new Intl.DateTimeFormat('en-US', {
          timeZone: 'America/New_York',
          year: 'numeric', month: '2-digit', day: '2-digit',
          hour: '2-digit', minute: '2-digit', second: '2-digit'
        }).format(d);
      }catch(_){
        return d.toLocaleString('en-US', { timeZone: 'America/New_York' });
      }
    }
    async function loadPortal(){
      await tryMagic();
      const token = (document.getElementById('portal-token').value||'').trim();
      let portalMode = false;
      let acctName = '', acctEmail = '', balanceCents = 0, payments = [];
      if (token){
        const data = await fetchJSON('/api/index.php?route=/accounts/portal&token='+encodeURIComponent(token));
        portalMode = true;
        acctName = data.account?.name||'';
        acctEmail = data.account?.email||'';
        balanceCents = data.account?.balance_cents||0;
        payments = data.payments||[];
      } else {
        await fetchJSON('/api/index.php?route=/client/reconcile-balance', { method: 'POST' }).catch(()=>{});
        let data;
        try{
          data = await fetchJSON('/api/index.php?route=/client/me');
        }catch(e){
          document.getElementById('acct-info').innerHTML = '<div>Please open a fresh magic link from your email or request one below.</div>';
          const req = document.getElementById('req-magic-wrap'); if (req) req.style.display = '';
          return;
        }
        acctName = data.name||'';
        acctEmail = data.email||'';
        balanceCents = data.balance_cents||0;
        try {
          const p = await fetchJSON('/api/index.php?route=/client/payments');
          payments = p.items||[];
        } catch(_) { payments = []; }
      }
      document.getElementById('acct-info').innerHTML = `<div><b>${acctName}</b> &lt;${acctEmail}&gt; — Balance $${(balanceCents/100).toFixed(2)}</div>`;
      const fundsPayments = document.getElementById('funds-payments');
      if (fundsPayments) { fundsPayments.style.display = portalMode ? 'none' : ''; }
      const tbody = document.getElementById('pay-body');
      if (tbody) { tbody.innerHTML = (payments||[]).map(p=>`<tr><td>$${(p.amount_cents/100).toFixed(2)}</td><td>${p.currency}</td><td>${formatEST(p.ts)}</td></tr>`).join(''); }
      const btn = document.getElementById('checkout-btn');
      if (btn) btn.onclick = async ()=>{
        const dollars = parseFloat((document.getElementById('amount')||{}).value||'0');
        if(!(dollars>0)) { alert('Enter amount'); return; }
        const body = portalMode ? { portal_token: token, amount_cents: Math.round(dollars*100) }
                                : { amount_cents: Math.round(dollars*100) };
        const res = await fetchJSON('/api/index.php?route=/payments/checkout',{
          method:'POST', headers:{'Content-Type':'application/json'},
          body: JSON.stringify(body)
        });
        if(res.url){ window.location.href = res.url; }
      };
    }
    window.addEventListener('DOMContentLoaded', ()=>{
      const inp = document.getElementById('portal-token');
      const preset = '<?= htmlspecialchars($token, ENT_QUOTES) ?>';
      if(preset){ inp.value = preset; }
      document.getElementById('load-btn').addEventListener('click', loadPortal);
      // If redirected back from Stripe success, reconcile then reload
      const params = new URLSearchParams(location.search);
      if (params.get('status') === 'success' && params.has('session_id')) {
        const sid = params.get('session_id');
        fetchJSON('/api/index.php?route=/payments/reconcile', {
          method: 'POST', headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ session_id: sid })
        }).then(()=>{
          try{ history.replaceState({}, '', location.pathname); }catch(_){}
          loadPortal();
          setTimeout(loadPortal, 1500);
        }).catch(()=>{ loadPortal(); });
        return;
      }
      loadPortal();

      // Self-serve magic link request
      const btn = document.getElementById('req-magic-btn');
      if (btn) btn.addEventListener('click', async ()=>{
        const email = (document.getElementById('req-email').value||'').trim().toLowerCase();
        const mins = Math.max(1, Math.min(1440, parseInt(document.getElementById('req-exp').value||'15',10)));
        if(!email){ alert('Enter your email'); return; }
        try{
          const res = await fetchJSON('/api/index.php?route=/client/magic/start', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ email, expires_minutes: mins })
          });
          const el = document.getElementById('req-magic-result');
          if (el) el.textContent = res.emailed
            ? `Magic link emailed (expires in ${res.expires_minutes||mins} min).`
            : `If this email is registered, a magic link has been sent.`;
        }catch(_){ alert('Failed to send magic link'); }
      });
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
    <div id="req-magic-wrap" class="row" style="margin:8px 0 16px; display:none">
      <input id="req-email" type="email" placeholder="Enter your account email" />
      <input id="req-exp" type="number" min="1" max="1440" value="15" title="Expiry in minutes" />
      <button id="req-magic-btn">Email me a magic link</button>
      <div id="req-magic-result" style="font-size:12px;color:#475569;margin-left:8px"></div>
    </div>
    <div id="funds-payments" style="display:none">
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
  </div>
</body>
</html>


