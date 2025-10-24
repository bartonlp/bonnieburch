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
$S = new SiteClass($_site);
//vardump("S", $S);
// Do Auth via email first.

$email = $_GET['email'];

$T = new dbTables($S);

$S->css =<<<EOF
  #teams, #add { width: 100%; }
  #teams th:first-of-type { width: fit-content; padding: 0 5px; }

  .team, .name1, .name2, .phone1, .phone2, .email1, .email2 {
    font-size: 20px;
    width: fit-content;
    height: 40px;
    padding: 0 5px;
    cursor: pointer;
  }
  .email1, .email2 { width: 350px; }
  #button1, #button2, #button3, #button4 { border-radius: 5px; color: white; font-size: 25px; }
  #button1, #button4 { background: green; }
  #button2 { background: orange; }
  #button3 { background: red; }
  .error { background: red; color: white; width: fit-content; padding: 0 10px; }
  .small { width: 100px; }
EOF;

$S->b_inlineScript = <<<EOF

$("#teams .name1, #teams .name2, #teams .phone1, #teams .phone2, #teams .email1, #teams .email2")
.attr("contenteditable", "true");

$("#button1").on('click', function(e) {
  e.preventDefault();

  let rows = [];

  $("table tr").each(function() {
    let cols = $(this).find("td");
    if(cols.length){
      let team  = $(cols[0]).text().trim();
      let name1 = $(cols[1]).text().trim();
      let name2 = $(cols[2]).text().trim();
      let email1 = $(cols[3]).text().trim();
      let email2 = $(cols[4]).text().trim();
      let phone1 = $(cols[5]).text().trim();
      let phone2 = $(cols[6]).text().trim();
      rows.push({team:team, name1:name1, name2:name2, email1:email1, email2:email2, phone1:phone1, phone2:phone2});
    }
  });

  $.ajax({
    url: "addphone.php",
    method: "POST",
    data: { page: 'test', email: 'bonnieburch2015@gmail.com', rows: JSON.stringify(rows) },
    dataType: 'html'
  })
  .done(function(data) {
    document.open();
    document.write(data);     // server-rendered HTML page
    document.close();
  });
});
EOF;

// Update the page of the main page

if($_POST['page']) {
  $rows = json_decode($_POST['rows'], true);
  $email = $_POST['email'];
  //error_log("email" . print_r($email, true));
  
  foreach($rows as $r) {
    $S->sql("update marathon.teams set name1='{$r['name1']}', name2='{$r['name2']}', email1='{$r['email1']}',
             email2='{$r['email2']}', phone1='{$r['phone1']}', phone2='{$r['phone2']}', lasttime=now()
             where team='{$r['team']}'");
  };

  $S->banner = "<h1>Updated</h1>";

  $S->css = <<<EOF
body { padding: 5px; }
#headerImage2 { display: none; }
EOF;
  
  [$top, $bottom] = $S->getPageTopBottom();

  echo <<<EOF
$top
<hr>
<a href='addphone.php?email=$email'>Return To Page</a>
<hr>
$bottom
EOF;

  exit;
}
  
// Add-new. This is for 'Add Item' and 'Don't Add'

if($_POST['add-new']) {
  $S->sql("select team from marathon.teams order by team desc limit 1");
  [$team] = $S->fetchrow('num');
  ++$team;
  
  $S->banner = "<h1>Add Team</h1>";
  [$top, $bottom] = $S->getPageTopBottom();

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
<button id="button4" type="submit" name="add" value="true">Add Item</button><br>
<button id="button3" type="sumbit" name="noadd" value="true">Don't Add</button>
</form>
$bottom
EOF;
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
    [$top, $bottom] = $S->getPageTopBottom();

    echo <<<EOF
$top
$tbl
<br>
<form method="post">
<input type="hidden" name="team" value="$team">
Is this the team you want to delete
<button id="button4" type="submit" name="delete" value="true">YES Delete it</button>
<button id="button2" type="submit" name="no-delete" value="true">NO Return and let me try again</button>
</form>
$bottom
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

$S->sql("select team from marathon.teams order by team desc limit 1");
[$num] = $S->fetchrow('num');

$tbl = $T->maketable("select team, name1, name2, email1, email2, phone1, phone2 from marathon.teams",
                     ['attr'=>['id'=>'teams', 'border'=>'1']], true)[0];

$S->title = "Edit Team Info";
$S->banner = "<h1>$S->title</h1>";

if(empty($email) || !$S->sql("select team from marathon.teams where email1='$email' or email2='$email'")) {
  $S->sql("insert into $S->masterdb.badplayer (ip, site, botAs, type, errno, errmsg, agent, created, lasttime)
          values('$S->ip', '$S->siteName', 'counted', '$S->self', -2, 'Not Authorized', '$S->agent', now(), now())");

  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

[$top, $bottom] = $S->getPageTopBottom();

// This is the main page

echo <<<EOF
$top
<hr>
<div>$msg</div>
$tbl
<button id="button1" type='submit' name='page' value='submit'>Updates</button>

<br><br>
<form method='post'>
<button id="button2" type='submit' name="add-new" value="true">Add Team</button>
</form>

<br>
<form method='post'>
Enter the team number you want to delete:
<input class='small' type="text" name="team" placeholder="1 to $num"><br>
<button id="button3" type="submit" name="prev_delete" value="true">Preview Delete Item</button>
</form>
<br>
<a href="marathon.php?page=auth&email=$email">Return to main page</a>
<hr>
$bottom
EOF;
