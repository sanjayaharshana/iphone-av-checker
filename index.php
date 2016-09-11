

<?php
  function refresh_data() {
    global $file;
    $json = file_get_contents('https://reserve.cdn-apple.com/US/en_US/reserve/iPhone/availability.json');
    $json = json_decode($json, true);
    $json['timer'] = time();
    $json_enc = json_encode($json, JSON_PRETTY_PRINT);

    file_put_contents($file, $json_enc);
    return $json;
  }

  $stores = $models = false;

  if(isset($_GET['stores'])) {
    $stores = $_GET['stores'];
  }

  if(isset($_GET['models'])) {
    $models = $_GET['models'];
  }


  $file = 'data.json';
  $is_cached = file_get_contents($file);
  if($is_cached !== false) {
    $json = json_decode($is_cached, true);
    if(time() - $json['timer'] > 10) {
      $json = refresh_data();
    }
  } else {
    $json = refresh_data();
  }

  $result = array();

  if($stores !== false && $models !== false) {

    for($i = 0; $i < count($stores); $i++) {
      $models_in_store = $json[$stores[$i]];
      $all_models = array();
      for($j = 0; $j < count($models); $j++) {
        $model_availability = $models_in_store[$models[$j]];
        if($model_availability == 'ALL') {
          $all_models[$models[$j]] = true;
        } else {
          $all_models[$models[$j]] = false;
        }
      }

      $result[$stores[$i]] = $all_models;
    }
  }

  $stores = file_get_contents('https://raw.githubusercontent.com/MystK/apple-reservations-checker/master/stores.json');
  $stores = json_decode($stores, true);

  $models = file_get_contents('https://raw.githubusercontent.com/MystK/apple-reservations-checker/master/product-offering.json');
  $models = json_decode($models, true);

  $carriers = array();
  foreach($models['carriers'] as $carrier) {
    $carriers[$carrier['groups'][0]] = $carrier['carrier_label'];
  }

 ?>
<!DOCTYPE HTML>
<html>
<head>
  <meta charset="UTF-8">

  <style>
    * {
      padding: 0;
      margin: 0;
    }

    select {
      resize: both;
      height: 500px;
    }

    .avail, .unavail {
      padding: 8px;
      margin-left: 25px;
    }

    .avail {
      background-color: #22dd22;
    }

    .unavail {
      background-color: #dd2222;
    }
  </style>
</head>
<body>
  <p>Based on <a href="https://github.com/MystK/apple-reservations-checker">MystK</a> availability checker.</p>
  <form method="get">
    <?php
      print 'Stores: <select name="stores[]" multiple>';
      foreach($stores['stores'] as $store) {
        print '<option value="' . $store['storeNumber'] . '">' . $store['storeName'] . '</option>';
      }
      print '</select>';
      print 'Models: <select name="models[]" multiple>';

      foreach($models['skus'] as $model) {
        print '<option value="' . $model['part_number'] . '">' . $carriers[$model['group_id']] . ' - ' . $model['productDescription'] . '</option>';
      }
      print '</select>';
    ?>
    <input type="submit">
  </form>

  <?php

    foreach($stores['stores'] as $store) {
      foreach($result as $store_id => $store_models) {
        if($store['storeNumber'] == $store_id) {
          print '<p><h1>' . $store['storeName'] . ':</h1>';
          foreach($models['skus'] as $model) {
            foreach($store_models as $model_id => $avail) {
              if($model['part_number'] == $model_id) {
                $name = $carriers[$model['group_id']] . ' - ' . $model['productDescription'];
                if($avail) {
                  print '<p class="avail"> ' . $name . ' is available</p>';
                } else {
                  print '<p class="unavail"> ' . $name . ' is unavailable</p>';
                }
              }
            }
          }
          print '</p>';
        }
      }
    }
   ?>
</body>
</html>
