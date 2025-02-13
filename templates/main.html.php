<!DOCTYPE html>
<html lang="en">

<head>
  <title>GLFTPD:WEBUI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-Equiv="Cache-Control" Content="no-cache" />
  <meta http-Equiv="Pragma" Content="no-cache" />
  <meta http-Equiv="Expires" Content="0" />
  <link rel="stylesheet" href="lib/bootstrap-4.6.2-dist/css/bootstrap.min.css">
  <?php if (cfg::get('spy')['show']): ?>
    <link rel="stylesheet" href="assets/css/spy.css">
  <?php else: ?>
    <link rel="stylesheet" href="assets/css/style.css">
  <?php endif ?>
  <link rel="stylesheet" href="assets/css/dark.css">
  <link rel="stylesheet" href="lib/fontawesome-free-6.5.1-web/css/all.min.css">
  <script type="text/javascript" src="lib/jquery/jquery-3.6.0.min.js"></script>
  <script type="text/javascript" src="lib/bootstrap-4.6.2-dist/js/bootstrap.bundle.min.js"></script>
  <script type="text/javascript" src="assets/js/modal_func.js"></script>
</head>

<body>
  <div class="title"><?= cfg::get('theme')['title'] ?></div>
  <div class="status">
    &nbsp;
    MODE: <span id="mode"><?= cfg::get('mode') ?></span>
    <hr class="vsep" />
    STATUS:
    <?php foreach ($_SESSION['status'] as $service => $state): ?>
      <div id="<?= $state ?>" style="display:<?= (preg_match('/(up|down|open)/', $state) ? 'inline-block' : 'none') ?>">
        <?= $service ?>:<strong><?= strtoupper($state) ?></strong>
      </div>
    <?php endforeach ?>
    <hr class="vsep" />
    <span class="theme-switch-wrapper">
      <label class="theme-switch" for="theme-checkbox">
        <input type="checkbox" id="theme-checkbox" />
        <span class="slider round"></span>
      </label>
      <div id="theme"><span>DARK THEME</span></div>
    </span>
    <form id="form" action="/" method="POST" class="form-inline d-inline">
      <button type="submit" name="show_all_stats" class="btn btn-sm btn-outline-<?= cfg::get('theme')['btn-color-2'] ?> text-dark mb-1 pl-2 pr-2 ml-3" ?>
        <em class='fa-solid fa-chart-simple'></em> stats
      </button>
    </form>
    <a href="/auth/login.php">
      <button type="button" class="btn btn-sm btn-outline-<?= cfg::get('theme')['btn-color-2'] ?> text-dark mb-1 pl-2 pr-2" <?= (cfg::get('auth') !== 'none' ? "" : "disabled") ?>>
        <em class="fa-solid fa-id-card"></em> login
      </button></a>
    <a href data-toggle="modal" data-target="#bsModal" data-type="html" data-path="/templates/about.html">
      <button type="button" id="about" class="btn btn-sm btn-outline-<?= cfg::get('theme')['btn-color-2'] ?> text-dark mb-1 pl-2 pr-2 ml-3">
        <em class="fa-solid fa-poo"></em> about
      </button></a>
    <form id="form" action="/" method="POST" class="form-inline d-inline">
      <button type="submit" name="help" class="btn btn-sm btn-outline-<?= cfg::get('theme')['btn-color-2'] ?> text-dark mb-1 pl-2 pr-2">
        <em class="fa-solid fa-question"></em> help
      </button>
    </form>
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
          <button type="button" class="btn btn-<?= cfg::get('theme')['btn-color-1'] ?>" data-dismiss="modal">Close</button>
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

  <?php if (cfg::get('spy')['show']): ?>
    <div>
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
          <a href="<?= $_SERVER['PHP_SELF'] ?>"><button type="button" class="btn btn-sm btn-outline-<?= cfg::get('theme')['btn-color-2'] ?> text-dark mb-1 pr-2">
              <em class="fa-solid fa-sync"></em>
            </button></a>
          <button type="button" class="btn btn-sm btn-outline-<?= cfg::get('theme')['btn-color-2'] ?> text-dark mb-1 pr-2" <?= (cfg::get('spy')['refresh'] ? "" : "disabled") ?> onclick="set_norefresh(3000);">
            <em class="fa-solid fa-pause-circle"></em>
          </button>
          &nbsp;
          <a href="/spy">
            <button type="button" class="btn btn-sm btn-<?= cfg::get('theme')['btn-small-color'] ?> mb-1 mr-2 ">
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

  <?php if ($fm_data['count']['dirs'] > 0 || $fm_data['count']['files']['all'] > 0): ?>
    <div>
      <p>
        <a class="btn-collapse" data-toggle="collapse" href="#colFileMan" role="button" aria-expanded="false" aria-controls="colFileMan">
          <em class="fa-solid fa-chevron-up" id="colFileManUp"></em>
          <em class="fa-solid fa-chevron-right" id="colFileManRight"></em>File Manager
        </a>
      </p>
      <div class="group collapse multi-collapse" id="colFileMan">
        <?php if ($fm_data['count']['dirs'] > 0): ?>
          Browse:
          <?php foreach ($filemanager as $key => $value): ?>
            <?php if ($value['type'] === 'dir'): ?>
              <button type="button" data-toggle="modal" data-target="#bsModal" data-type="dir" data-path="<?= $value['path'] ?>" class="btn btn-sm btn-<?= cfg::get('theme')['btn-small-color'] ?>"><?= $key ?></button>
            <?php endif ?>
          <?php endforeach ?>
        <?php endif ?>
        <?php if ($fm_data['count']['files']['edit'] > 0): ?>
            <hr class="vsep" />
            Edit:
          <?php foreach ($filemanager as $key => $value): ?>
            <?php if ($value['type'] === 'file' &&  $value['mode'] === 'edit'): ?>
              <button type="button" data-toggle="modal" data-target="#bsModal" data-type="file" data-path="<?= $value['path'] ?>" data-edit="<?= $key ?>" class="btn btn-sm btn-<?= cfg::get('theme')['btn-small-color'] ?>"><?= $key ?></button>
            <?php endif ?>
          <?php endforeach ?>
        <?php endif ?>
        <?php if ($fm_data['count']['files']['view'] > 0): ?>
           <hr class="vsep" />
            View:
          <?php foreach ($filemanager as $key => $value): ?>
            <?php if ($value['type'] === 'file' && ($value['mode'] === 'view' || !isset($value['mode']))): ?>
              <button type="button" data-toggle="modal" data-target="#bsModal" data-type="file" data-path="<?= $value['path'] ?>" data-view="<?= $key ?>" class="btn btn-sm btn-<?= cfg::get('theme')['btn-small-color'] ?>"><?= $key ?></button>
            <?php endif ?>
          <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>
  <?php endif ?>

  <div class="hspace"></div>

  <form id="form" action="/" method="POST">

    <div>
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
        <?php include 'change_more.html.php' ?>

        <!-- main: end includes -->

        <p></p>
        <div class="subcat">Action log</div>
        <button type="submit" name="gltoolCmd" value="gltool_tail" class="btn btn-sm btn-<?= cfg::get('theme')['btn-small-color'] ?>">Last 10</button>
        <button type="submit" name="gltoolCmd" value="gltool_log" class="btn btn-sm btn-<?= cfg::get('theme')['btn-small-color'] ?>">View all</button>
      </div>
    </div>
    </div>

    <?php if (!empty(cfg::get('buttons')['Glftpd']) && count(cfg::get('buttons')['Glftpd']) > 0): ?>
      <div>
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
          <?php foreach (cfg::get('buttons')['Glftpd'] as $key => $value): ?>
            <button type="submit" name="glCmd" value="<?= $value['cmd'] ?>" class="btn btn-sm btn-<?= cfg::get('theme')['btn-small-color'] ?>"><?= $key ?></button>
          <?php endforeach ?>
          <?php if (cfg::get('mode') == "local" && !empty(cfg::get('local')['env_bus'])): ?>
            <div id="help" class="form-text text-muted ml-2">
              <small><em>connecting to host using systemd dbus broker</em></small>
            </div>
          <?php endif ?>
        </div>
      </div>
    <?php endif ?>

    <?php if (cfg::get('mode') == "docker" && !empty(cfg::get('buttons')['Docker']) && count(cfg::get('buttons')['Docker']) > 0): ?>
      <div>
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
          <?php foreach (cfg::get('buttons')['Docker'] as $key => $value): ?>
            <button type="submit" name="dockerCmd" value="<?= $value['cmd'] ?>" class="btn btn-sm btn-<?= cfg::get('theme')['btn-small-color'] ?> <?= isset($value['disabled']) ? 'disabled' : '' ?>"><?= $key ?></button>
          <?php endforeach ?>
        </div>
      </div>
    <?php endif ?>

    <?php if (!empty(cfg::get('buttons')['Terminal']) && count(cfg::get('buttons')['Terminal']) > 0): ?>
      <div>
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
          <?php foreach (cfg::get('buttons')['Terminal'] as $key => $value): ?>
            <button type="submit" name="termCmd" value="<?= $value['cmd'] ?>" id="<?= $value['cmd'] ?>" class="btn btn-sm btn-<?= cfg::get('theme')['btn-small-color'] ?>"><?= $key ?></button>
            <?= (isset($value['sep'])) ? '<hr class="vsep"/>' : '' ?>
          <?php endforeach ?>
          <a class="btn-txt" data-toggle="collapse" href="#colTermInfo" role="button" aria-expanded="false" aria-controls="colTermInfo">
            <em class="fa-solid fa-circle-info h5 align-text-top"></em>
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

  <div class="hspace"></div>

  <?php if (!empty(cfg::get('stats'))): ?>
    <div>
      <p>
        <a class="btn-collapse" data-toggle="collapse" href="#colStats" role="button" aria-expanded="false" aria-controls="colStats">
          <em class="fa-solid fa-chevron-up" id="colStatsUp"></em>
          <em class="fa-solid fa-chevron-right" id="colStatsRight"></em>Stats
        </a>
      </p>
      <div class="group collapse multi-collapse" id="colStats">
        <div>
          <?php foreach (cfg::get('stats')['commands'] as $key => $item): ?>
            <?php if ($item['show'] === 2): ?>
              <div class="stats stats_main">
                <h6><?= "{$item['stat']}  " . (substr($key, 0, 1) === 'G' ? "GROUP" : "USER") ?> TOP</h6>
                <p></p>
                <div>
                  <?php $pos = 1; ?>
                  <?php $result = $data->get_chart_stats($item); ?>
                  <?php foreach ($result['fields_all'] as $fields):  ?>
                    <?php if ($pos <= 10): ?>
                      <div>
                        <?= sprintf("%02d", $pos) ?>. <?= ($pos === 1) ? "<strong>{$fields[0]}</strong>" : $fields[0] ?> <?= $fields[1] ?> (<?= $fields[2] ?>)
                      </div>
                    <?php endif ?>
                    <?php $pos++ ?>
                  <?php endforeach ?>
                </div>
              </div>
              <?= create_svg("pie", $result['chart_data'], $result['chart_labels'], cfg::get('palette')['default']); ?>
            <?php endif ?>
          <?php endforeach ?>
          <button type="submit" name="show_all_stats" class="btn btn-<?= cfg::get('theme')['btn-color-1'] ?> mr-4" style="float:right;margin-top:30%;">
            <em class='fa-solid fa-chart-simple'></em> More...
          </button>
        </div>
      </div>
    </div>
  <?php endif ?>

  </form>

  <div class="hspace"></div>
  <div class="bottom"></div>
