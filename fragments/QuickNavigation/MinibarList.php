<?php
/**
 * @var $this rex_fragment
 * @psalm-scope-this rex_fragment
 */
?>

<?php foreach($this->getVar('listItems', []) as $listItem): ?>
    <div class="rex-minibar-info-group">
        <div class="rex-minibar-info-piece">
            <?= $listItem ?>
        </div>
    </div>
<?php endforeach ?>