<?php

class Checkbots 
{
  private $dir; 
  private $rules;
  private $time;
  private $blockIt = [];
  private $user = [];

  public function __construct(
    $dir ='requestBlocker/',
    $rules = [['requests' => 5, 'sek' => 5, 'blockTime' => 300]]
  ) {
    $this->dir = $dir;
    $this->rules = $rules;   
    $this->time = time();
    $this->blockIt = [];
    $this->user = [];
  }

  private function isBots()
  {
    $agent='';
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
      $agent = $_SERVER['HTTP_USER_AGENT'];
    }
    return (preg_match('/(test|http|google|baidu|yahoo|spider|msn|bot|jeevesteoma|slurp|gulper|linkwalker|validator|webaltbot|wget|feed|bing|websitepulse|sogou|mediapartners|sohu|soso|search|yodao|robozilla)/i', $agent));
  }


  public function requestBlocker()
  {
    if($this->isBots() !== 0) {
      // chan request luon
      return true;
    }

    
    $dir = $this->dir;
    $rules = $this->rules;
    $time    = $this->time;
    $blockIt = $this->blockIt;
    $user    = $this->user;

    #kiem tra dia chi ip cua client gui len
    $user[] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'IP_unknown';
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
}