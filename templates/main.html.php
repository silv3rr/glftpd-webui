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
  <a href="https://github.com/silv3rr/glftpd-webui/blob/master/README.md"><button type="button" class="btn btn-sm btn-outline-secondary text-dark ml-3 mb-1 pl-2 pr-2">
    <em class="fa-solid fa-question"></em> help
  </button></a>
  <a href data-toggle="modal" data-target="#bsModal" data-type="html" data-path="/templates/about.html">
    <button type="button" id="about" class="btn btn-sm btn-outline-secondary text-dark mb-1 pl-2 pr-2">
    <em class="fa-solid fa-poo"></em> about
  </button></a>
  <a href="/auth/login.php"><button type="button" class="btn btn-sm btn-outline-secondary text-dark mb-1 pl-2 pr-2" <?= (cfg::get('auth') !== 'none' ? "" : "disabled") ?>>
    <em class="fa-solid fa-id-card"></em> login
  </button></a>
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
  <div class="cmd collapse multi-collapse" id="colSpy">
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
<div class="enabled-group">
  <p>
    <a class="btn-collapse" data-toggle="collapse" href="#colFileMan" role="button" aria-expanded="false" aria-controls="colFileMan">
      <em class="fa-solid fa-chevron-up" id="colFileManUp"></em> 
      <em class="fa-solid fa-chevron-right" id="colFileManRight"></em>File Manager
    </a>
  </p>
  <div class="cmd collapse multi-collapse" id="colFileMan">
    Browse:
    <button type="button" data-toggle="modal" data-target="#bsModal" data-type="dir"  data-path="glftpd/site" class="btn btn-sm btn-custom">Glftpd Site</button>
    <button type="button" data-toggle="modal" data-target="#bsModal" data-type="dir"  data-path="" class="btn btn-sm btn-custom">Web Files</button>
    <hr class="vsep"/>
    Edit:
    <button type="button" data-toggle="modal" data-target="#bsModal" data-type="file" data-path="" data-edit="config.php" class="btn btn-sm btn-custom">config.php</button>
    <button type="button" data-toggle="modal" data-target="#bsModal" data-type="file" data-path="glftpd" data-edit="glftpd.conf" class="btn btn-sm btn-custom">glftpd.conf</button>
    <button type="button" data-toggle="modal" data-target="#bsModal" data-type="file" data-path="glftpd/sitebot" data-edit="eggdrop.conf" class="btn btn-sm btn-custom">eggdrop.conf</button>
    <button type="button" data-toggle="modal" data-target="#bsModal" data-type="file" data-path="glftpd/sitebot/pzs-ng" data-edit="ngBot.conf" class="btn btn-sm btn-custom">ngBot.conf</button>
  </div>
</div>



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
    <div class="cmd collapse multi-collapse" id="colUserMgmt">

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

      </div>
      <p></p>
      <div class="subcat">Action log</div>
        <button type="submit" name="gltoolCmd" value="gltool_tail" class="btn btn-sm btn-custom">Last 10</button>
        <button type="submit" name="gltoolCmd" value="gltool_log" class="btn btn-sm btn-custom">View all</button>
      </div>
  </div>


  </div>


  <?php if (!$local_dockerenv_exists): ?>
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
    <div class="cmd collapse multi-collapse" id="colGlftpd">
        <button type="submit" name="glCmd" value="glftpd_status" class="btn btn-sm btn-custom">Status</button>
        <button type="submit" name="glCmd" value="glftpd_start" class="btn btn-sm btn-custom">Start</button>
        <button type="submit" name="glCmd" value="glftpd_stop" class="btn btn-sm btn-custom">Stop</button>
        <button type="submit" name="glCmd" value="glftpd_restart" class="btn btn-sm btn-custom">Restart</button>
   </div>
  </div>
  <?php endif ?>

  <?php if (cfg::get('mode') == "local"): ?>
    <div class="disabled-group">
      <div class="hspace"></div>
        <p>
        <label for="sdCmd">
          <a class="btn-collapse" data-toggle="collapse" href="#colSystemd" role="button" aria-expanded="false" aria-controls="colSystemd">
            <em class="fa-solid fa-chevron-up" id="colSystemdUp"></em> 
            <em class="fa-solid fa-chevron-right" id="colSystemdRight"></em>System
          </a>
        </label> 
      </p>
      <span class="text-muted">Controls glftpd service</span>
    </div>
  <?php endif ?>

  <?php if (cfg::get('mode') == "docker"): ?>
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
      <div class="cmd collapse multi-collapse" id="colDocker">  
        <button type="button" name="dockerCmd" value="glftpd_create" class="btn btn-sm btn-custom disabled">Create</button>
        <button type="submit" name="dockerCmd" value="glftpd_inspect" class="btn btn-sm btn-custom">Inspect</button>
        <button type="submit" name="dockerCmd" value="glftpd_top" class="btn btn-sm btn-custom">Top</button>
        <button type="submit" name="dockerCmd" value="glftpd_kill" class="btn btn-sm btn-custom">Kill</button>
        <button type="submit" name="dockerCmd" value="glftpd_tail" class="btn btn-sm btn-custom">Tail log</button>
        <button type="submit" name="dockerCmd" value="glftpd_logs" class="btn btn-sm btn-custom">View log</button>
      </div>
    </div>
  <?php endif ?>
  
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
    <div class="cmd collapse multi-collapse" id="colTerm">
        <button type="submit" name="glCmd" value="pywho" class="btn btn-sm btn-custom">sitewho</button>
        <hr class="vsep"/>
        <button type="submit" name="termCmd" value="tty_eggdrop" id="tty_eggdrop" class="btn btn-sm btn-custom">telnet bot</button>
        <hr class="vsep"/>
        <button type="submit" name="termCmd" value="tty_useredit" id="tty_useredit" class="btn btn-sm btn-custom">useredit</button>
        <button type="submit" name="termCmd" value="tty_glspy" id="tty_glspy" class="btn btn-sm btn-custom">gl_spy</button>
        <button type="submit" name="termCmd" value="tty_pyspy" id="tty_pyspy" class="btn btn-sm btn-custom">pyspy</button>
        <hr class="vsep"/>
        <button type="submit" name="termCmd" value="kill_gotty" id="kill_gotty" class="btn btn-sm btn-custom">close tty</button>
        <hr class="vsep"/>
        <a class=" btn btn-sm btn-custom" data-toggle="collapse" href="#colTermInfo" role="button" aria-expanded="false" aria-controls="colTermInfo">
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

</form>

<div class="hspace"></div>
<div class="bottom"></div>
