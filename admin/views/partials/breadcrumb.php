<?php
/**
 * Shared breadcrumb for course/module/lesson/quiz edit pages.
 * Expects: $crumbs = [['label' => ..., 'url' => ... or null]]
 */
?>
<nav class="admin-breadcrumb">
    <?php foreach ($crumbs as $i => $crumb): ?>
        <?php if ($i > 0): ?><span class="admin-breadcrumb-sep">&rsaquo;</span><?php endif; ?>
        <?php if (!empty($crumb['url']) && $i < count($crumbs) - 1): ?>
            <a href="<?= $crumb['url'] ?>"><?= Security::e($crumb['label']) ?></a>
        <?php else: ?>
            <span><?= Security::e($crumb['label']) ?></span>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
