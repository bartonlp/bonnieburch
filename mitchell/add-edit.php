<?php
$_site = require_once(getenv("SITELOADNAME"));
$S = new SiteClass($_site);
$T = new dbTables($S);

if($_POST['Delete']) {
  extract($_POST);
  if(!$S->query("select fname from bonnie.family where fname='$fname' and lname='$lname'")) {
    $S->banner = "<h1>$fname $lname not found in database.</h1>";
  } else {
    $S->query("delete from bonnie.family where fname='$fname' and lname='$lname'");
    $S->banner = "<h1>$fname $lname Deleted</h1>";
  }
  
  [$top, $footer] = $S->getPageTopBottom();
  
  echo <<<EOF
$top
<hr>
<a href="add-edit.php?page=auth&email=$xemail">Return to Add/Edit/Delete</a><br>
<a href="family.php?page=auth&email=$xemail">Return to Mitchell Family</a>
<hr>
$footer
EOF;
  exit();
}

if($_POST['submit']) {
  extract($_POST);

  $S->query("insert into bonnie.family (fname, lname, phone, email, address, created, lasttime) ".
            "values('$fname', '$lname', '$phone', '$email', '$address', now(), now()) ".
            "on duplicate key update phone='$phone', email='$email', address='$address', lasttime=now()");

  $S->title = "Posted";
  $S->banner = "<h1>$S->title</h1>";
  
  [$top, $footer] = $S->getPageTopBottom();

  echo <<<EOF
$top
<hr>
<p>The following data was poster:</p>
<ul>
<li>Fname: $fname
<li>Lname: $lname
<li>Phone: $phone
<li>Email: $email
<li>Address: $address
</ul>
<a href="add-edit.php?page=auth&email=$xemail">Return to Add/Edit/Delete</a><br>
<a href="family.php?page=auth&email=$xemail">Return to Mitchell Family</a>
<hr>
$footer
EOF;
  exit();
}

if($_POST['New']) {
  $S->title = "Add";
  $S->banner = "<h1>$S->title</h1>";
  doit($_POST);
  exit();
}

if($_GET['fname']) {
  $fname = $_GET['fname'];
  $lname = $_GET['lname'];
  $xemail = $_GET['email'];

  $sql = "select fname, lname, phone, email, address from bonnie.family where fname='$fname' and lname='$lname'";
  $S->query($sql);
  
  $row = $S->fetchrow('assoc');

  $S->title = "Edit";
  $S->banner = "<h1>$S->title</h1>";
  doit(array_merge(['xemail'=>$xemail], $row));
  exit();
}

function doit($info) {
  global $S;
  extract($info);
  // maskedinput.js formats the phone items

  $S->h_script = <<<EOF
<script src="https://bartonphillips.net/js/allnatural/js/maskedinput.js"></script>
<script>
jQuery(document).ready(function($) {
  $("#phone").mask("(999) 999-9999");
});
</script>
EOF;

  [$top, $footer] = $S->getPageTopBottom();
  
  echo <<<EOF
$top
<hr>
<form action="add-edit.php" method="post">
<table>
<tbody>
<tr><td>Fname</td><td><input type='text' data-form-type='other' name='fname' value='$fname'></td></tr>
<tr><td>Lname</td><td><input type='text' data-form-type='other' name='lname' value='$lname'></td></tr>
<tr><td>Phone</td><td><input type='text' data-form-type='other' id='phone' name='phone' value='$phone'></td></tr>
<tr><td>Email</td><td><input type='text' data-form-type='other' name='email' value='$email'></td></tr>
<tr><td>Address</td><td><input type='text' data-form-type='other' name='address' value='$address'></td></tr>
</tbody>
</table>
<input type='hidden' name='xemail' value='$xemail'>
<button type="submit" name="submit" value="submit">Submit</button>
</form>
<a href="add-edit.php?page=auth&email=$xemail">Return to Add/Edit/Delete</a><br>
<a href="family.php?page=auth&email=$xemail">Return to Mitchell Family</a>
<hr>
$footer
EOF;
  exit();
}

//******************
// Check Authorized

$xemail = $_GET['email'];

if(empty($xemail) || !$S->query("select fname, lname from bonnie.family where email='$xemail'")) {
  error_log("$S->self: $S->ip, $S->siteName, 'NOT_AUTH', 'Not Authorized', $S->agent");

  echo "<h1>Not Authorized</h1><p>Go Away</p>";  
  exit();
}

$S->title = "Add/Edit/Delete";
$S->banner = "<h1>$S->title</h1>";

[$top, $footer] = $S->getPageTopBottom();

function addcheck(&$row, &$desc) {
  global $xemail;
  
  $fname = $row['fname'];
  $lname = $row['lname'];
  $row['Select'] = "<a href='add-edit.php?page=auth&email=$xemail&fname=$fname&lname=$lname'>Edit</a>";
}

$sql ="select 'Select', fname, lname, email, phone, address from bonnie.family order by lname";
$tbl = $T->maketable($sql, ['callback'=>'addcheck', 'attr'=>['border'=>'1', 'id'=>'tbl']])[0];

echo <<<EOF
$top
<hr>
<p>Select entry to edit.</p>
$tbl
<hr>
<form method="POST">
<input type='hidden' name='xemail' value='$xemail'>
<button type="submit" name="New" value="new">Add Record</button>
</form>
<hr>
<form method="POST">
<p>Delete the record for:</p>
Fname<input type="text" data-form-type='other' name="fname"><br>
Lname<input type="text" data-form-type='other' name="lname"><br>
<input type='hidden' name='xemail' value='$xemail'>
<button type="submit" name="Delete" value="del">Delete Record</button>
<hr>
<a href="family.php?page=auth&email=$xemail">Return to Mitchell Family</a>
<hr>
$footer
EOF;
