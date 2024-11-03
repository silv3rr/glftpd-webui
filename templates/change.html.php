<!-- user management - change, included in main.html.php -->

<?php if (cfg::get('debug') > 9): ?>
  <br>DEBUG: change get_user=<?= $data->get_user() ?> check_user=<?= $data->check_user();  ?><br>
<?php endif ?>

<div class="bg-custom pb-3">
  <div class="col-auto">
    <div class="form-row align-items-center">
      <div class="col-2">
        <label for="select_user" class="col-form-label">User:</label>
      </div>
      <div class="col-6">
        <select id="select_user" name="select_user" onchange="this.form.submit()" class='form-control' style="font-weight: 600;">
          <option selected class='form-control selected'>
            <?php print (!empty($data->get_user())) ? $data->get_user() : "Select username..."; ?>
          </option>
          <?php option_user(); ?>
        </select>
      </div>
    </div>
  <p></p>
  <div class="form-row align-items-center">
    <div class="col-2">
      <label for="ip_add" class="col-form-label">IP masks:</label>
    </div>
    <?php if (!empty($data->get_user()) && $data->check_user()): ?>
      <div class="col-auto">
        <?php $masks = $data->get_mask(); if (!empty($masks)): ?>
        <input type="hidden" name="ipCmd" value="ip_del" />
        <div class="form-row align-items-center">
          <div class="col-auto">
            <textarea rows="2" id="ip_add" cols="35" name="ipCmd" class="form-control"><?= implode(" ", $masks) ?></textarea>
          </div>
          <div class="col-amt-4">
            <span class=" col-form-label-sm text-muted">
              <button type="button" id="ip_reset" onclick="document.getElementById('ip_add').value='';" value="Clear" class="btn btn-outline-secondary btn-sm" />
                <em class="fa-solid fa-eraser"></em>Clear
              </button>
            </span>
          </div>
        </div>
        <?php else: ?>
          <textarea rows="2" id="ip_add" cols="35" name="ipCmd" placeholder="myident@11.22.33.* *@example.org" class="form-control"><?= $masks ?></textarea>
        <?php endif ?>
        <label for="ip_add" class="col-form-label-sm text-muted ml-1">use space to separate masks</label>
      </div>
    <?php else: ?>
      <span class='col-form-label-sm text-muted'>&lt;user:none&gt;</span>
    <?php endif ?>
    </div>
    <p></p>
    <div class="form-row">
      <div class="col-2">
        <label for="passwd_change" class="col-form-label">Password:</label>
      </div>
      <div class="col-5">
        <input type="password" id="passwd_change" name="setPassCmd" placeholder="mynewpasswd" class="form-control">
      </div>
    </div>
    <p></p>
    <div class="form-row mb-2">
      <div class="col-2">
        <label for="add_user_group" class="col-form-label">Groups:</label>
      </div>
    <?php if (empty($data->get_user()) || !$data->check_user()): ?>
      <span class='col-form-label-sm text-muted'>&lt;user:none&gt;</span>
      </div>
    <?php else: ?>
      <?php $user_groups = $data->get_user_group(); if (!empty($user_groups)): ?>
      <?php if (count($user_groups['current']) > 0): ?>
        <div class="col-9 bg-white align-items-center border rounded text-break ml-2 mb-2 pt-2 pb-2">
        <?php foreach ($user_groups['current'] as $group => $gadmin): ?>
          <span class="badge badge-pill badge-secondary pt-0 pb-0">
          <?= $group ?>
          <?php if ($gadmin): ?>
            <em title="User is Group Admin" class='fa-solid fa-circle-user'></em>
          <?php endif ?>
          <button title="Remove group from user" type='submit' id='del_user_group' name='userGrpCmd[]' value='del_user_group|<?= $group ?>' class='btn btn-sm btn-txt text-dark align-text-center mb-1'>
            <em class='fa-solid fa-circle-xmark'></em>
          </button>
        </span>
      <?php endforeach ?>
      <?php $user_pgroups = $data->get_user_pgroup(); if (!empty($user_pgroups)): ?>
      <?php foreach ($user_pgroups as $pgroup): ?>
        <span title="Private Group" class="badge badge-pill badge-secondary  pt-0 pb-0"">
          *<?= $pgroup ?>
          <em class='fa-solid fa-shield-halved text-light'></em>
          <button title="Remove pgroup from user" type='submit' id='del_user_pgroup' name='userGrpCmd[]' value='del_user_pgroup|<?= $pgroup ?>' class='btn btn-sm btn-txt text-dark align-text-center mb-1'>
            <em class='fa-solid fa-circle-xmark'></em>
          </button>
      </span>
      <?php endforeach ?>
    <?php endif ?>
      </div>
    </div>
      <?php if (count($user_groups['current']) > 1): ?>
        <div class="form-row ml-1 pb-2">
          <div class="col-2"></div>
          <div class="col-amt-4">
            <span title="Check to remove all groups from user" class='border border-secondary rounded p-2'>
              <em class="fa-solid fa-eraser"></em>Remove <strong>all</strong> groups
              <input type='checkbox' id='del_user_group_all' name='userGrpCmd[]' value='del_user_group_all|<?= implode(PHP_EOL, array_keys($user_groups["current"])) ?>'/>
            </span>
          </div>
          </div>
        </div>
      <?php endif ?>
  <?php else: ?>
    <span class='col-form-label-sm text-muted ml-2'>&lt;No groups&gt;</span>
    </div>
  <?php endif ?>
    <p></p>
    <div class="form-row pb-2">
      <div class="col-2 ml-2"></div>
    <?php if (count($user_groups['available']) > 0): ?>
      <div class="col-auto">
        <select id="add_user_group" name="userGrpCmd[]" columns="5" class='form-control'>
          <option selected class='form-control selected'>Add user to group...</option>
          <?php foreach ($user_groups['available'] as $group): ?>
            <option value='add_user_group|<?= $group ?>'><?= $group ?></option>
          <?php endforeach ?>
        </select>
      </div>
    <?php endif ?>
    <?php if (count($user_groups['current']) > 0): ?>
      <div class="col-auto">
        <select id="user_toggle_gadmin" name="userGrpCmd[]" class='form-control'>
          <option selected class='form-control selected'>Group Admin...</option>
          <?php foreach ($user_groups['current'] as $group => $gadmin): ?>
            <?php if ($gadmin): ?>
              <option value='user_toggle_gadmin|<?= $group ?>'>-  <?= $group ?></option>
            <?php else: ?>
              <option value='user_toggle_gadmin|<?= $group ?>'>+ <?= $group ?></option>
            <?php endif ?>
          <?php endforeach ?>
        </select>
      </div>
    <?php else: ?>
      <?php if (!empty($_SESSION['pgroups'])): ?>
        &nbsp;
       <?php endif ?>
    <?php endif ?>
    <?php if (!empty($_SESSION['pgroups']) && count($_SESSION['pgroups']) > 0): ?>
      <div class="col-auto">
        <select id="add_user_group" name="userGrpCmd[]" class='form-control'>
          <option selected class='form-control selected'>Add user to privgroup...</option>
          <?php foreach ($_SESSION['pgroups'] as $pgroup => $desc): ?>
            <option value='add_user_pgroup|<?= $pgroup ?>'>*<?= $pgroup ?></option>
          <?php endforeach ?>
        </select>
        </div>
      </div>
    <?php else: ?>
        </div>
    <?php endif ?>
  <?php else: ?>
    </div>
  <?php endif ?>
<?php endif ?>

<?php if (empty($data->get_user()) || !$data->check_user()): ?>
  </div>
<?php endif ?>

<p></p>
