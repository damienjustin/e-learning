<?php
/**
 * Self-contained block builder widget: loads its own JS and initializes
 * itself directly by id, independent of whether layout.php was updated
 * to include builder.js (deployments may lag behind on static assets).
 * Expects: $builderName (form field name), $builderBlocksJson (JSON string).
 */
$builderId = 'bb_' . preg_replace('/[^a-z0-9_]/', '', strtolower($builderName));
?>
<div class="block-builder" data-builder data-target="#<?= Security::e($builderId) ?>_input" id="<?= Security::e($builderId) ?>">
    <div class="block-toolbar">
        <div class="block-toolbar-group">
            <button type="button" class="btn-icon" data-action="undo" title="Annuler (Ctrl+Z)">↶</button>
            <button type="button" class="btn-icon" data-action="redo" title="Rétablir (Ctrl+Maj+Z)">↷</button>
        </div>
        <span class="block-count"></span>
        <div class="block-toolbar-group block-view-toggle">
            <button type="button" class="btn-secondary btn-small is-active" data-view="split">Côte à côte</button>
            <button type="button" class="btn-secondary btn-small" data-view="editor">Éditeur</button>
            <button type="button" class="btn-secondary btn-small" data-view="preview">Aperçu</button>
        </div>
    </div>
    <div class="block-builder-layout">
        <div class="block-builder-editor">
            <div class="block-list"></div>
            <div class="block-add-menu"></div>
        </div>
        <div class="block-builder-preview">
            <div class="block-preview-label">Aperçu</div>
            <div class="block-preview prose"></div>
        </div>
    </div>
    <input type="hidden" id="<?= Security::e($builderId) ?>_input" name="<?= Security::e($builderName) ?>" value="<?= Security::e($builderBlocksJson) ?>">
</div>
<script src="/admin/assets/js/builder.js"></script>
<script>
(function () {
    var root = document.getElementById(<?= json_encode($builderId) ?>);
    if (!root) { return; }
    if (window.BlockBuilderLib) {
        window.BlockBuilderLib.init(root);
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            if (window.BlockBuilderLib) { window.BlockBuilderLib.init(root); }
        });
    }
})();
</script>
