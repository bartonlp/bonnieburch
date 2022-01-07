<?php
// Edit the names in the bridge table
/*
CREATE TABLE `bridge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(254) DEFAULT NULL,
  `fname` varchar(255) DEFAULT NULL,
  `lname` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `lasttime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
*/

$_site = require_once(getenv("SITELOADNAME"));
ErrorClass::setDevelopment(true);

$S = new $_site->className($_site);

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
<h2>Posted edited name as "$fname $lname"</h2>
<a href="/bridge/editnames.php">Return to Edit Names</a>
$footer
EOF;
  
  exit();
}

if($_GET['page'] == 'edit') {
  $id = $_GET['id'];
  $fname = $_GET['fname'];
  $lname = $_GET['lname'];

  $h->title = "Edit Name";
  $h->banner = "<h1>Edit Bridge Name</h1>";
  
  [$top, $footer] = $S->getPageTopBottom($h, $b);

  echo <<<EOF
$top
<h2>Edit the name and then 'Submit'</h2>
<form method="post">
Selected Name <input type="text" name="fname" value="$fname"><input type="text" name="lname" value="$lname"><br>
<input type="hidden" name="id" value="$id">
<button type="submit" name="page" value="post">Submit</button>
</form>
$footer
EOF;
  exit();
}

$S->query("select id, name, fname, lname from bridge");
while([$id, $name, $fname, $lname] = $S->fetchrow('num')) {
  $lines .= "<tr><td class='name' data-id='$id'>$name</td><td class='dontshow fname'>$fname</td><td class='dontshow lname'>$lname</td></tr>";
}

$h->title = "Select Name";
$h->banner = "<h1>Select Bridge Name To Edit</h1>";
$h->css =<<<EOF
<style>
  .dontshow { display: none; }
</style>
EOF;

$b->script =<<<EOF
<script>
  $(".name").on("click", function() {
    const id = $(this).attr("data-id");
    const tr = $(this).closest('tr');
    const fname = $(".fname", tr).text();
    const lname = $(".lname", tr).text();

    //console.log("name: "+name+", fname: "+fname+", lname: "+lname);

    location.replace("editnames.php?page=edit&id="+id+"&fname="+fname+"&lname="+lname);
  });             
</script>
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

echo <<<EOF
$top
<p>Click on the name you want to edit.</p>
<table id="names">
<thead>
<tr><th>Name</th><th class="dontshow">fname</th><th class="dontshow">lname</th></tr>
</thead>
<tbody>
$lines
</tbody>
</table>
<br>
<a href="/bridge">Return to Home Page</a>
$footer
EOF;
