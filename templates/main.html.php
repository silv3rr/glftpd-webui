<!DOCTYPE html>
<html lang="en">
<head>
  <title>GLFTPD:WEBUI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-Equiv="Cache-Control" Content="no-cache" />
  <meta http-Equiv="Pragma" Content="no-cache" />
  <meta http-Equiv="Expires" Content="0" />
  <link rel="stylesheet" href="lib/bootstrap-4.6.2-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/spy.css">
  <link rel="stylesheet" href="assets/css/dark.css">
  <link rel="stylesheet" href="lib/fontawesome-free-6.5.1-web/css/all.min.css"> 
  <script type="text/javascript" src="lib/jquery/jquery-3.6.0.min.js"></script>
  <script type="text/javascript" src="lib/bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js"></script>
  <script type="text/javascript" src="assets/js/modal_func.js"></script>
</head>
<body>

<div class="title"><?= cfg::get('title') ?>
</div>
<div class="status">
  &nbsp;
  MODE: <span id="mode"><?= cfg::get('mode') ?></span><hr class="vsep"/>
  STATUS:
  <?php foreach ($_SESSION['status'] as $service => $state): ?>
    <div id="<?= $state ?>" style="display:<?= (preg_match('/(up|down|open)/', $state) ? 'inline-block' : 'none') ?>">
      <?= $service ?>:<strong><?= strtoupper($state) ?></strong>
    </div>
  <?php endforeach ?>
  <hr class="vsep"/>
  <span class="theme-switch-wrapper">
    <label class="theme-switch" for="theme-checkbox">
        <input type="checkbox" id="theme-checkbox"/>
        <span class="slider round"></span>
    </label>
    <div id="theme"><span>DARK THEME</span></div>
  </span>
  <form id="form" action="/" method="POST" class="form-inline d-inline">
    <button type="submit" name="help" class="btn btn-sm btn-outline-secondary text-dark ml-3 mb-1 pl-2 pr-2">
      <em class="fa-solid fa-question"></em> help
    </button>
  </form>
  <a href data-toggle="modal" data-target="#bsModal" data-type="html" data-path="/templates/about.html">
    <button type="button" id="about" class="btn btn-sm btn-outline-secondary text-dark mb-1 pl-2 pr-2">
      <em class="fa-solid fa-poo"></em> about
    </button>
  </a>
  <a href="/auth/login.php">
    <button type="button" class="btn btn-sm btn-outline-secondary text-dark mb-1 pl-2 pr-2" <?= (cfg::get('auth') !== 'none' ? "" : "disabled") ?>>
      <em class="fa-solid fa-id-card"></em> login
    </button>
  </a>
</div>

<div class="modal fade" id="bsModal" tabindex="F1" aria-labelledby="bsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" style="max-width: 1200px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bsModalLabel"></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p style="white-space: pre-line"></p>
        <iframe title="modal" id="bsModalFrame" src="" style="zoom:0" width="80%" height="600"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<div>
  <div>
    <button type="button" id="colShow" class="btn-collapse"><em class="fa-solid fa-folder-open"></em> Show All</button>
    <button type="button" id="colHide" class="btn-collapse"><em class="fa-solid fa-folder"></em> Hide All</button>
  </div>
  <p></p>
</div>

<p></p>

<?php if (cfg::get('spy')['enabled']): ?>
  <div class="enabled-group">
    <p>
      <a class="btn-collapse" data-toggle="collapse" href="#colSpy" role="button" aria-expanded="false" aria-controls="colSpy">
        <em class="fa-solid fa-chevron-up" id="colSpyUp"></em>
        <em class="fa-solid fa-chevron-right" id="colSpyRight"></em>Spy
      </a>
    </p>
    <div class="group collapse multi-collapse" id="colSpy">
      <p></p>

      <div class="spy_menu">
        &nbsp;
        <a href="<?php $_SERVER['PHP_SELF'] ?>"><button type="button" class="btn btn-sm btn-outline-secondary text-dark mb-1 pr-2">
          <em class="fa-solid fa-sync"></em>
        </button></a>
        <button type="button" class="btn btn-sm btn-outline-secondary text-dark mb-1 pr-2" <?= (cfg::get('spy')['refresh'] ? "" : "disabled") ?> onclick="set_norefresh(3000);">
          <em class="fa-solid fa-pause-circle"></em>
        </button>
        &nbsp;
        <a href="/spy"><button type="button" class="btn btn-sm btn-custom mb-1 mr-2 ">
          <em class="fa-solid fa-up-right-and-down-left-from-center icon"></em>
        </button></a>
      </div>

      <div id="include_spy_totals" class="totals">
      </div>
      <p></p>
      <div class="subcat">Online users</div>
      <div id="include_spy_users" class="users ml-2">&lt;none&gt;</div>
      <div id="spy_api_result"></div>
    </div>
  </div>
  <p></p>
<?php endif ?>

<div class="hspace"></div>

<?php if (!empty($filemanager)): ?>
  <div class="enabled-group">
    <p>
      <a class="btn-collapse" data-toggle="collapse" href="#colFileMan" role="button" aria-expanded="false" aria-controls="colFileMan">
        <em class="fa-solid fa-chevron-up" id="colFileManUp"></em>
        <em class="fa-solid fa-chevron-right" id="colFileManRight"></em>File Manager
      </a>
    </p>
    <div class="group collapse multi-collapse" id="colFileMan">
      <?php if (!empty($filemanager['dirs']) && count($filemanager['dirs']) > 0): ?>
        Browse:
        <?php foreach ($filemanager['dirs'] as $name => $path): ?>
          <button type="button" data-toggle="modal" data-target="#bsModal" data-type="dir" data-path="<?= $path ?>" class="btn btn-sm btn-custom"><?= $name ?></button>
          <hr class="vsep"/>
          <?php endforeach ?>
      <?php endif?>
      <?php if (!empty($filemanager['files']) && count($filemanager['files']) > 0): ?>
        Edit:
        <?php foreach ($filemanager['files'] as $file => $path): ?>
          <button type="button" data-toggle="modal" data-target="#bsModal" data-type="file" data-path="<?= $path ?>" data-edit="<?= $file ?>" class="btn btn-sm btn-custom"><?= $file ?></button>
        <?php endforeach ?>
      <?php endif?>
    </div>
  </div>
<?php endif?>


<div class="hspace"></div>

<form id="form" action="/" method="POST">

  <div class="enabled-group">
    <div class="hspace"></div>
    <p>
      <label for="userCmd">
        <a class="btn-collapse" data-toggle="collapse" href="#colUserMgmt" role="button" aria-expanded="false" aria-controls="colUserMgmt">
          <em class="fa-solid fa-chevron-up" id="colUserMgmtUp"></em>
          <em class="fa-solid fa-chevron-right" id="colUserMgmtRight"></em>User Management
        </a>
      </label>
    </p>
    <div class="group collapse multi-collapse" id="colUserMgmt">

      <!-- main: begin includes -->

      <div class="subcat">Users</div>
      <p></p>
      <?php include 'users.html.php' ?>
      <p></p>

      <div class="subcat">Groups</div>
      <p></p>
      <?php include 'groups.html.php' ?>
      <p></p>

      <div class="subcat">Change</div>
      <?php include 'change.html.php' ?>
      <?php include 'more_options.html.php' ?>

      <!-- main: end includes -->

      <p></p>
      <div class="subcat">Action log</div>
        <button type="submit" name="gltoolCmd" value="gltool_tail" class="btn btn-sm btn-custom">Last 10</button>
        <button type="submit" name="gltoolCmd" value="gltool_log" class="btn btn-sm btn-custom">View all</button>
      </div>
    </div>
  </div>

  <?php if (!empty(cfg::get('ui_buttons')['glftpd']) && count(cfg::get('ui_buttons')['glftpd']) > 0): ?>
    <div class="enabled-group">
      <div class="hspace"></div>
      <p>
        <label for="glCmd">
          <a class="btn-collapse" data-toggle="collapse" href="#colGlftpd" role="button" aria-expanded="false" aria-controls="colGlftpd">
            <em class="fa-solid fa-chevron-up" id="colGlftpdUp"></em>
            <em class="fa-solid fa-chevron-right" id="colGlftpdRight"></em>Glftpd
          </a>
        </label>
      </p>
      <div class="group collapse multi-collapse" id="colGlftpd">
        <?php foreach (cfg::get('ui_buttons')['glftpd'] as $key => $value): ?>
          <button type="submit" name="glCmd" value="<?= $value['cmd'] ?>" class="btn btn-sm btn-custom"><?= $key ?></button>
        <?php endforeach ?>
        <?php if (cfg::get('mode') == "local" && !empty(cfg::get('local')['env_bus'])): ?>
          <div id="help" class="form-text text-muted ml-2">
            <small><em>connecting to host using systemd dbus broker</em></small>
          </div>
        <?php endif ?>
      </div>
    </div>
  <?php endif ?>

  <?php if (cfg::get('mode') == "docker" && !empty(cfg::get('ui_buttons')['docker']) && count(cfg::get('ui_buttons')['docker']) > 0): ?>
    <div class="enabled-group">
      <div class="hspace"></div>
      <p>
        <label for="dockerCmd">
          <a class="btn-collapse" data-toggle="collapse" href="#colDocker" role="button" aria-expanded="false" aria-controls="colDocker">
            <em class="fa-solid fa-chevron-up" id="colDockerUp"></em>
            <em class="fa-solid fa-chevron-right" id="colDockerRight"></em>Docker
          </a>
        </label> 
      </p>
      <div class="group collapse multi-collapse" id="colDocker">
        <?php foreach (cfg::get('ui_buttons')['docker'] as $key => $value): ?>
          <button type="submit" name="dockerCmd" value="<?= $value['cmd'] ?>" class="btn btn-sm btn-custom <?=isset($value['disabled']) ? 'disabled' : ''?>"><?= $key ?></button>
        <?php endforeach ?>
      </div>
    </div>
  <?php endif ?>

  <?php //if ((cfg::get('mode') == "docker") || (cfg::get('mode') == "local" && !$local_dockerenv_exists)): ?>
  <?php if (!empty(cfg::get('ui_buttons')['term']) && count(cfg::get('ui_buttons')['term']) > 0): ?>
    <div class="enabled-group">
      <div class="hspace"></div>
      <p>
        <label for="termCmd">
          <a class="btn-collapse" data-toggle="collapse" href="#colTerm" role="button" aria-expanded="false" aria-controls="colTerm">
            <em class="fa-solid fa-chevron-up" id="colTermUp"></em>
            <em class="fa-solid fa-chevron-right" id="colTermRight"></em>Terminal
          </a>
        </label>
      </p>
      <div class="group collapse multi-collapse" id="colTerm">
        <?php foreach (cfg::get('ui_buttons')['term'] as $key => $value): ?>
            <button type="submit" name="termCmd" value="<?= $value['cmd'] ?>" id="<?= $value['cmd'] ?>" class="btn btn-sm btn-custom"><?= $key ?></button>
            <?= (isset($value['sep'])) ? '<hr class="vsep"/>' : '' ?>
        <?php endforeach ?>
        <a class="btn-txt" data-toggle="collapse" href="#colTermInfo" role="button" aria-expanded="false" aria-controls="colTermInfo">
          <em class="fa-solid fa-circle-info"></em>
        </a>
        <div class="collapse" id="colTermInfo">
          <div class="card card-body">
            <div class="text-muted">
              Run cli tools in browser, interactive commands use javascript based terminal <a href="https://github.com/sorenisanerd/gotty">goTTY</a>.
              Click <strong>close tty</strong> button to kill any runaway gotty processes left open. Sitewho (pywho) is shown in a popup and does not require gotty.
            </div>
          </div>
        </div>
     </div>
    </div>
  <?php endif ?>

</form>

<div class="hspace"></div>
<div class="bottom"></div>
