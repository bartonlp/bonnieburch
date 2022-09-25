<?php
// Edit the names in the bridge table

require("startup.i.php");

$S = new $_site->className($_site);

$h->css =<<<EOF
  .dontshow { display: none; }
  button { font-size: var(--blpFontSize); border-radius: 10px; background: green; color: white; }
  .delete { background: red; }
  input { font-size: var(--blpFontSize); }
EOF;

if($_POST['page'] == "delete") {
  $h->title = "Delete Name";
  $h->banner = "<h1>$h->title</h1>";
  [$top, $footer] = $S->getPageTopBottom($h);
  
  $fname = $_POST['fname'];
  $lname = $_POST['lname'];
  $id = $_POST['id'];

  $msg = '';
  
  if($S->query("select * from weeks where fid='$id'")) {
    $msg = "<h2>Can't delete $fname $lname because there are Attendance Records</h2>";
  }
  if($S->query("select * from money where fid='$id'")) {
    $msg = "<h2>Can't delete $fname $lname because there are Donation Records</h2>";
  }
  if($msg == '') {
    $S->query("delete from bridge where id='$id'");
    $msg = "<h2>The name $fname $lname has been deleted</h2><p>There were no Attendance or Donation Records</p>";
  }

  echo <<<EOF
$top
$msg
<hr>
<a href="editBridgeNames.php">Return to Edit Names</a><br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}

if($_POST['page'] == "add") {
  $h->title = "Add New Name";
  $h->banner = "<h1>$h->title</h1>";
  [$top, $footer] = $S->getPageTopBottom($h);
  
  $fname = $_POST['fname'];
  $lname = $_POST['lname'];
  $name = "$fname $lname";

  $msg = "New Name $name Posted";
  
  try {
    $S->query("insert into bridge (name, fname, lname, created, lasttime) values('$name', '$fname', '$lname', now(), now())");
  } catch(Exception $e) {
    if($e->getCode() == 1062) { // 1062 is dup key error
      $msg = "The name $name has already been entered.";
    } else {
      throw($e);
    }
  }
  echo <<<EOF
$top
<h1>$msg</h1>
<a href="editBridgeNames.php">Return to Edit Bridge Names</a><br>
<a href="bridgeclub.php">Return to Home Page</a>
$footer
EOF;

  exit();
}

if($_POST['page'] == "post") {
  $id = $_POST['id'];
  $fname = $_POST['fname'];
  $lname = $_POST['lname'];
  
  $h->title = "Posted Name";
  $h->banner = "<h1>Edited Name Posted</h1>";
  [$top, $footer] = $S->getPageTopBottom($h, $b);
  
  $S->query("update bridge set name='$fname $lname', fname='$fname', lname='$lname', lasttime=now() where id=$id");
  echo <<<EOF
$top
<hr>
<h2>Posted edited name as "$fname $lname"</h2>
<a href="editBridgeNames.php">Return to Edit Names</a><br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
  
  exit();
}

if($_GET['page'] == 'edit') {
  $id = $_GET['id'];
  $fname = $_GET['fname'];
  $lname = $_GET['lname'];

  $h->title = "Edit Name";
  $h->banner = "<h1>$h->title</h1>";

  [$top, $footer] = $S->getPageTopBottom($h);

  echo <<<EOF
$top
<hr>
<h2>Edit the name and then 'Submit'</h2>
<form method="post">
Selected Name <input type="text" name="fname" value="$fname" required><input type="text" name="lname" value="$lname" required><br>
<input type="hidden" name="id" value="$id">
<button type="submit" name="page" value="post">Submit</button>
</form>
<hr>
<h2>Delete $fname $lname</h2>
<form method="post">
<button class="delete" type="submit" name='page' value='delete'>Delete $fname $lname</button>
<input type='hidden' name='fname' value='$fname'>
<input type='hidden' name='lname' value='$lname'>
<input type="hidden" name="id" value="$id">
</form>
<hr>
<a href="editBridgeNames.php">Return to Edit Bridge Names</a><br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
  exit();
}

$S->query("select id, name, fname, lname from bridge order by lname");
while([$id, $name, $fname, $lname] = $S->fetchrow('num')) {
  $lines .= "<tr><td class='name' data-id='$id'>$name</td><td class='dontshow fname'>$fname</td><td class='dontshow lname'>$lname</td></tr>";
}

$h->title = "Select Name";
$h->banner = "<h1>Add, Edit or Delete Player's Names</h1>";

$b->inlineScript =<<<EOF
  $(".name").on("click", function() {
    const id = $(this).attr("data-id");
    const tr = $(this).closest('tr');
    const fname = $(".fname", tr).text();
    const lname = $(".lname", tr).text();

    //console.log("name: "+name+", fname: "+fname+", lname: "+lname);

    location.replace("editBridgeNames.php?page=edit&id="+id+"&fname="+fname+"&lname="+lname);
  });             
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

echo <<<EOF
$top
<hr>
<p>Click on the name you want to edit or delete. Go to the bottom of the page to add a new name.</p>
<table border="1" id="names">
<thead>
<tr><th>Name</th><th class="dontshow">fname</th><th class="dontshow">lname</th></tr>
</thead>
<tbody>
$lines
</tbody>
</table>
<h2>Add a New Name</h2>
<form method='post'>
New First Name <input type='text' name='fname' required>&nbsp;New Last Name <input type'text' name='lname' required>
<button type='submit' name='page' value='add'>Add New Name</button>
</form>
<br>
<a href="bridgeclub.php">Return to Home Page</a>
<hr>
$footer
EOF;
