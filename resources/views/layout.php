<?php
/**
 * @var string $title
 * @var string|null $entry
 * @var string|null $activeNav
 * @var array|null $user
 * @var array $initialData
 * @var string $content
 * @var \Schedule\View\ViteManifest $vite
 */
?>
<!doctype html>
<html lang="en" class="dark">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - Schedule</title>
    <link rel="stylesheet" href="/layout.css">
<?php if ($entry !== null): ?>
    <?= $vite->tags($entry) ?>

<?php endif ?>
  </head>
  <body class="">
    <div class="app-shell">
      <nav class="site-nav">
        <span class="brand">Schedule</span>
<?php if ($user !== null): ?>
        <a href="/blocks" class="<?= $activeNav === 'blocks' ? 'is-active' : '' ?>">Games</a>
        <a href="/calendar" class="<?= $activeNav === 'calendar' ? 'is-active' : '' ?>">Calendar</a>
<?php if ($user['is_admin']): ?>
        <a href="/admin" class="admin-link <?= $activeNav === 'admin' ? 'is-active' : '' ?>">Admin</a>
<?php endif ?>
        <form method="post" action="/logout" class="logout-form">
          <button type="submit">Log out</button>
        </form>
<?php endif ?>
      </nav>

      <main class="content">
<?= $content ?>
      </main>
    </div>

<?php if ($entry !== null): ?>
    <script type="application/json" id="page-data"><?= json_encode(
        ['user' => $user, 'initialData' => $initialData],
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
    ) ?></script>
<?php endif ?>
  </body>
</html>
