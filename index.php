<?php
require 'config.php';
$s = settings();
$photos = db()->query('SELECT id FROM photos ORDER BY pos,id')->fetchAll(PDO::FETCH_COLUMN);
$status = $_GET['sent'] ?? '';
if (!in_array($status, ['ok_yes','ok_no','error'], true)) $status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_ok() && empty($_POST['website'])) {
    $name = trim(mb_substr($_POST['name'] ?? '', 0, 80));
    $attending = ($_POST['attending'] ?? '') === 'yes' ? 'yes' : 'no';
    $guests = $attending === 'yes' ? max(1, min(10, (int)($_POST['guests'] ?? 1))) : 0;
    $note = trim(mb_substr($_POST['note'] ?? '', 0, 400));
    if ($name === '') {
        header('Location: index.php?sent=error#rsvp'); exit;
    } else {
        db()->prepare('INSERT INTO rsvps(id,name,attending,guests,note) VALUES(?,?,?,?,?)')->execute([bin2hex(random_bytes(4)), $name, $attending, $guests, $note]);
        header('Location: index.php?sent=ok_' . $attending . '#rsvp'); exit;
    }
}
$ts = strtotime($s['date']);
$hero = $photos ? 'photo.php?id=' . (int)$photos[0] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($s['bride']) ?> &amp; <?= e($s['groom']) ?> — Wedding Invitation</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root{
  --paper:#faf6ef;      /* page background */
  --linen:#f1eadd;      /* alternate band */
  --ink:#2c2a25;        /* body text */
  --soft:#6f695c;       /* secondary text */
  --gold:#b0905a;       /* accents, rules */
  --gold-deep:#8c6e3e;  /* headings */
  --line:rgba(176,144,90,.35);
  --serif:'Cormorant Garamond',Georgia,serif;
  --sans:'Jost',system-ui,sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{background:var(--paper);color:var(--ink);font-family:var(--sans);font-weight:300;line-height:1.75;-webkit-font-smoothing:antialiased}
h1,h2,h3{font-family:var(--serif);font-weight:300}
img{display:block;max-width:100%}

/* ---------- Hero ---------- */
.hero{position:relative;min-height:100svh;display:grid;place-items:center;text-align:center;padding:3rem 1.5rem;overflow:hidden;color:#fbf7ef;background:#2c2a25}
.hero-bg{position:absolute;inset:-2%;background-size:cover;background-position:center;transform:scale(1.04);animation:drift 24s ease-in-out infinite alternate}
.hero::after{content:"";position:absolute;inset:0;background:radial-gradient(ellipse at center,rgba(24,21,17,.55) 0%,rgba(24,21,17,.68) 60%,rgba(24,21,17,.82) 100%)}
.hero.plain{background:radial-gradient(circle at 50% 35%,#fffdf8,var(--linen) 75%);color:var(--ink)}
.hero.plain::after{display:none}
.frame{position:relative;z-index:1;max-width:720px;width:100%;padding:4.5rem 3rem;border:1px solid rgba(214,188,140,.7);outline:1px solid rgba(214,188,140,.3);outline-offset:12px;background:rgba(22,19,15,.5);-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);text-shadow:0 1px 14px rgba(0,0,0,.55)}
.hero.plain .frame{background:none;-webkit-backdrop-filter:none;backdrop-filter:none;text-shadow:none}
.hero.plain .frame{border-color:var(--gold);outline-color:var(--line)}
.eyebrow{font-size:.74rem;font-weight:400;letter-spacing:.42em;text-transform:uppercase;color:#ecd9b3}
.hero.plain .eyebrow{color:var(--gold-deep)}
.hero .msg{max-width:440px;margin:1.4rem auto 2rem;font-family:var(--serif);font-style:italic;font-size:1.25rem;line-height:1.6}
.names{font-size:clamp(3.2rem,10vw,6.6rem);line-height:1;font-weight:300;letter-spacing:.01em}
.names .amp{display:block;font-style:italic;font-size:.45em;color:#e3cfa6;margin:.25em 0 .2em}
.hero.plain .names{color:var(--gold-deep)}
.hero.plain .names .amp{color:var(--gold)}
.when{display:flex;align-items:center;justify-content:center;gap:1.2rem;margin-top:2.4rem;font-size:.82rem;letter-spacing:.32em;text-transform:uppercase}
.when i{display:block;width:40px;height:1px;background:currentColor;opacity:.55}
.venue{margin-top:.7rem;font-family:var(--serif);font-size:1.15rem;letter-spacing:.06em;}
.scroll{position:absolute;z-index:1;bottom:1.8rem;left:50%;transform:translateX(-50%);width:1px;height:46px;background:linear-gradient(currentColor,transparent);opacity:.6}

/* ---------- Sections ---------- */
section{padding:clamp(4.5rem,10vw,7.5rem) 1.5rem;text-align:center}
.wrap{max-width:960px;margin:0 auto}
.narrow{max-width:540px;margin:0 auto}
h2{font-size:clamp(2.2rem,5.5vw,3.2rem);color:var(--gold-deep);font-style:italic;line-height:1.15}
.kicker{display:block;font-family:var(--sans);font-style:normal;font-size:.7rem;letter-spacing:.42em;text-transform:uppercase;color:var(--gold-deep);margin-bottom:.9rem}
.orn{display:flex;align-items:center;justify-content:center;gap:.8rem;margin:1.6rem auto 2.8rem;color:var(--gold)}
.orn::before,.orn::after{content:"";width:56px;height:1px;background:var(--line)}
.orn svg{width:14px;height:14px}
.lead{color:var(--soft);font-size:1rem}
.band{background:var(--linen)}

/* Countdown */
.count{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));max-width:640px;margin:0 auto;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
.count div{padding:1.6rem .5rem}
.count div+div{border-left:1px solid var(--line)}
.count b{display:block;font-family:var(--serif);font-weight:300;font-size:clamp(2.4rem,7vw,3.8rem);line-height:1.1;color:var(--ink);font-variant-numeric:lining-nums tabular-nums}
.count small{font-size:.66rem;letter-spacing:.32em;color:var(--soft)}

/* Details */
.details{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.2rem;max-width:760px;margin:3.5rem auto 0}
.card{background:#fffdf9;border:1px solid var(--line);padding:2.2rem 1.5rem}
.card h3{font-size:1.5rem;color:var(--gold-deep);margin-bottom:.6rem;font-style:italic}
.card p{color:var(--soft);font-size:.95rem}

/* Gallery */
/* Uniform tiles, auto-wrapped and centred, so any mix of photo sizes lines up evenly */
.gallery{--gap:16px;display:flex;flex-wrap:wrap;justify-content:center;gap:var(--gap)}
.gallery figure{flex:0 0 calc((100% - 2*var(--gap))/3);aspect-ratio:4/5;overflow:hidden;background:#fffdf9;border:1px solid var(--line);cursor:zoom-in}
.gallery img{width:100%;height:100%;object-fit:cover;object-position:center 30%;transition:transform .9s cubic-bezier(.2,.7,.2,1),filter .9s}
.gallery figure:hover img{transform:scale(1.035);filter:brightness(1.04)}

/* RSVP */
.rsvp-card{background:#fffdf9;border:1px solid var(--line);padding:clamp(2rem,6vw,3.2rem);text-align:left;margin-top:.5rem}
form{display:grid;gap:1.6rem}
label{display:block;font-size:.68rem;letter-spacing:.28em;text-transform:uppercase;color:var(--gold-deep);margin-bottom:.15rem}
input,select,textarea{width:100%;background:transparent;border:0;border-bottom:1px solid var(--line);color:var(--ink);padding:.55rem 0;font:inherit;font-size:1.02rem;border-radius:0;transition:border-color .3s}
textarea{resize:vertical}
select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6' fill='none' stroke='%23b0905a'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right .2rem center;cursor:pointer}
input:focus,select:focus,textarea:focus{outline:none;border-bottom-color:var(--gold-deep)}
input:focus-visible,select:focus-visible,textarea:focus-visible{box-shadow:0 1px 0 var(--gold-deep)}
button{margin-top:.6rem;background:var(--ink);color:#fbf7ef;border:1px solid var(--ink);padding:1.05rem;font:inherit;font-size:.78rem;font-weight:400;letter-spacing:.34em;text-transform:uppercase;cursor:pointer;transition:background .35s,color .35s}
button:hover,button:focus-visible{background:transparent;color:var(--ink)}
.note{padding:1rem 1.2rem;border-left:2px solid var(--gold);background:var(--linen);margin-bottom:1.8rem;color:var(--ink);font-family:var(--serif);font-size:1.15rem;font-style:italic;text-align:left}
.hp{position:absolute;left:-9999px}

/* Footer */
footer{background:var(--ink);color:#d9cfbd;text-align:center;padding:4rem 1rem 3.2rem}
.mono{font-family:var(--serif);font-size:2.4rem;font-style:italic;color:#e3cfa6;line-height:1}
.mono span{font-size:.6em;margin:0 .25em;opacity:.7}
footer p{margin-top:1rem;font-size:.7rem;letter-spacing:.38em;text-transform:uppercase;opacity:.7}

/* Lightbox */
#lb{position:fixed;inset:0;background:rgba(24,22,18,.94);display:none;place-items:center;z-index:9;cursor:zoom-out;padding:4vh 4vw}
#lb.on{display:grid;animation:fade .3s ease}
#lb img{max-width:100%;max-height:92vh;box-shadow:0 20px 60px rgba(0,0,0,.5)}

/* Motion */
.reveal{opacity:0;transform:translateY(22px);transition:opacity 1.1s ease,transform 1.1s ease}
.reveal.in{opacity:1;transform:none}
@keyframes rise{from{opacity:0;transform:translateY(18px)}}
@keyframes fade{from{opacity:0}}
@keyframes drift{to{transform:scale(1.1)}}
@media(prefers-reduced-motion:no-preference){.frame{animation:rise 1.6s ease both}}
@media(prefers-reduced-motion:reduce){.hero-bg{animation:none}.reveal{opacity:1;transform:none;transition:none}html{scroll-behavior:auto}}
@media(max-width:600px){
  .frame{padding:3rem 1.3rem;outline-offset:7px}
  .count{grid-template-columns:repeat(2,1fr)}
  .count div:nth-child(3){border-left:0}
  .count div:nth-child(n+3){border-top:1px solid var(--line)}
  .when i{width:22px}
  .gallery{--gap:10px}
  .gallery figure{flex-basis:calc((100% - var(--gap))/2)}
}
</style>
</head>
<body>
<?php
$orn = '<div class="orn" aria-hidden="true"><svg viewBox="0 0 14 14"><path d="M7 0l2 5 5 2-5 2-2 5-2-5-5-2 5-2z" fill="currentColor"/></svg></div>';
$initial = fn($n) => e(mb_strtoupper(mb_substr(trim($n), 0, 1)));
?>
<header class="hero<?= $hero ? '' : ' plain' ?>">
  <?php if ($hero): ?><div class="hero-bg" style="background-image:url('<?= e($hero) ?>')"></div><?php endif; ?>
  <div class="frame">
    <p class="eyebrow">The wedding of</p>
    <h1 class="names"><?= e($s['bride']) ?><span class="amp">&amp;</span><?= e($s['groom']) ?></h1>
    <p class="msg"><?= e($s['message']) ?></p>
    <p class="when"><i></i><?= $ts ? e(date('F j · Y', $ts)) : '' ?><i></i></p>
    <p class="venue"><?= e($s['venue']) ?></p>
  </div>
  <span class="scroll" aria-hidden="true"></span>
</header>

<section>
  <div class="wrap reveal">
    <h2><span class="kicker">Save the date</span>Until we say “I do”</h2>
    <?= $orn ?>
    <div class="count" id="count" data-t="<?= (int)$ts ?>">
      <div><b id="d">0</b><small>DAYS</small></div><div><b id="h">0</b><small>HOURS</small></div>
      <div><b id="m">0</b><small>MINUTES</small></div><div><b id="s">0</b><small>SECONDS</small></div>
    </div>
    <div class="details">
      <div class="card"><h3>When</h3><p><?= $ts ? e(date('l', $ts)) . '<br>' . e(date('F j, Y', $ts)) . '<br>' . e(date('g:i A', $ts)) : '' ?></p></div>
      <div class="card"><h3>Where</h3><p><?= e($s['venue']) ?><br><?= e($s['address']) ?></p></div>
    </div>
  </div>
</section>

<?php if ($photos): ?>
<section class="band">
  <div class="wrap reveal">
    <h2><span class="kicker">Moments</span>Our story in pictures</h2>
    <?= $orn ?>
    <div class="gallery">
      <?php foreach ($photos as $p): ?><figure><img src="photo.php?id=<?= (int)$p ?>" alt="<?= e($s['bride']) ?> and <?= e($s['groom']) ?>" loading="lazy"></figure><?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section id="rsvp"<?= $photos ? '' : ' class="band"' ?>>
  <div class="narrow reveal">
    <h2><span class="kicker">RSVP</span>Kindly reply</h2>
    <?= $orn ?>
    <p class="lead" style="margin-bottom:2.2rem">We would be honoured by your presence.<br>Please respond by <?= e($s['rsvp_deadline']) ?>.</p>
    <div class="rsvp-card">
      <?php if ($status === 'ok_yes'): ?><p class="note">Thank you! We can’t wait to celebrate with you.</p>
      <?php elseif ($status === 'ok_no'): ?><p class="note">Thank you for letting us know. You will be missed.</p>
      <?php elseif ($status === 'error'): ?><p class="note">Please enter your name.</p><?php endif; ?>
      <form method="post" action="#rsvp">
        <input type="hidden" name="csrf" value="<?= csrf() ?>">
        <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
        <div><label for="name">Full name</label><input id="name" name="name" required maxlength="80" autocomplete="name"></div>
        <div><label for="attending">Will you attend?</label>
          <select id="attending" name="attending"><option value="yes">Joyfully accept</option><option value="no">Regretfully decline</option></select></div>
        <div id="gw"><label for="guests">Number of guests (including you)</label><input id="guests" type="number" name="guests" min="1" max="10" value="1"></div>
        <div><label for="note">A message for the couple (optional)</label><textarea id="note" name="note" rows="3" maxlength="400"></textarea></div>
        <button type="submit">Send RSVP</button>
      </form>
    </div>
  </div>
</section>

<footer>
  <div class="mono"><?= $initial($s['bride']) ?><span>&amp;</span><?= $initial($s['groom']) ?></div>
  <p><?= e($s['bride']) ?> &amp; <?= e($s['groom']) ?> · <?= $ts ? e(date('F j, Y', $ts)) : '' ?></p>
</footer>
<div id="lb" role="dialog" aria-label="Photo"><img alt=""></div>
<script>
const c=document.getElementById('count'),t=+c.dataset.t*1000;
const pad=n=>String(n).padStart(2,'0');
function tick(){let x=Math.max(0,t-Date.now())/1000;
 d.textContent=Math.floor(x/86400);h.textContent=pad(Math.floor(x%86400/3600));
 m.textContent=pad(Math.floor(x%3600/60));s.textContent=pad(Math.floor(x%60))}
if(t){tick();setInterval(tick,1000)}
const at=document.getElementById('attending'),gw=document.getElementById('gw');
at.onchange=()=>gw.style.display=at.value==='yes'?'':'none';
const lb=document.getElementById('lb');
document.querySelectorAll('.gallery img').forEach(i=>i.onclick=()=>{lb.firstElementChild.src=i.src;lb.classList.add('on')});
lb.onclick=()=>lb.classList.remove('on');
document.addEventListener('keydown',e=>{if(e.key==='Escape')lb.classList.remove('on')});
if('IntersectionObserver' in window){
 const io=new IntersectionObserver(es=>es.forEach(en=>{if(en.isIntersecting){en.target.classList.add('in');io.unobserve(en.target)}}),{threshold:.12});
 document.querySelectorAll('.reveal').forEach(el=>io.observe(el));
}else document.querySelectorAll('.reveal').forEach(el=>el.classList.add('in'));
</script>
</body>
</html>
