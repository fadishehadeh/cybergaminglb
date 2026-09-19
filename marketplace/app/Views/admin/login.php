<?php
$siteName = (string) setting('site_name', 'CyberGaming Lebanon');
$err = flash('error');
$ok  = flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Sign in | Admin | <?= e($siteName) ?></title>
<link rel="icon" href="<?= e(asset('img/logo.jpg')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="login">
<main class="login-card">
    <img class="login-logo" src="<?= e(asset('img/logo-wide.png')) ?>" alt="<?= e($siteName) ?>">
    <h1>Admin sign in</h1>
    <?php if ($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error" role="alert"><?= e($err) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('/admin/login')) ?>" autocomplete="on">
        <?= csrf_field() ?>
        <div class="field">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="username">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Sign in</button>
    </form>
    <p class="login-foot">Staff only. Repeated failed attempts lock the account for 15 minutes.</p>
</main>
</body>
</html>
