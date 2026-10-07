<?php
/**
 * head.php
 * Oczekuje ustawionych zmiennych: $pageTitle, $csrfToken (opcjonalnie).
 */
?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="robots" content="noindex, nofollow" />
  <?php if (!empty($csrfToken)): ?>
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
  <?php endif; ?>
  <title><?php echo htmlspecialchars($pageTitle ?? 'Panel administratora', ENT_QUOTES, 'UTF-8'); ?> — Panel Śląskiej Ligi Tenisa</title>
  <link rel="stylesheet" href="../styles.css" />
  <link rel="stylesheet" href="assets/admin.css" />
</head>
<body class="admin-body">
