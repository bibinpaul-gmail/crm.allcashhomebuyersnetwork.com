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
  <title>Billing (Admin)</title>
  <script>
    async function fetchJSON(url, opts={}){
      const res = await fetch(url, opts);
      if(!res.ok){
        const t = await res.text();
        throw new Error('HTTP '+res.status+' '+t);
      }
      const ct = res.headers.get('content-type')||'';
      return ct.includes('application/json') ? res.json() : {raw: await res.text()};
    }
    function tokenHeader(){
      const t = localStorage.getItem('token')||'';
      return t ? {Authorization: 'Bearer '+t} : {};
    }
    function formatEST(input){
      if(input===undefined||input===null||input==='') return '';
      let d=null;
      if(typeof input==='number'){
        const ms = input < 1e12 ? input*1000 : input; d=new Date(ms);
      } else if(typeof input==='string'){
        const s=input.trim();
        if(/^\d+$/.test(s)){ const n=parseInt(s,10); const ms=n<1e12?n*1000:n; d=new Date(ms); }
        else { const t=Date.parse(s); if(!isNaN(t)) d=new Date(t); }
      } else if(input instanceof Date){ d=input; }
      if(!d||isNaN(d.getTime())) return String(input);
      try{
        return new Intl.DateTimeFormat('en-US',{ timeZone:'America/New_York', year:'numeric', month:'2-digit', day:'2-digit', hour:'2-digit', minute:'2-digit', second:'2-digit' }).format(d);
      }catch(_){ return d.toLocaleString('en-US',{ timeZone:'America/New_York' }); }
    }
    async function loadAccounts(){
      try{
        // Ensure balances reflect Stripe by reconciling first
        try { await fetchJSON('/api/index.php?route=/admin/reconcile-balances', { method:'POST', headers: tokenHeader() }); } catch(_){ }
        const data = await fetchJSON('/api/index.php?route=/admin/accounts',{headers: tokenHeader()});
        const sel = document.getElementById('acc-select');
        sel.innerHTML = '';
        data.items.forEach(a=>{
          const opt = document.createElement('option');
          opt.value = a.id; opt.textContent = a.name+' ('+a.email+') - balance $'+(a.balance_cents/100).toFixed(2);
          sel.appendChild(opt);
        });
        const list = document.getElementById('acc-list');
        list.innerHTML = data.items.map(a=>`<li>${a.name} &lt;${a.email}&gt; — ${a.type} — balance $${(a.balance_cents/100).toFixed(2)}</li>`).join('');
      }catch(e){ console.error(e); alert('Load accounts failed: '+e.message); }
    }
    async function createAccount(ev){ ev.preventDefault();
      const name = document.getElementById('acc-name').value.trim();
      const email = document.getElementById('acc-email').value.trim();
      const type = document.getElementById('acc-type').value;
      if(!name||!email){ alert('Name and email required'); return; }
      try{
        await fetchJSON('/api/index.php?route=/admin/accounts',{
          method:'POST', headers:{'Content-Type':'application/json', ...tokenHeader()},
          body: JSON.stringify({name,email,type})
        });
        await loadAccounts();
        (document.getElementById('acc-form')).reset();
      }catch(e){ console.error(e); alert('Create failed: '+e.message); }
    }
    async function startCheckout(){
      const id = document.getElementById('acc-select').value;
      const dollars = parseFloat(document.getElementById('amount').value||'0');
      if(!id || !(dollars>0)){ alert('Pick account and amount'); return; }
      try{
        const res = await fetchJSON('/api/index.php?route=/payments/checkout',{
          method:'POST', headers:{'Content-Type':'application/json', ...tokenHeader()},
          body: JSON.stringify({account_id:id, amount_cents: Math.round(dollars*100)})
        });
        if(res.url){ window.location.href = res.url; }
      }catch(e){ console.error(e); alert('Checkout failed: '+e.message); }
    }
    async function reconcileIfNeeded(){
      try{
        const usp = new URLSearchParams(location.search);
        const status = usp.get('status')||''; const session_id = usp.get('session_id')||'';
        if (status==='success' && session_id){
          await fetchJSON('/api/index.php?route=/payments/reconcile',{
            method:'POST', headers:{'Content-Type':'application/json', ...tokenHeader()}, body: JSON.stringify({ session_id })
          });
          // Remove query params to avoid repeated reconcile on refresh
          try { history.replaceState({}, '', location.pathname); } catch(_){ }
          const msg = document.getElementById('recon-msg'); if (msg) msg.textContent = 'Payment reconciled at '+formatEST(Date.now());
          // Immediately refresh accounts to reflect new balance, and again shortly after
          try { await loadAccounts(); } catch(_){ }
          setTimeout(()=>{ loadAccounts().catch(()=>{}); }, 1500);
        }
      }catch(e){ console.warn('reconcile failed', e); }
    }
    window.addEventListener('DOMContentLoaded', ()=>{
      document.getElementById('acc-form').addEventListener('submit', createAccount);
      document.getElementById('checkout-btn').addEventListener('click', startCheckout);
      // Ensure balance reflects immediately after returning from Stripe:
      (async ()=>{ await reconcileIfNeeded(); await loadAccounts(); })();
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
  </style>
</head>
<body>
  <div class="wrap">
    <div class="row" style="justify-content: space-between;">
      <div class="row" style="gap:8px"><img src="<?=htmlspecialchars($logo)?>" alt="logo" style="height:28px"/><h1>Billing (Admin)</h1></div>
      <small>Stripe PK: <?= $stripePk ? 'configured' : 'not set' ?></small>
    </div>

    <h2>Create client/builder account</h2>
    <form id="acc-form" class="row">
      <label>Name<br><input id="acc-name" placeholder="Acme Builders" /></label>
      <label>Email<br><input id="acc-email" placeholder="billing@acme.com" /></label>
      <label>Type<br><select id="acc-type"><option value="client">client</option><option value="builder">builder</option></select></label>
      <div style="align-self:flex-end"><button type="submit">Create</button></div>
    </form>

    <h2>Fund an account</h2>
    <div class="row">
      <select id="acc-select" style="min-width:320px"></select>
      <input id="amount" type="number" min="1" step="1" placeholder="Amount ($)" style="width:120px" />
      <button id="checkout-btn">Checkout with Stripe</button>
    </div>
    <small>Note: this uses your admin token from localStorage to call the API.</small>
    <div id="recon-msg" style="margin-top:6px;color:#475569;font-size:12px"></div>

    <h2>Accounts</h2>
    <ul id="acc-list"></ul>
  </div>
</body>
</html>


