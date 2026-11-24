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
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,500;1,300;1,500&family=Jost:wght@300;400&display=swap" rel="stylesheet">
<style>
:root{--ink:#0e2620;--deep:#091a16;--gold:#c8a96a;--gold-soft:#e3d2a6;--ivory:#f4efe4;--mute:#b9c2bb}
*{box-sizing:border-box;margin:0}
html{scroll-behavior:smooth}
body{background:var(--deep);color:var(--ivory);font-family:'Jost',sans-serif;font-weight:300;line-height:1.7}
h1,h2,h3{font-family:'Cormorant Garamond',serif;font-weight:300}
.hero{min-height:100vh;display:grid;place-items:center;text-align:center;padding:2rem;position:relative;
 background:linear-gradient(rgba(9,26,22,.55),rgba(9,26,22,.9)),<?= $hero ? "url('" . e($hero) . "') center/cover" : 'radial-gradient(circle at 50% 30%,#17413a,#091a16 70%)' ?>}
.frame{border:1px solid var(--gold);padding:4rem 3rem;max-width:760px;position:relative;outline:1px solid rgba(200,169,106,.35);outline-offset:10px;background:rgba(9,26,22,.35);backdrop-filter:blur(3px)}
.hero .msg{max-width:460px;margin:0 auto 1.6rem;color:var(--mute);font-size:.95rem;letter-spacing:.04em}
.names{font-size:clamp(3rem,9vw,6.4rem);line-height:1.05;color:var(--gold-soft);font-style:italic}
.names span{display:block;font-size:.4em;font-style:normal;color:var(--gold);margin:.3em 0}
.date{margin-top:2rem;font-family:'Cormorant Garamond',serif;font-size:1.6rem;letter-spacing:.18em}
.venue{color:var(--mute)}
section{padding:6rem 1.5rem;max-width:980px;margin:0 auto;text-align:center}
h2{font-size:clamp(2rem,5vw,3rem);color:var(--gold-soft);margin-bottom:1.5rem;font-style:italic}
.rule{width:60px;height:1px;background:var(--gold);margin:0 auto 2rem}
.count{display:flex;justify-content:center;gap:clamp(1rem,5vw,3.5rem);flex-wrap:wrap}
.count div{min-width:80px}
.count b{display:block;font-family:'Cormorant Garamond',serif;font-weight:300;font-size:clamp(2.6rem,7vw,4.4rem);color:var(--gold)}
.count small{color:var(--mute);letter-spacing:.15em}
.gallery{columns:3 240px;column-gap:14px;margin-top:1rem}
.gallery img{width:100%;margin-bottom:14px;display:block;border:1px solid rgba(200,169,106,.4);cursor:zoom-in}
.alt{background:var(--ink);max-width:none}
.alt>div{max-width:560px;margin:0 auto}
form{text-align:left;display:grid;gap:1.1rem}
label{font-size:.85rem;color:var(--gold-soft);letter-spacing:.05em}
input,select,textarea{width:100%;background:transparent;border:0;border-bottom:1px solid var(--gold);color:var(--ivory);padding:.6rem 0;font:inherit;font-size:1rem;border-radius:0}
select option{color:#000}
input:focus,select:focus,textarea:focus{outline:2px solid var(--gold);outline-offset:4px}
button{margin-top:.8rem;background:var(--gold);color:var(--deep);border:0;padding:1rem;font:inherit;font-weight:400;letter-spacing:.12em;cursor:pointer}
button:hover{background:var(--gold-soft)}
.note{padding:1rem;border:1px solid var(--gold);margin-bottom:1.5rem;color:var(--gold-soft)}
.hp{position:absolute;left:-9999px}
footer{padding:3rem 1rem;text-align:center;color:var(--mute);font-size:.85rem}
#lb{position:fixed;inset:0;background:rgba(0,0,0,.92);display:none;place-items:center;z-index:9;cursor:zoom-out}
#lb img{max-width:92vw;max-height:92vh}
@media(max-width:600px){.frame{padding:2.5rem 1.2rem}}
@media(prefers-reduced-motion:no-preference){.frame{animation:rise 1.4s ease both}@keyframes rise{from{opacity:0;transform:translateY(18px)}}}
</style>
</head>
<body>
<header class="hero">
  <div class="frame">
    <p class="msg"><?= e($s['message']) ?></p>
    <h1 class="names"><?= e($s['bride']) ?><span>and</span><?= e($s['groom']) ?></h1>
    <p class="date"><?= $ts ? e(date('F j, Y', $ts)) : '' ?></p>
    <p class="venue"><?= e($s['venue']) ?></p>
  </div>
</header>

<section>
  <h2>Until we say “I do”</h2><div class="rule"></div>
  <div class="count" id="count" data-t="<?= (int)$ts ?>">
    <div><b id="d">0</b><small>DAYS</small></div><div><b id="h">0</b><small>HOURS</small></div>
    <div><b id="m">0</b><small>MINUTES</small></div><div><b id="s">0</b><small>SECONDS</small></div>
  </div>
  <p style="margin-top:3rem;color:var(--mute)"><?= $ts ? e(date('l, F j, Y \a\t g:i A', $ts)) : '' ?><br><?= e($s['venue']) ?><br><?= e($s['address']) ?></p>
</section>

<?php if ($photos): ?>
<section>
  <h2>Our story in pictures</h2><div class="rule"></div>
  <div class="gallery">
    <?php foreach ($photos as $p): ?><img src="photo.php?id=<?= (int)$p ?>" alt="<?= e($s['bride']) ?> and <?= e($s['groom']) ?>" loading="lazy"><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="alt" id="rsvp"><div>
  <h2>Kindly reply</h2><div class="rule"></div>
  <p style="color:var(--mute);margin-bottom:2rem">Please respond by <?= e($s['rsvp_deadline']) ?>.</p>
  <?php if ($status === 'ok_yes'): ?><p class="note">Thank you! We can’t wait to celebrate with you.</p>
  <?php elseif ($status === 'ok_no'): ?><p class="note">Thank you for letting us know. You will be missed.</p>
  <?php elseif ($status === 'error'): ?><p class="note">Please enter your name.</p><?php endif; ?>
  <form method="post" action="#rsvp">
    <input type="hidden" name="csrf" value="<?= csrf() ?>">
    <input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
    <div><label for="name">Full name</label><input id="name" name="name" required maxlength="80"></div>
    <div><label for="attending">Will you attend?</label>
      <select id="attending" name="attending"><option value="yes">Joyfully accept</option><option value="no">Regretfully decline</option></select></div>
    <div id="gw"><label for="guests">Number of guests (including you)</label><input id="guests" type="number" name="guests" min="1" max="10" value="1"></div>
    <div><label for="note">Message for the couple (optional)</label><textarea id="note" name="note" rows="3" maxlength="400"></textarea></div>
    <button type="submit">SEND RSVP</button>
  </form>
</div></section>

<footer><?= e($s['bride']) ?> &amp; <?= e($s['groom']) ?> · <?= $ts ? e(date('F j, Y', $ts)) : '' ?></footer>
<div id="lb"><img alt=""></div>
<script>
const c=document.getElementById('count'),t=+c.dataset.t*1000;
function tick(){let x=Math.max(0,t-Date.now())/1000;
 d.textContent=Math.floor(x/86400);h.textContent=Math.floor(x%86400/3600);
 m.textContent=Math.floor(x%3600/60);s.textContent=Math.floor(x%60)}
if(t){tick();setInterval(tick,1000)}
const at=document.getElementById('attending'),gw=document.getElementById('gw');
at.onchange=()=>gw.style.display=at.value==='yes'?'':'none';
const lb=document.getElementById('lb');
document.querySelectorAll('.gallery img').forEach(i=>i.onclick=()=>{lb.firstElementChild.src=i.src;lb.style.display='grid'});
lb.onclick=()=>lb.style.display='none';
</script>
</body>
</html>
