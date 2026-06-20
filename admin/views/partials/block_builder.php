<?php
/**
 * Expects: $builderName (form field name), $builderBlocksJson (JSON string).
 */
?>
<div class="block-builder" data-builder data-target="#<?= Security::e($builderName) ?>_input">
    <div class="block-list"></div>
    <div class="block-add-menu"></div>
    <input type="hidden" id="<?= Security::e($builderName) ?>_input" name="<?= Security::e($builderName) ?>" value="<?= Security::e($builderBlocksJson) ?>">
</div>
