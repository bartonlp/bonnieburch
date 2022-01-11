<?php
// Edit the names in the bridge table

require("startup.i.php");

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
<hr>
<h2>Posted edited name as "$fname $lname"</h2>
<a href="editBridgeNames.php">Return to Edit Names</a><br>
<a href="index.php">Return to Home Page</a>
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
  $h->banner = "<h1>Edit Bridge Name</h1>";
  $h->css = "<style>button { background: green; color: white; border-radius: 10px; }</style>";

  [$top, $footer] = $S->getPageTopBottom($h);

  echo <<<EOF
$top
<hr>
<h2>Edit the name and then 'Submit'</h2>
<form method="post">
Selected Name <input type="text" name="fname" value="$fname"><input type="text" name="lname" value="$lname"><br>
<input type="hidden" name="id" value="$id">
<button type="submit" name="page" value="post">Submit</button>
</form>
<br>
<a href="editBridgeNames.php">Return to Edit Bridge Names</a><br>
<a href="index.php">Return to Home Page</a>
<hr>
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

    location.replace("editBridgeNames.php?page=edit&id="+id+"&fname="+fname+"&lname="+lname);
  });             
</script>
EOF;

[$top, $footer] = $S->getPageTopBottom($h, $b);

echo <<<EOF
$top
<hr>
<p>Click on the name you want to edit.</p>
<table border="1" id="names">
<thead>
<tr><th>Name</th><th class="dontshow">fname</th><th class="dontshow">lname</th></tr>
</thead>
<tbody>
$lines
</tbody>
</table>
<br>
<a href="index.php">Return to Home Page</a>
<hr>
$footer
EOF;
