<?php
/**
 * @var rex_fragment $this
 * @psalm-scope-this rex_fragment
 */

// Variables
$label = $this->getVar('label', '');
$icon = $this->getVar('icon', '');
$buttonClass = $this->getVar('buttonClass', 'btn btn-default');
$buttonId = $this->getVar('buttonId', '');
$treeItems = $this->getVar('treeItems', []);
$placeholder = $this->getVar('placeholder', 'Search...');

?>
<!-- Structure Tree Button -->
<div class="btn-group">
    <button class="<?= $buttonClass ?>" 
            type="button" 
            id="<?= $buttonId ?>"
            data-target="#structure-tree-sidebar"
            data-quick-navigation-toggle="tooltip" 
            title="<?= rex_escape($label) ?>"
            aria-label="<?= rex_escape($label) ?>">
        <i class="<?= $icon ?>" aria-hidden="true"></i>
        <span class="sr-only quick-navigation-button-label"><?= rex_escape($label) ?></span>
    </button>
</div>

<!-- Off-Canvas Sidebar (initially hidden) -->
<div id="structure-tree-sidebar" class="structure-tree-sidebar" style="display: none;">
    <div class="structure-tree-content">
        <div class="structure-tree-header">
            <h4><i class="<?= $icon ?>"></i> <?= rex_escape($label) ?></h4>
            <div class="structure-tree-header-buttons">
                <button class="btn btn-sm btn-default structure-tree-expand-toggle" type="button" title="<?= rex_i18n::msg('quick_navigation_expand_toggle', 'Alle auf-/zuklappen') ?>">
                    <i class="rex-icon fa-expand-arrows-alt"></i>
                </button>
                <button type="button" class="structure-tree-close" aria-label="<?= rex_i18n::msg('close', 'Schließen') ?>">
                    <i class="rex-icon fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="structure-tree-search">
            <div class="input-group input-group-sm">
                <span class="input-group-addon">
                    <i class="rex-icon rex-icon-search"></i>
                </span>
                <input type="text" 
                       class="form-control structure-tree-search-input" 
                       placeholder="<?= rex_escape($placeholder) ?>"
                       autocomplete="off">
                <span class="input-group-btn">
                    <button class="btn btn-default structure-tree-clear-search" type="button" title="<?= rex_i18n::msg('search_clear', 'Löschen') ?>">
                        <i class="rex-icon fa-times"></i>
                    </button>
                </span>
            </div>
        </div>
        
        <div class="structure-tree-body">
            <?php if (!empty($treeItems)): ?>
                <ul class="structure-tree-list">
                    <?php foreach ($treeItems as $item): ?>
                        <li><?= $item ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="structure-tree-empty">
                    <p><?= rex_i18n::msg('quick_navigation_no_results') ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
