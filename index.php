<?php
require 'bots.php';

if (requestBlocker() || spider()) {
  echo 'dont pass here!';
  print_r(requestBlocker());
} else {
  echo "go on!";
}