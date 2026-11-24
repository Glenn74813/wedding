<?php
require 'config.php';
$s = settings();
$msg = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (csrf_ok() && password_verify($_POST['password'] ?? '', $s['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: admin.php'); exit;
    } else { $msg = 'Wrong password.'; }
}
$in = !empty($_SESSION['admin']);

if ($in && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'login') {
    if (!csrf_ok()) { die('Invalid session. Reload the page.'); }
    $a = $_POST['action'];
    if ($a === 'settings') {
        foreach (['bride','groom','date','venue','address','message','rsvp_deadline'] as $k)
            $s[$k] = trim($_POST[$k] ?? '');
        if (strlen($_POST['new_password'] ?? '') >= 6) $s['password_hash'] = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        save_settings($s); $msg = 'Settings saved.';
    } elseif ($a === 'upload') {
        $n = 0;
        foreach ($_FILES['photos']['tmp_name'] ?? [] as $i => $tmp) {
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK || $_FILES['photos']['size'][$i] > 8*1024*1024) continue;
            $info = @getimagesize($tmp);
            if (!in_array($info['mime'] ?? '', ['image/jpeg','image/png','image/webp'], true)) continue;
            $fh = fopen($tmp, 'rb');
            $st = db()->prepare('INSERT INTO photos(mime,data,pos) VALUES(?,?,(SELECT COALESCE(MAX(pos),0)+1 FROM photos))');
            $st->bindValue(1, $info['mime']); $st->bindParam(2, $fh, PDO::PARAM_LOB); $st->execute(); $n++;
        }
        $msg = "$n photo(s) uploaded (JPG/PNG/WEBP, max 8 MB each).";
    } elseif ($a === 'delphoto') {
        db()->prepare('DELETE FROM photos WHERE id=?')->execute([(int)$_POST['file']]); $msg = 'Photo removed.';
    } elseif ($a === 'cover') {
        db()->prepare('UPDATE photos SET pos=(SELECT COALESCE(MIN(pos),0)-1 FROM photos) WHERE id=?')->execute([(int)$_POST['file']]); $msg = 'Cover photo updated.';
    } elseif ($a === 'delrsvp') {
        db()->prepare('DELETE FROM rsvps WHERE id=?')->execute([$_POST['id']]); $msg = 'RSVP deleted.';
    }
    $_SESSION['flash'] = $msg;
    header('Location: admin.php'); exit;
}

$rsvps = db()->query("SELECT id,name,attending,guests,note,to_char(created,'YYYY-MM-DD HH24:MI') AS \"time\" FROM rsvps ORDER BY created")->fetchAll();
if ($in && isset($_GET['csv'])) {
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename=rsvps.csv');
    $o = fopen('php://output', 'w'); fputcsv($o, ['Name','Attending','Guests','Message','Submitted']);
    foreach ($rsvps as $r) fputcsv($o, [$r['name'],$r['attending'],$r['guests'],$r['note'],$r['time']]);
    exit;
}
$yes = array_filter($rsvps, fn($r) => $r['attending'] === 'yes');
$totalGuests = array_sum(array_column($yes, 'guests'));
$photos = db()->query('SELECT id FROM photos ORDER BY pos,id')->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin</title>
<style>
body{font-family:system-ui,sans-serif;background:#f3f1ec;color:#1b2b26;margin:0}
.w{max-width:960px;margin:0 auto;padding:1.5rem}
h1{font-weight:500}h2{margin-top:2.5rem;border-bottom:2px solid #c8a96a;padding-bottom:.4rem}
.card{background:#fff;padding:1.2rem;border-radius:6px;margin-top:1rem;box-shadow:0 1px 3px rgba(0,0,0,.08)}
label{display:block;margin:.7rem 0 .2rem;font-size:.9rem}
input,textarea{width:100%;padding:.6rem;border:1px solid #bbb;border-radius:4px;font:inherit;box-sizing:border-box}
button,.btn{background:#0e2620;color:#fff;border:0;padding:.6rem 1.1rem;border-radius:4px;cursor:pointer;text-decoration:none;font:inherit;display:inline-block}
button.del{background:#a33}button.sec{background:#7d6a3a}
.stats{display:flex;gap:1rem;flex-wrap:wrap}.stats div{flex:1;min-width:130px;background:#fff;padding:1rem;border-radius:6px;text-align:center}
.stats b{display:block;font-size:2rem;color:#7d6a3a}
table{width:100%;border-collapse:collapse;background:#fff}td,th{padding:.55rem;border-bottom:1px solid #eee;text-align:left;font-size:.92rem}
.photos{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.8rem}
.photos img{width:100%;height:120px;object-fit:cover;border-radius:4px}
.ok{background:#e4f2e6;padding:.7rem;border-radius:4px}.tbl{overflow-x:auto}
</style></head><body><div class="w">
<?php if (!$in): ?>
  <h1>Admin login</h1>
  <?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>
  <form method="post" class="card"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="login">
    <label for="p">Password</label><input id="p" type="password" name="password" required autofocus><br><br><button>Log in</button></form>
<?php else: ?>
  <p style="float:right"><a href="index.php" target="_blank">View site</a> · <a href="?logout=1">Log out</a></p>
  <h1>Wedding dashboard</h1>
  <?php if ($msg): ?><p class="ok"><?= e($msg) ?></p><?php endif; ?>

  <div class="stats">
    <div><b><?= count($yes) ?></b>Accepted</div>
    <div><b><?= $totalGuests ?></b>Total guests</div>
    <div><b><?= count($rsvps) - count($yes) ?></b>Declined</div>
  </div>

  <h2>Guest responses</h2>
  <div class="tbl"><table><tr><th>Name</th><th>Attending</th><th>Guests</th><th>Message</th><th>Date</th><th></th></tr>
  <?php foreach (array_reverse($rsvps) as $r): ?>
    <tr><td><?= e($r['name']) ?></td><td><?= $r['attending'] === 'yes' ? 'Yes' : 'No' ?></td><td><?= (int)$r['guests'] ?></td>
    <td><?= e($r['note']) ?></td><td><?= e($r['time']) ?></td>
    <td><form method="post" onsubmit="return confirm('Delete this RSVP?')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="delrsvp"><input type="hidden" name="id" value="<?= e($r['id']) ?>"><button class="del">Delete</button></form></td></tr>
  <?php endforeach; if (!$rsvps): ?><tr><td colspan="6">No responses yet.</td></tr><?php endif; ?>
  </table></div>
  <p><a class="btn" href="?csv=1">Download CSV</a></p>

  <h2>Wedding details</h2>
  <form method="post" class="card"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="settings">
    <label>Bride’s name</label><input name="bride" value="<?= e($s['bride']) ?>">
    <label>Groom’s name</label><input name="groom" value="<?= e($s['groom']) ?>">
    <label>Wedding date and time</label><input type="datetime-local" name="date" value="<?= e($s['date']) ?>">
    <label>Venue</label><input name="venue" value="<?= e($s['venue']) ?>">
    <label>Address</label><input name="address" value="<?= e($s['address']) ?>">
    <label>Invitation message</label><textarea name="message" rows="3"><?= e($s['message']) ?></textarea>
    <label>RSVP deadline</label><input name="rsvp_deadline" value="<?= e($s['rsvp_deadline']) ?>">
    <label>New admin password (leave blank to keep; min 6 characters)</label><input type="password" name="new_password" autocomplete="new-password"><br><br>
    <button>Save changes</button></form>

  <h2>Photos</h2>
  <form method="post" enctype="multipart/form-data" class="card"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="upload">
    <label>Choose photos (JPG, PNG, WEBP; max 8 MB each)</label><input type="file" name="photos[]" accept="image/*" multiple required><br><br><button>Upload photos</button></form>
  <div class="photos card">
  <?php foreach ($photos as $i => $p): ?>
    <div><img src="photo.php?id=<?= (int)$p ?>" alt=""><?= $i === 0 ? '<small>Cover photo</small>' : '' ?>
    <form method="post" style="margin-top:.3rem"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="file" value="<?= e($p) ?>">
      <?php if ($i): ?><button class="sec" name="action" value="cover">Set cover</button> <?php endif; ?><button class="del" name="action" value="delphoto" onclick="return confirm('Remove this photo?')">Remove</button></form></div>
  <?php endforeach; if (!$photos) echo 'No photos yet.'; ?>
  </div>
<?php endif; ?>
</div></body></html>
