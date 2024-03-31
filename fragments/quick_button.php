<div class="btn-group">
     <?= $this->prepend ?? ''?>
     <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
     <i class="<?= $this->icon ?? 'fa fa-bullseye'?>" aria-hidden="true"></i>
     <span class="caret"></span>
     </button>
     <ul class="quicknavi-items quicknavi dropdown-menu dropdown-menu-right">
     <?= $this->link ?? ''?>
     <?php
    if (isset($this->items) && is_array($this->items)) {
        foreach ($this->items as $item) {
            echo $item;
        }
    }?>
     </ul>
</div>
