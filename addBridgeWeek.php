<?php
// Add information for Bridge members for current Wed.
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

// Define a week and the first wed. we will use.

define(WEEK, 604800);
define(STARTWED, 1641358800);

//$unixToday = strtotime("today");
$unixToday = strtotime('2022-02-15');
$today = date("l F j, Y", $unixToday);

$unixWed = strtotime("Wednesday", $unixToday);
$unixPrevWed = strtotime("previous Wednesday", $unixToday);
$unixNextWed = strtotime("next Wednesday", $unixToday) + 604800;
$nextWed = date('Y-m-d', $unixNextWed);

if($unixToday >= $unixWed && $unixToday < $unixNextWed) {
  $wed = date('Y-m-d', $unixWed);
} else {
  $wed = date("Y-m-d", $unixPrevWed);
  $unixWed = $unixPrevWed;
} 

$fullDate = date("l F j, Y", $unixWed);

// Post the ADD

if($_POST) {
  $_site->footerFile = null;
  
  $S = new $_site->className($_site);
  
  $h->css =<<<EOF
<style>
table tbody td:nth-of-type(2) { text-align: center; }
table thead th:nth-of-type(3) { text-align: right; }
table tbody td:nth-of-type(3) { text-align: right; }
.posted { font-weight: bold; }
</style>
EOF;

  [$top, $footer] = $S->getPageTopBottom($h);
  
  $ids = $_POST['id'];
  $cash = $_POST['cash'];

  // First insert the id and date.
  
  foreach($ids as $k=>$v) {
    $S->query("select fname, lname from bridge where id=$k");
    [$fname, $lname] = $S->fetchrow('num');
    $names["$fname $lname"] = 1;
    
    try {
      $sql = "insert into weeks (fid, date, lasttime) values('$k', '$wed', now())";
      $S->query($sql);
    } catch(Exception $e) {
      if($e->getCode() == 1062) { // 1062 is dup key error
        $err .= "This date has already been entered for $fname $lname.<br>";
      } else {
        throw($e);
      }
    }
  }

  // Now insert or update the cash. I do not care about overwriting the cash value.
  // IF YOU GIVE a donation you are HERE!
  
  foreach($cash as $k=>$v) {
    if(!$v) continue;
    $v = preg_replace("~,~", "", $v);

    $S->query("select fname, lname from bridge where id=$k");
    [$fname, $lname] = $S->fetchrow('num');
    $names["$fname $lname"] = 1;

    $sql = "insert into weeks (fid, date, cash, lasttime) values('$k', '$wed', '$v', now()) ".
           "on duplicate key update cash='$v', lasttime=now()";
    $S->query($sql);
  }
  
  $sql = "select id, fname, lname from bridge";
  $S->query($sql);
  $r = $S->getResult();
  while([$id, $fname, $lname] = $S->fetchrow($r, 'num')) {
    $sql = "select count(*), sum(cash) from weeks where fid=$id and date <= '$wed'";
    $S->query($sql);
    [$cnt, $money] = $S->fetchrow('num');
    $money = "$" . number_format(($money ?? 0));
    $list .= "<tr><td>$fname $lname</td><td>$cnt</td><td>$money</td></tr>";
  }

  // POSTED page with totals

  foreach($names as $k=>$v) {
    $nameList .= "$k, ";
  }
  $nameList = "<span class='posted'>For: </span> " . rtrim($nameList, ', ') . "<br>";

  $err = $err ? "<br>$err" : null;
  
  echo <<<EOF
$top
<h1>Data Posted $today</h1>
$nameList
$err
<h1>Totals as of $fullDate</h1>
<table>
<thead>
<tr><th></th><th>Count</th><th>Cash</th></tr>
</thead>
<tbody>
$list
</tbody>
</table>
<a href="/bridge">Return to Home Page</a>
$footer
EOF;
  exit();
}

// First Page

$S = new $_site->className($_site);

$h->title = "Bridge";
$h->desc = "Lot of bridge playing here";
$h->css =<<<EOF
<style>
table tbody td:nth-of-type(2) { text-align: center; }
table thead th:nth-of-type(3) { text-align: right; }
table tbody td:nth-of-type(3) input { text-align: right; width: 100px; }
</style>
EOF;

$b->script = <<<EOF
<script>
var savedthis;
$("input[data-type='currency']").on({
    keyup: function() {
      savedthis = this;
      formatCurrency($(this));
    },
    blur: function() { 
      formatCurrency($(this), "blur");
    }
});


function formatNumber(n) {
  // format number 1000000 to 1,234,567
  return n.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ",")
}

function formatCurrency(input, blur) {
  // appends $ to value, validates decimal side
  // and puts cursor back in right position.
  
  // get input value
  var input_val = input.val();
  
  // don't validate empty input
  if (input_val === "") { return; }
  
  // original length
  var original_len = input_val.length;

  // initial caret position 
  var caret_pos = input.prop("selectionStart");

  // BLP 2022-01-03 -- Removed Check for decimal see section at end of file
  // If we add the section back in we need to remove the section below

  /* Start of section to remove */
  // no decimal entered
  // add commas to number
  // remove all non-digits
  input_val = formatNumber(input_val);
  /* End of section to remove */
  
  // send updated string to input
  input.val(input_val);

  // put caret back in the right position
  var updated_len = input_val.length;
  caret_pos = updated_len - original_len + caret_pos;
  input[0].setSelectionRange(caret_pos, caret_pos);
  if(blur) {
    $(savedthis).closest('tr').find("td:nth-of-type(2) input").prop('checked', true);
  }
}
</script>
EOF;

[$top, $footer] = $S->getPageTopBottom($h,$b);

$S->query("select id, fname, lname from bridge");
while([$id, $nfname, $nlname] = $S->fetchrow('num')) {
  $names .= <<<EOF
<tr><td>$nfname $nlname</td>
<td><input type='checkbox' name='id[$id]'></td>
<td><input type='text' name='cash[$id]' data-type='currency'></td></tr>
EOF;
}

echo <<<EOF
$top
<h1>$fullDate</h1>
<p>Today is $today</p>
<form method="post">
<table>
<thead>
<tr><th></th><th>Select</th><th>Cash</th><tr>
</thead>
<tbody>
$names
</tbody>
</table>
<button name="submit">Submit</button>
</form>
<a href="/bridge">Return to Home Page</a>
$footer;
EOF;

/* This is the check for a decimal value section */
/*
  // check for decimal
  if(input_val.indexOf(".") >= 0) {

    // get position of first decimal
    // this prevents multiple decimals from
    // being entered
    var decimal_pos = input_val.indexOf(".");

    // split number by decimal point
    var left_side = input_val.substring(0, decimal_pos);
    var right_side = input_val.substring(decimal_pos);

    // add commas to left side of number
    left_side = formatNumber(left_side);

    // validate right side
    right_side = formatNumber(right_side);
    
    // On blur make sure 2 numbers after decimal
    if (blur === "blur") {
      right_side += "00";
    }
    
    // Limit decimal to only 2 digits
    right_side = right_side.substring(0, 2);

    // join number by .
    //input_val = "$" + left_side + "." + right_side;
    input_val = left_side + "." + right_side;
    } else {
      // no decimal entered
      // add commas to number
      // remove all non-digits
      input_val = formatNumber(input_val);
      //input_val = "$" + input_val;
      input_val = input_val;
      // final formatting
      if (blur === "blur") {
        input_val += ".00";
      }
    }
*/
