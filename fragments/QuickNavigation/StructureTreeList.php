<?php
/**
 * @var $this rex_fragment
 * @psalm-scope-this rex_fragment
 */

$listItems = $this->getVar('listItems', []);
$cssClass = $this->getVar('cssClass', '');
?>
<?php if (!empty($listItems)): ?>
<ul class="structure-tree-list <?= $cssClass ?>">
<?php foreach($listItems as $listItem): ?>
    <li><?= $listItem ?></li>
<?php endforeach ?>
</ul>
<?php endif; ?>
