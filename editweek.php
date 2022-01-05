<?php
// A spread sheet of the bridge club
// It shows the name and then each wed from 1/5 to the current time.
/*
CREATE TABLE `bridge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `weeks` (
  `fid` int NOT NULL,
  `date` date NOT NULL,
  `cash` decimal(7,2) DEFAULT '0.00',
  `lasttime` datetime NOT NULL,
  UNIQUE KEY `fiddate` (`fid`,`date`),
  KEY `fid` (`fid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);

// Check if user is Authorized
$finger = $_COOKIE['BLP-Finger'];
$bonnieFingers = require("/var/www/bartonphillipsnet/bonnieFinger.php");

if(array_intersect([$finger] , $bonnieFingers)[0] === null) {
  echo <<<EOF
<h1>You are NOT AUTHORIZED</h1>
EOF;
  exit();
}
// End of Check if user is Authorized

$S = new $_site->className($_site);

function add($week, $id, $cash) {
  global $S;
  try {
    $S->query("insert into weeks (fid, date, cash, lasttime) values($id, '$week', '$cash', now())");
  } catch(Exception $e) {
    if($e->getCode() == 1062) { // duplicate key
      return false;
    }
    throw(new SqlException("editweek.php: " . $e->getCode()));
  }
  return true;   
}

function delete($week, $id) {
  global $S;
  $S->query("delete from weeks where fid=$id and date='$week'");
}

function edit($week, $id, $cash) {
  global $S;
  $S->query("update weeks set cash='$cash', lasttime=now() where fid=$id and date='$week'");
}

if($_POST) {
  $week = $_POST['week'];
  $id = $_POST['id'];
  $cash = $_POST['cash'];
  $page = $_POST['page'];

  $cash = empty($cash) ? 0 : $cash;

  $S->query("select fname, lname from bridge where id=$id");
  [$fname, $lname] = $S->fetchrow('num');

  $msg = "The record for $fname $lname for week date $week has been {$page}ed.";
  
  switch($page) {
    case 'delete':
      delete($week, $id);
      break;
    case 'edit':
      edit($week, $id, $cash);
      break;
    case 'add':
      if(add($week, $id, $cash) === false) {
        $msg = "Duplicate Entrey for $fname $lname. Use Edit instead of Add.";
      }
      break;
    default:
      throw(new Exception("editweek.php: $page"));
  }

  $page = ucfirst($page);
  
  $h->title = "Bridge $page";
  $h->desc = $page;
  $h->banner = "<h1>Bridge Recored $page</h1>";

  [$top, $footer] = $S->getPageTopBottom($h);
  
  echo <<<EOF
$top
<h2>$msg</h2>
<a href="/bridge">Return to Home Page</a>
$footer
EOF;
  exit();
}

if($_GET['page'] == 'id') {
  $h->title = "Bridge Edit Week";
  $h->banner = "<h1>Bridge Edit</h1>";
  $h->css =<<<EOF
  <style>
    input { width: 50px; text-align: right; }
  </style>
  EOF;

  [$top, $footer] = $S->getPageTopBottom($h);

  $id = $_GET['id'];
  $week = $_GET['week'];
  
  $S->query("select fname, lname from bridge where id=$id");
  [$fname, $lname] = $S->fetchrow('num');
  $S->query("select cash from weeks where fid=$id and date='$week'");
  $cash = $S->fetchrow('num')[0];
  $line = "$fname $lname <input type='text' name='cash' value='$cash'>";

  echo <<<EOF
$top
<h2>For week $week.</h2>
<form method="post">
$line
<input type="hidden" name="id" value="$id">
<input type="hidden" name="week" value="$week">
<button type='submit' name="page" value="edit">Submit</button>
</form>
<form method="post">
<button type="submit" name="page" value="delete">Delete Entry</button>
<input type="hidden" name="id" value="$id">
<input type="hidden" name="week" value="$week">
</form>
$footer
EOF;
  exit();
}

if($_GET['page'] == 'week') {
  $h->title = "Bridge Add Week";
  $h->banner = "<h1>Bridge Add</h1>";
  $h->css =<<<EOF
  <style>
    input { width: 50px; text-align: right; }
  </style>
  EOF;

  [$top, $footer] = $S->getPageTopBottom($h);

  $week = $_GET['week'];
  $id = $_GET['id'];
  $S->query("select fname, lname from bridge where id=$id");
  [$fname, $lname] = $S->fetchrow('num');
  
  echo <<<EOF
$top
<h2>For week $week</h2>
<p>Add entry for $fname $lname.</p>
<form method='post'>
Add a Donation? <input type='text' name='cash'>
<button type='submit' name='page' value='add'>Add New Record</button>
<input type="hidden" name="id" value="$id">
<input type="hidden" name="week" value="$week">
</form>
$footer
EOF;
  exit();
}

echo "<h1>Go Away</h1>";
