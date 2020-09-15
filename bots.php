<?php

function requestBlocker()
{

	// tao thu muc - co quyen ghi du lieu, se ghi log cac bots truy cap vao
  $dir = 'requestBlocker/';

  $rules   = [
   	// tao ra cac luat check thoi gian ma bots no gui request len lien tuc
   	// tuy viec thich block bao thoi gian hoac la khoa luon ko cho gui request vao
    [
      //if > 3 requests in 3 Seconds then Block client 300 Seconds
      'requests' => 3, //3 requests
      'sek' => 3, //3 requests in 3 Seconds
      'blockTime' => 300 // Block client 300 Seconds
    ],
    [
      //if > 5 requests in 5 Seconds then Block client 500 Seconds
      'requests' => 5, //5 requests
      'sek' => 5, //5 requests in 5 Seconds
      'blockTime' => 500 // Block client 500 Seconds
    ]
  ];
  $time    = time();
  $blockIt = [];
  $user    = [];

  #kiem tra dia chi ip va user agent cua client gui len
  $user[] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'IP_unknown';
  $user[] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
  $user[] = strtolower(gethostbyaddr($user[0])); // gethostbyaddr : lay dia chi host tu dia chi ip

  # Luu thong tin cua bots vao file de check cho cac request tiep theo
  # vi bots se khong the truy cap vao session dc nen ko check qua cai do
  $botFile = $dir . substr($user[0], 0, 8) . '_' . substr(md5(join('', $user)), 0, 5) . '.txt';

  if (file_exists($botFile)) {
    $file   = file_get_contents($botFile);
    $client = unserialize($file);

  } else {
    $client                = [];
    $client['time'][$time] = 0;
  }

  # Set/Unset Blocktime for blocked Clients
  if (isset($client['block'])) {
    foreach ($client['block'] as $ruleNr => $timestampPast) {
      $elapsed = $time - $timestampPast;
      if (($elapsed ) > $rules[$ruleNr]['blockTime']) {
        unset($client['block'][$ruleNr]);
        continue;
      }
      // thong bao vi pham luat nao ??
      $blockIt[] = 'Block active for Rule: ' . $ruleNr . ' - unlock in ' . ($elapsed - $rules[$ruleNr]['blockTime']) . ' Sec.';
    }
    if (!empty($blockIt)) {
      return $blockIt;
    }
  }

  # log/count each access
  if (!isset($client['time'][$time])) {
    $client['time'][$time] = 1;
  } else {
    $client['time'][$time]++;
  }

  #check the Rules for Client
  $min = [0];
  foreach ($rules as $ruleNr => $v) {
    $i            = 0;
    $tr           = false;
    $sum[$ruleNr] = 0;
    $requests     = $v['requests'];
    $sek          = $v['sek'];
    foreach ($client['time'] as $timestampPast => $count) {
      if (($time - $timestampPast) < $sek) {
        $sum[$ruleNr] += $count;
        if ($tr == false) {
          #register non-use Timestamps for File 
          $min[] = $i;
          unset($min[0]);
          $tr = true;
        }
      }
      $i++;
    }

    if ($sum[$ruleNr] > $requests) {
      $blockIt[]                = 'Limit : ' . $ruleNr . '=' . $requests . ' requests in ' . $sek . 'seconds!';
      $client['block'][$ruleNr] = $time;
    }
  }
  $min = min($min) - 1;
  #drop non-use Timestamps in File 
  foreach ($client['time'] as $k => $v) {
    if (!($min <= $i)) {
      unset($client['time'][$k]);
    }
  }
  $file = file_put_contents($botFile, serialize($client));
  return $blockIt;
}


if ($t = requestBlocker()) {
  echo 'dont pass here!';
  print_r($t);
} else {
  echo "go on!";
}