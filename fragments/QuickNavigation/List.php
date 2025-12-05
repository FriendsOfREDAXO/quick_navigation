<?php
/**
 * @var $this rex_fragment
 * @psalm-scope-this rex_fragment
 */
?>

<ul>
<?php foreach($this->getVar('listItems', []) as $listItem): ?>
    <?php if (str_contains($listItem, 'quick-navigation-section-header') || str_contains($listItem, 'quick-navigation-section-divider')): ?>
        <?= $listItem ?>
    <?php else: ?>
    <li>
        <div class="quick-navigation-item">
            <?= $listItem ?>
        </div>
    </li>
    <?php endif ?>
<?php endforeach ?>
</ul>