<?php
$_site = require_once(getenv("SITELOADNAME"));

// If a post already had a valid score

if($_POST['page'] == 'postanyhow') {
  extract($_POST); // turn post into values

  $S = new SiteClass($_site);
  $h->title = 'Post Data';
  
  $h->css = 'select, input { font-size: 20px; };';

  [$top, $footer] = $S->getPageTopBottom($h);

  if($no) {
    echo <<<EOF
$top
<hr>
<h1>Data will not be posted</h1>
<a href='marathon.php?page=auth&email=$email'>Return to main page</a>
<hr>
$footer
EOF;
    exit();
  }

  $S->query("select score from scores where fkteam='$team' and month='$month'");
  $oldscore = $S->fetchrow('num')[0];
  
  $S->query("update scores set score=$score, lasttime=now() where fkteam=$team and month='$month'");

  echo <<<EOF
$top
<hr>
<h1>Date posted</h1>
<h3>Old Score was $oldscore. Score is now $score.</h1>
<a href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$footer
EOF;
  exit();
}

// Try to post a score

if($_POST['page'] == 'post') {
  $month = $_POST['month'];
  $team = $_POST['team'];
  $score = $_POST['score'];
  $email = $_POST['email'];
  
  $S = new SiteClass($_site);
  $h->css =<<<EOF
select, button { font-size: 20px; }
button { border-radius: 5px; }
button[name='yes'] { background: green; color: white }
button[name='no'] { background: red; color: white }
EOF;

  [$top, $footer] = $S->getPageTopBottom($h);

  $S->query("select score, created from scores where fkteam='$team' and month='$month'");
  [$oldscore, $created] = $S->fetchrow('num');

  if($created !== null) {
    echo <<<EOF
$top
<hr>
<h1>$month already has a score of $oldscore associated with it.</h1>
<h3>Do you want to enter the new score of $score anyway?</h2>
<form method='post'>
<input type='hidden' name='page' value='postanyhow'>
<input type='hidden' name='month' value='$month'>
<input type='hidden' name='team' value='$team'>
<input type='hidden' name='score' value='$score'>
<input type='hidden' name='email' value='$email'>
<button type='submit' name='yes' value='yes'>Yes</button> <button type='submit' name='no' value='no'>No</button>
</form>
<hr>
$footer
EOF;
    exit();
  }

  // created was null so this is the first update so set created and lasttime to real values not
  // null.
  
  $S->query("update scores set score=$score, created=now(), lasttime=now() where fkteam='$team' and month='$month'");

  echo <<<EOF
$top
<hr>
<p>Your score of $score was posted to team $team for the month of $month. Thank you.</p>
<a href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$footer
EOF;
  exit();
}

$S = new SiteClass($_site);

$h->title = "Enter Scores";
$h->banner = "<h1>Enter Scores</h1>";
$h->css =<<<EOF
select, input { font-size: 20px; }
input { width: 100px; text-align: right; }
button { font-size: 20px; border-radius: 5px; background: green; color: white; }
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

$team = $_GET['team'];
$name1 = $_GET['name1'];
$name2 = $_GET['name2'];
$email = $_GET['email'];

echo <<<EOF
$top
<hr>
<form method="post">
<p>Your are team $team of $name1 and $name2.<br>
Select the month you want to enter and the score</p>
<table>
<tr><td>Select Month:</td>
<td><select name="month">
<option>September</option>
<option>October</option>
<option>November</option>
<option>December</option>
<option value="January 1">January (1)</option>
<option value="January 2">January (2)</option>
<option value="February 1">February (1)</option>
<option value="February 2">February (2)</option>
<option>March</option>
<option>April</option>
<option>May</option>
</select><td><tr>
<tr><td>Enter Score:</td><td><input type='number' required name='score'></td></tr>
</table>
<input type='hidden' name='team' value='$team'>
<input type='hidden' name='email' value='$email'>
<button type='submit' name='page' value='post'>Continue</button>
</form>
<br>
<a href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$footer
EOF;
