<!-- groups.html.php: User Management, part of main.html.php -->

<div class="col-auto">
  <p></p>
  <?php if (!empty($_SESSION['groups'])): ?>
    <?php if (count($_SESSION['groups']) > cfg::get('max_items')): ?>
  <div class="form-row align-items-center border rounded p-2">
    <div class="col-auto">
      <a class="btn btn-link color-custom" data-toggle="collapse" href="#colGroups" role="button" aria-expanded="false" aria-controls="colGroups">
        <em class="border border-primary rounded p-1 fa-solid fa-arrows-up-down"></em>
        Show/hide <strong><?= count($_SESSION['groups']) ?></strong> groups...
      </a>
    </div>
  <div class="col-auto">
    <span class=" col-form-label-sm text-muted">
      <button type="submit" name="grpCmd" value="sort_groups|a-z" class="btn btn-outline-secondary btn-sm"><em class="fa-solid fa-arrow-down-a-z"></em></button>
    </span>
  </div>
  <div class="col-auto">
    <span class=" col-form-label-sm text-muted">
      <button type="submit" name="grpCmd" value="sort_groups|z-a" class="btn btn-outline-secondary btn-sm"><em class="fa-solid fa-arrow-up-a-z"></em></button>
    </span>
  </div>
  </div>
  <div class="<?= (isset($_SESSION['display_sort']['groups']) ? "show" : "collapse") ?>" id="colGroups">
    <div class="card card-body">
      <?php endif ?>
      <?php foreach ($_SESSION['groups'] as $group => $desc): ?>
      <span title="<?= (!empty($desc) ? "$desc" : "")?>" class="mr-2"><?= $group ?><button type='submit' id='group_del' name='grpCmd' value='group_del|<?= $group ?>' class='btn btn-txt btn-outline-info color-custom align-text-bottom'><em class='fa-solid fa-circle-xmark' style='padding-left:1px;'></em></button></span>
      <?php endforeach ?>

  <?php if (count($_SESSION['groups']) > cfg::get('max_items')): ?>
        </div>
    </div>
  <?php endif ?>

  <?php else: ?>
    <span class='col-form-label-sm text-muted pl-20'>&lt;No groups found&gt;</span>
  <?php endif ?>
  <p></p>
  <div class="form-row align-items-center">
    <div class="col-auto">
      <label for="new_group">Add group:</label>
    </div>
    <div class="col-5">
      <input type="text" id="new_group" name="group_add" placeholder="mygroup" class="form-control">
    </div>
    <div class="col-auto ml-2">
      <button type="submit" id="add_group" name="grpCmd" value="group_add" class="btn btn-primary"><em class='fa-solid fa-circle-plus'></em> Add</button>
    </div>
  </div>
</div>
