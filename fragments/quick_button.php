<div class="btn-group">
     <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
     <i class="<?= (isset($this->icon) ? 'fa fa-star-o' : 'fa fa-bullseye')?>" aria-hidden="true"></i>
     <span class="caret"></span>
     </button>
     <ul class="quickfiles quicknavi dropdown-menu dropdown-menu-right">
     <?php foreach ($this->items as $item) {
     echo $item;
     }?>
     </ul>
</div>
