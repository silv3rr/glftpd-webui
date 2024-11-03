<!-- user management, included in main.html.php -->

<div class="col-auto">
  <?php if (!empty($_SESSION['users_groups'])): ?>
    <?php if (count($_SESSION['users_groups']) > cfg::get('max_items')): ?>
      <div class="form-row align-items-center border rounded p-2">
        <div class="col-auto">
          <a class="btn btn-link color-custom" data-toggle="collapse" href="#colUsersGroups" role="button" aria-expanded="false" aria-controls="colUsersGroups">
            <em class="border border-primary rounded p-2 fa-solid fa-arrows-up-down"></em>
            Show/hide <strong><?= count($_SESSION['users_groups']) ?></strong> users...
          </a>
        </div>
        <div class="col-auto">
        <span class=" col-form-label-sm text-muted">
          <button type="submit" name="sortList" value="sort_users_groups|a-z" class="btn btn-outline-secondary btn-sm"><em class="fa-solid fa-arrow-down-a-z"></em></button>
        </span>
      </div>
      <div class="col-auto">
        <span class=" col-form-label-sm text-muted">
          <button type="submit" name="sortList" value="sort_users_groups|z-a" class="btn btn-outline-secondary btn-sm"><em class="fa-solid fa-arrow-up-a-z"></em></button>
        </span>
      </div>
      <div class="col-auto">
        <span class=" col-form-label-sm text-muted">
          <button type="submit" name="sortList" value="sort_users_groups|group" class="btn btn-outline-secondary btn-sm"><em class="fa-solid fa-people-group"></em></button>
        </span>
      </div>
    </div>
    <div class="<?= (isset($_SESSION['display_sort']['users_groups']) ? "show" : "collapse") ?>" id="colUsersGroups">
      <div class="card card-body">
      <?php endif ?>
        <p class="text-muted">
          Click on a username to edit in <strong>Change</strong> form below
        </p>
       <?php foreach ($_SESSION['users_groups'] as $user => $group): ?>
        <div>
          <span class="btn-txt">
            <button type='submit' id='user' name='set_user' value='<?= $user ?>' class='btn btn-txt btn-sm'><strong><?= $user ?></strong><?= (!empty($group) ? "/{$group}" : "") ?></button>
          </span>
        </div>
        <?php endforeach ?>
    <?php else: ?>
      <span class='col-form-label-sm text-muted'>&lt;No users found&gt;</span>
    <?php endif ?>
    <?php if (count($_SESSION['users_groups']) > cfg::get('max_items')): ?>
      </div>
      </div>
    <?php endif ?>
    <p></p>
    <div class="form-row mb-3">
      <div class="col-5">
        <label for="new_user_name">Add user:</label>
        <input type="text" id="new_user_name" name="user_name" placeholder="myusername" class="form-control">
      </div>
    <div class="col-6 ml-1">
      <label for="new_user_password">Password:</label>
      <input type="text" id="new_user_password" name="user_password" placeholder="secr3t" class="form-control">
    </div>
  </div>
  <div class="form-row">
    <div class="col-5">
      <label for="new_user_group">Group <span class="col-form-label-sm text-muted">(optional)</span></label>
      <select id="new_user_group" name="user_group" class='form-control'>
        <option selected class='form-control selected'>Select group...</option>
        <?php if (!empty($_SESSION['groups'])): ?>
          <?php foreach ($_SESSION['groups'] as $group => $desc): ?>
          <option value='<?= $group ?>'><?= $group ?></option>
          <?php endforeach ?>
        <?php endif ?>
      </select>
    </div>
  <div class="col-6">
    <label for="new_user_ip">IP masks <span class="col-form-label-sm text-muted">(optional)</span></label>
    <input type="text" id="new_user_ip" name="user_ip" placeholder="*@1.2.3.*" class="form-control">
  </div>
  <div class="col-auto ml-2">
    <label for="new_user_gadmin" class="col-form-label mr-0"><em class='fa-solid fa-circle-user'></em> gadmin</label><input type="checkbox" id="new_user_gadmin" name="user_gadmin" class="form-check-sm">
  </div>
  <div class="col-8"></div>
  <div class="col-auto"></div>
    <button type="submit" id="add_user" name="userCmd" value="user_add" class="btn btn-primary mt-3">
      <em class='fa-solid fa-circle-plus'></em> Add
    </button>
  </div>
</div>
