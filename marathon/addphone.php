<?php
// BLP 2023-02-23 - using new approach
/*
CREATE TABLE `teams` (
  `team` int NOT NULL,
  `name1` varchar(100) NOT NULL,
  `name2` varchar(100) NOT NULL,
  `email1` varchar(100) NOT NULL,
  `email2` varchar(100) NOT NULL,
  `phone1` varchar(20) DEFAULT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `created` datetime NOT NULL,
  `lasttime` datetime NOT NULL,
  PRIMARY KEY (`team`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once getenv("SITELOADNAME");
//$_site = require_once "/var/www/site-class/includes/autoload.php";
$S = new SiteClass($_site);

// Do Auth via email first.

$email = $_GET['email'];

if(empty($email) || !$S->sql("select team from marathon.teams where email1='$email' or email2='$email'")) {
  $S->sql("insert into $S->masterdb.badplayer (ip, site, botAs, type, count, errno, errmsg, agent, created, lasttime) " .
              "values('$S->ip', '$S->siteName', 'counted', '$S->self', 1, -2, 'Not Authorized', '$S->agent', now(), now()) ".
              "on duplicate key update count=count+1, lasttime=now()");

  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

$T = new dbTables($S);

$S->css =<<<EOF
  #teams, #add { width: 100%; }
  #teams th:first-of-type { width: fit-content; padding: 0 5px; }
  .team, .name1, .name2, .phone1, .phone2, .email1, .email2 { font-size: 20px; width: fit-content; height: 40px; padding: 0 5px; border: none; cursor: pointer; }
  .team { width: 100%; }
  .email1, .email2 { width: 350px; }
  #button1, #button2, #button3 { border-radius: 5px; background: green; color: white; font-size: 25px; }
  #button2 { background: orange; }
  #button3 { background: red; }
  .error { background: red; color: white; width: fit-content; padding: 0 10px; }
  .small { width: 50px; }
EOF;

// maskedinput.js formats the phone items

$S->h_script = <<<EOF
  <script src="https://bartonphillips.net/js/maskedinput.js"></script>
  <script>
jQuery(document).ready(function($) {
  $(".phone1").mask("(999) 999-9999");
  $(".phone2").mask("(999) 999-9999");
});
  </script>
EOF;

// Submit the changes.

if($_POST['page'] == 'submit') {
  $team = $_POST['team'];
  $name1 = $_POST['name1'];
  $name2 = $_POST['name2'];
  $phone1 = $_POST['phone1'];
  $phone2 = $_POST['phone2'];
  $email1 = $_POST['email1'];
  $email2 = $_POST['email2'];
  
  $email = $_POST['email'];

  $updates = ''; // String to show who has been updated
  
  for($i=1; $i<11; ++$i) {
    // If everything is empty skip.
    
    if(empty($phone1[$i]) && empty($phone2[$i]) && empty($email1[$i]) && empty($email2[$i])) continue;

    // Get the original info.
    
    $S->sql("select team, name1, name2, email1, email2, phone1, phone2 from marathon.teams where team=$i");
    [$s_team, $s_name1, $s_name2, $e1, $e2, $p1, $p2] = $S->fetchrow('num');

    $str = '';

    // Is the new info different from the old info?

    if($team[$i] != $s_team) $str .= "team='$team[$i]',";
    if($name1[$i] != $s_name1) $str .= "name1='$name1[$i]',";
    if($name2[$i] != $s_name2) $str .= "name2='$name2[$i]',";
    
    if($phone1[$i] != $p1) $str .= "phone1='$phone1[$i]',";
    if($phone2[$i] != $p2) $str .= "phone2='$phone2[$i]',";
    if($email1[$i] != $e1) $str .= "email1='$email1[$i]',";
    if($email2[$i] != $e2) $str .= "email2='$email2[$i]',";

    // If no change do next team

    if(empty($str)) continue;

    $msg = rtrim($str, ","); // strip off the trailing ','
    $updates .= "$msg<br>";

    // UPDATE the table.
    
    $S->sql("update marathon.teams set $str lasttime=now() where team=$i");
  }

  $S->title = "Add Phone POST";
  $S->banner = "<h1>$S->title</h1>";

  [$top, $footer] = $S->getPageTopBottom();
  
  echo <<<EOF
$top
<hr>
<h2>Updated</h2>
<div>$updates</div>
<br>
<a href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$footer
EOF;
  
  exit();
}

if($_POST['add-new']) {
  $team = $_POST['team'];
  if($S->sql("select * from marathon.teams where team=$team")) {
    $msg = "<h1 class='error'>That team already exists ($team)</h1>";
  } else {
    $S->banner = "<h1>Add Team</h1>";
    [$top, $footer] = $S->getPageTopBottom();
    echo <<<EOF
$top
<form method="post">
<table id="add" border="1">
<thead>
<tr><th>team</th><th>name1</th><th>name2</th><th>phone1</th><th>phone2</th><th>email1</th><th>email2</th></tr>
</thead>
<tbody>
<tr>
<td>$team</td>
<td><input class="name1" data-form-type='other' name="name1"></td>
<td><input class="name2" data-form-type='other' name="name2"></td>
<td><input class="phone1" data-form-type='other' name="phone1"></td>
<td><input class="phone2" data-form-type='other' name="phone2"></td>
<td><input class="email1" data-form-type='other' name="email1"></td>
<td><input class="email2" data-form-type='other' name="email2"></td>
</tr>
</tbody>
</table>
<input type="hidden" name="team" value="$team">
<button id="button1" type="submit" name="add" value="true">Add Item</button><br>
<button id="button3" type="sumbit" name="noadd" value="true">Don't Add</button>
</form>
$footer
EOF;
  }
  exit();
}

// Actually add to the teams table

if($_POST['add']) {
  extract($_POST);

  $S->sql("insert into marathon.teams (team, name1, name2, phone1, phone2, email1, email2, created, lasttime) ".
          "values('$team', '$name1', '$name2', '$phone1', '$phone2', '$email1', '$email2', now(), now())");

  $msg = "<h1>Team $team posted to database</h1>";
  //header("Location: ./test2.php");
  //exit();
}

// Review delete

if($_POST['prev_delete']) {
  $team = $_POST['team'];

  $query = "select team, name1, name2, phone1, phone2, email1, email2 from marathon.teams where team=$team";
  $tbl = $T->maketable($query, ['attr'=>['id'=>'teams', 'border'=>'1']])[0];
  if($tbl) {
    $S->banner = "<h1>Review Delete</h1>";
    [$top, $footer] = $S->getPageTopBottom();
    echo <<<EOF
$top
$tbl
<br>
<form method="post">
<input type="hidden" name="team" value="$team">
Is this the team you want to delete
<button id="button1" type="submit" name="delete" value="true">YES Delete it</button>
<button id="button2" type="submit" name="no-delete" value="true">NO Return and let me try again</button>
</form>
$footer
EOF;
    exit();
  } else {
    $msg = "<h1 class='error'>Team does not exist ($team)</h1>";
  }
}

// Delete a team

if($_POST['delete']) {
  $team = $_POST['team'];

  if($S->sql("delete from marathon.teams where team=$team")) {
    $msg = "<h1>Deleted team $team</h1>";
  } else {
    $msg = "<h1>No team with that number ($team)</h1>";
  }
}

// Callback tor $T. Make the input fields.

function callback(&$row, &$desc) {
  $team = $row['team'];
  
  $row['team'] = "<input class='team' data-form-type='other' name='team[$team]' value='". $row["team"]."'>";
  $row['name1'] = "<input class='name1' data-form-type='other' name='name1[$team]' value='". $row["name1"]."'>";
  $row['name2'] = "<input class='name2' data-form-type='other' name='name2[$team]' value='". $row["name2"]."'>";
  $row['phone1'] = "<input class='phone1' data-form-type='other' name='phone1[$team]' value='". $row["phone1"]."'>";
  $row['phone2'] = "<input class='phone2' data-form-type='other' name='phone2[$team]' value='". $row["phone2"]."'>";
  $row['email1'] = "<input class='email1' data-form-type='other' name='email1[$team]' value='". $row["email1"]."'>";
  $row['email2'] = "<input class='email2' data-form-type='other' name='email2[$team]' value='". $row["email2"]."'>";
}

$tbl = $T->maketable("select team, name1, name2, email1, email2, phone1, phone2 from marathon.teams", ['callback'=>'callback', 'attr'=>['id'=>'teams', 'border'=>'1']])[0];

$S->title = "Edit Team Info";
$S->banner = "<h1>$S->title</h1>";

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<hr>
<div>$msg</div>
<form method='post'>
$tbl
<input type='hidden' name='email' value='$email'>
<button id="button1" type='submit' name='page' value='submit'>Preview Updates</button>
</form>
<br>
<form method='post'>
Enter team number to add: <input class="small" type="text" name="team"><br>
<button id="button2" type='submit' name="add-new" value="true">Add Team</button>
</form>
<br>
<form method='post'>
Enter the team number you want to delete: <input class="small" type="text" name="team"><br>
<button id="button3" type="submit" name="prev_delete" value="true">Preview Delete Item</button>
</form>
<br>
<a href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$footer
EOF;
