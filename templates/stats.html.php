<!DOCTYPE html>
<html lang="en">

<head>
  <title>GLFTPD:WEBUI:STATS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="lib/bootstrap-4.6.2-dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/dark.css">
  <link rel="stylesheet" href="lib/fontawesome-free-6.5.1-web/css/all.min.css"> 
</head>

<body>
  <div class="title mb-5">
    <h1 style="display:inline"><?= cfg::get('theme')['title'] ?></h1>
    <h1 style="display:inline;text-decoration:none">&nbsp;| ALL STATS</h1>
  </div>
  <p></p>
  
  <a href="<?= $_SERVER['PHP_SELF'] ?>?stats=1>"><button class="fixed-top btn-reload ml-5">Reload</a></button>
  
  <a href="<?= $_SERVER['PHP_SELF'] ?>"><button class="fixed-top btn-back ml-5">Back</a></button>
  
  <div class="stats_tmpl">
    <?php foreach (cfg::get('stats')['commands'] as $key => $item): ?>
      <?php if ($item['show'] >= 1): ?>
        <div class="stats ml-3" style="height:<?= cfg::get('stats')['options']['max_pos'] * 40 ?>px;"> 
          <h6><?= "{$item['stat']} " . (substr($key, 0, 1) === 'G' ? "GROUP" : "USER") ?> TOP</h6>
          <p></p>
          <div>
            <?php $pos = 1; ?>
            <?php $result = $data->get_chart_stats($item); ?>
            <?php $color = cfg::get('stats')['options']['color'] ?>
            <?php foreach ($result['fields_all'] as $fields):  ?>
              <?php if ($pos <= cfg::get('stats')['options']['max_pos']): ?>
                <div>
                  <?= sprintf("%02d", $pos) ?>. <?= ($pos === 1) ? "<strong>{$fields[0]}</strong>" : $fields[0] ?> <?= $fields[1] ?> (<?= $fields[2] ?>)
                </div>
              <?php endif ?>
              <?php $pos++ ?>
            <?php endforeach ?>
            <?php for ($pos; $pos <= cfg::get('stats')['options']['max_pos']; $pos++): ?>
              <div>&nbsp;</div>
              <?php $pos++; ?>
            <?php endfor ?>
            <?php print(create_svg("pie", $result['chart_data'], $result['chart_labels'], cfg::get('palette')[$color])); ?>
          </div>
        </div>
      <?php endif ?>
    <?php endforeach ?>
  </div>

  <script type='text/javascript'>
    const thisTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : null;
    if (thisTheme) document.documentElement.setAttribute('data-theme', thisTheme);
  </script>
  </body>
</html>