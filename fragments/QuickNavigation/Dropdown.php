<?php
/**
 * @var $this rex_fragment
 * @psalm-scope-this rex_fragment
 *
 * Vars
 *  header - string
 *  label - string
 *  icon - string
 *  listItems - array
 *  listType - enum -> list, tree
 */

$listType = $this->getVar('listType', 'list');
if (!in_array($listType, ['list', 'tree'])) {
    $listType = 'list';
}
?>
<div class="btn-group open">
    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="<?= $this->getVar('icon', 'fa fa-bullseye') ?>" aria-hidden="true"></i>
        <?php if ($this->getVar('label', false)): ?>
            <span class="quick-navigation-button-label"><?= rex_escape($this->getVar('label')) ?></span>
        <?php endif ?>
        <span class="caret"></span>
    </button>

    <div class="quick-navigation-menu dropdown-menu dropdown-menu-right" role="menu">
        <?php if ($this->getVar('header', false)): ?>
            <div class="quick-navigation-menu-header">
                <?= $this->getVar('header') ?>
            </div>
        <?php endif ?>
        <div class="quick-navigation-menu-body quick-navigation-menu-list-type-<?= $listType ?>">
            <?php $this->subfragment('QuickNavigation/List.php') ?>
        </div>
    </div>
</div>
