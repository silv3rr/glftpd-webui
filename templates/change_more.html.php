<!--glftpd-webui::user-management::change:more, included in main.html.php -->

<div class="<?= ((cfg::get('show_more_opts')) ? 'show' : 'collapse') ?>" id="colMoreOpts">
  <div class="card card-body bg-custom">
    <div class="form-row align-items-center">
      <div class="col-1">
        <label for="flag_add" class="col-form-label">Flags:</label>
      </div>
      <?php if (!empty($data->get_user()) && $data->check_user() && isset($_SESSION['userfile'])): ?>
      <input type="hidden" name="flagCmd" value="flag_del" />
      <?php $userfile_flags = ((!empty($_SESSION['userfile'] && !empty($_SESSION['userfile']['FLAGS']))) ? str_split($_SESSION['userfile']['FLAGS']) : []); ?> 
      <div class="col-4">
        <select id="flag_add" name="flagCmd[]" multiple size="5" class="form-control">
          <?php foreach (flags_list() as $flag => $name): ?>
            <option <?= (in_array($flag, $userfile_flags) ? "selected" : "") ?> value='<?= "flag_add|$flag" ?>'><?= "$flag ({$name})" ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-amt-4">
        <span class=" col-form-label-sm text-muted">
          <button type="button" id="ip_reset" onclick="document.getElementById('flag_add').value='';" value="Clear" class="btn btn-outline-secondary btn-sm" />
            <em class="fa-solid fa-eraser"></em>Clear
          </button>
        </span>
      </div>
    </div>
    <p></p>
    <?php else: ?>
      <span class='col-form-label-sm text-muted'>&lt;user:none&gt;</span>
      </div>
    <?php endif ?>
  <p></p>
  <div class="form-row">
    <div class="col-1">
      <label for="logins" class="col-form-label">Logins:</label>
    </div>
    <div class="col-auto">
      <input type="text" id="logins" name="loginsCmd" <?= (!empty($data->get_user()) && $data->check_user() && !empty($_SESSION['logins']) ? 'value="' . $_SESSION['logins'] . '"' : 'placeholder="0"') ?> size="6" class="form-control">
    </div>
  </div>
  <p></p>
  <div class="form-row">
    <div class="col-1">
      <label for="ratio" class="col-form-label">Ratio:</label>
    </div>
    <div class="col-auto">
      <input type="text" id="ratio" name="ratioCmd" <?= (!empty($data->get_user()) && $data->check_user() && !empty($_SESSION['ratio']) ? 'value="' . $_SESSION['ratio'] . '"' : 'placeholder="0"') ?> size="22" class="form-control">
    </div>
  </div>
  <p></p>
  <div class="form-row">
    <div class="col-1">
      <label for="credits" class="col-form-label">Credits:</label>
    </div>
    <div class="col-auto">
      <div class="form-inline">
        <input type="text" id="credits" name="credsCmd" <?= (!empty($data->get_user()) && $data->check_user() && !empty($_SESSION['credits']) ? 'value="' . $_SESSION['credits'] . '"' : 'placeholder="0"') ?> size="22" class="form-control">
        <span class="ml-2">
          (default section: <strong><?= (!empty($data->get_user()) && $data->check_user() && !empty($_SESSION['credits']) && $_SESSION['credits'] > 0) ? format_bytes((int)$_SESSION['credits']) : "0b" ?></strong>)
        </span>
      </div>
    </div>
  </div>
  <p></p>
  <div class="form-row">
    <div class="col-1">
      <label for="tagline_change" class="col-form-label">Tagline:</label>
    </div>
    <div class="col-auto">
      <input type="text" id="tagline_change" name="tagCmd" <?= (!empty($data->get_user()) && $data->check_user() && !empty($_SESSION['tagline']) ? 'value="' . $_SESSION['tagline'] . '"' : 'placeholder="mytagline"') ?> size="30" class="form-control">
    </div>
  </div>
  <p></p>
  <div class="form-row align-items-center">
  <?php if ($data->check_user()): ?>
    <div class="col-1">
      <label for='reset_user_stats' class='col-form-label'>Stats:</label>
    </div>
    <div class="col-auto">
      <button type="submit" name="gltoolCmd" value="show_user_stats" class="btn btn-outline-primary mr-2 mb-1">
        <em class='fa-solid fa-chart-simple'></em> Show
      </button>
      <span class='border border-warning rounded p-2'>
        Reset
        <input type='checkbox' id='reset_user_stats' name='userCmd' value='reset_user_stats'>
      </span>
    </div>
  <?php endif ?>
  </div>
  <p></p>
  <div class="form-row align-items-center">
    <?php if (!empty($data->get_user()) && $data->check_user()): ?>
      <div class="col-auto">
        <label for='user_del' class='col-form-label'>
          Deluser <strong><?= $_SESSION['postdata']['select_user'] ?></strong>:
        </label>
      </div>
      <div class="col-auto">
        <span class='border border-danger rounded p-2 deluser'>
          <em class='fa-solid fa-triangle-exclamation text-danger'></em> Confirm
          <input type='checkbox' id='user_del' name='userCmd' value='user_del'>
        </span>
      </div>
    <?php endif ?>
    </div>
</div>
<p></p>
</div>

<div class="form-row mb-4">
  <div class="col-auto">
    <a class='btn btn-link color-custom' data-toggle="collapse" href="#colMoreOpts" role="button" aria-expanded="false" aria-controls="colMoreOpts">
      <em class="border border-primary rounded ml-3 p-2 fa-solid fa-arrows-up-down"></em>
      Show/hide more options
    </a>
  </div>
  <div class="col-5"></div>
  <button type='button' id='cancel' name='cancelBtn' onclick='window.location = "?user="' class='btn btn-secondary ml-4 <?= ($data->check_user() ? "" : "disabled") ?>'>
    <em class="fa-solid fa-circle-xmark"></em> Cancel
  </button>
  <button type='submit' id='apply' name='applyBtn' class='btn btn-primary btn ml-3 <?= ($data->check_user() ? "" : "disabled") ?>'>
    <em class="fa-solid fa-circle-check"></em> Apply
  </button>
</div>


</div>

