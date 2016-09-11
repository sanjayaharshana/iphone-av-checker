

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
      color: #333333;
    }

    body {
      background: #f1f1f1;
    }

    select {
      width: 300px;
    }

    .avail, .unavail {
      display: inline-block;
      padding: 8px;
      margin: 5px;
      width: 30%;
    }

    .avail {
      background-color: #44dd44;
    }

    .unavail {
      background-color: #dd4444;
    }

    .stores, .models {
      margin: 10px;
      width: 200px;
    }

    input[type=submit] {
      margin: 10px;
      padding: 5px;
    }

    .storeCheck {
      margin-bottom: 20px;
      padding-left: 25px;
    }
  </style>

  <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />

</head>
<body>
  <h1>iPhone availability check</h1>
  <p>Based on <a href="https://github.com/MystK/apple-reservations-checker">MystK</a> availability checker.</p>
  <form method="get">
    <?php
      print '<p class="stores">Stores: <select id="stores" name="stores[]" multiple>';
      foreach($stores['stores'] as $store) {
        print '<option value="' . $store['storeNumber'] . '">' . $store['storeName'] . '</option>';
      }
      print '</select></p>';
      print '<p class="models">Models: <select id="models" name="models[]" multiple>';

      foreach($models['skus'] as $model) {
        print '<option value="' . $model['part_number'] . '">' . $carriers[$model['group_id']] . ' - ' . $model['productDescription'] . '</option>';
      }
      print '</select></p>';
    ?>
    <input type="submit">
  </form>

  <?php

    foreach($stores['stores'] as $store) {
      foreach($result as $store_id => $store_models) {
        if($store['storeNumber'] == $store_id) {
          print '<div class="storeCheck"><h1>' . $store['storeName'] . ':</h1>';
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
          print '</div>';
        }
      }
    }
   ?>

   <script src="https://code.jquery.com/jquery-3.1.0.min.js"   integrity="sha256-cCueBR6CsyA4/9szpPfrX3s49M9vUU5BgtiJj06wt/s="   crossorigin="anonymous"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
   <script>
     $(document).ready(function() {
        $('#stores').select2();
        $('#models').select2();
     });

   </script>
</body>
</html>
