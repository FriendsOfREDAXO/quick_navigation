<div class="btn-group">
     <?= (isset($this->prepend) ? $this->prepend : '')?>
     <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
     <i class="<?= (isset($this->icon) ? $this->icon : 'fa fa-bullseye')?>" aria-hidden="true"></i>
     <span class="caret"></span>
     </button>
     <ul class="quickfiles quicknavi dropdown-menu dropdown-menu-right">
     <?= (isset($this->link) ? $this->link : '')?>
     <?php foreach ($this->items as $item) {
     echo $item;
     }?>
     </ul>
</div>


