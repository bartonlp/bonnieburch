<?php
// Show the 8mm movie.
// IMPORTANT NOTE: This file has utf8 characters. 0x2019 is an apostrophe and is invisible unless
// you do ctrl-F6 and select mode 2.

$_site = require_once(getenv("SITELOADNAME")); // Get Startup info from mysitemap.json
$S = new SiteClass($_site);

$S->title = "The Mitchell Family";
$S->banner = "<h1>$S->title</h1>";
$S->css = <<<EOF
.center {text-align: center;} .red { color: red; }
#download {
  border-radius: 10px;
  color: white;
  background: green;
  padding: 5px;
  font-size: var(--blpFontSize);
}
EOF;

[$top, $bottom] = $S->getPageTopBottom();

// POST

if($_POST['page'] == "download") {
  $file = "/var/www/bonnieburch.com/mitchell/data/Mitchells-8MM.mp4";
  
  header('Content-Description: File Transfer');
  header('Content-Type: application/octet-stream');
  header('Content-Transfer-Encoding: binary');
  header('Content-Disposition: attachment; filename="'.basename($file).'"');
  header('Expires: 0');
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  header('Content-Length: ' . filesize($file));
  ob_clean();
  flush();
  readfile($file);

  exit();
}

// If $_POST or $_GET check authorization.

if($_REQUEST['page'] == 'auth') {
  $email = $_REQUEST['email'];
  
  if(empty($email)) {
    echo <<<EOF
$top
<hr>
<h1>Sorry you are not authorized</h1>
<a href="../index.php">Return to main page</a>
<hr>
$bottom
EOF;
    exit();
  }
}

// Authorized

echo <<<EOF
$top
<hr>
<h2>The family 8mm home movie</h2>

<p style="line-height: 100%; margin-bottom: 0in">
Hi family,</p>

<p style="line-height: 100%; margin-bottom: 0in">I’m sending a
conversion from an 8 mm movie (Bonnie and Richard’s old camera) to
a digital format (.mp4). I’m hoping most, if not all, of you can
figure out how to watch it. Felix, for instance, click on the lower
left hand button that looks like a play button.  It’s my valentine
gift to the family.</p>

<p style="line-height: 100%; margin-bottom: 0in">I found the flash
drive recently in the bottom of my purse. No idea where I got it, and
I almost threw it away. Barton helped me set it up, and I was amazed
at what I saw. So many family members, especially all our parents.
It’s poorly done with shots of the ground, the sky, trees, big gaps
between scenes, etc. But you’ll see Mack McElrath and Owen Rouse
walking around as well as John Henry and Mabel. All the Mitchell
siblings in various settings, including Dan. Even a glimpse of
Fleeta.</p>

<p style="line-height: 100%; margin-bottom: 0in">The first section is
me working with my son Jeff, helping him take his first step. That
would have been in 1969. Next comes a gathering at the San Saba River
place right after Grandma and Granddad bought it, still with an
outhouse no less. Lots of family there, and I’m unsure about who
all the little kids are. Maybe you’ll see yourself.</p>

<p style="line-height: 100%; margin-bottom: 0in">Next comes the
sisters’ trip to our distant rich Bobo friends in Mississippi
(Kathleen, Ludy, LaVern and Ritchie. Maybe Iva too. Can’t
remember.) Don’t know how we’re related but Mother told me the
rich Bobos sent boxes of hand-me-down clothes routinely to the
family, always thankfully received.</p>

<p style="line-height: 100%; margin-bottom: 0in">Quite memorably
comes the infamous 90<sup>th</sup> birthday party for Grandma in her
living room, where they lit all the candles and blew them all out,
creating a plume of smoke that choked everyone in the room. Grandma
held a handkerchief to her nose the entire time, as did others. I
remember Vic and I were there and when it happened, he said, “Let’s
get the hell out of here. They’re trying to asphyxiate Grandma.”
And we hit the road.</p>

<p style="line-height: 100%; margin-bottom: 0in">There’s another
River segment with lots of folks, even with a poker game, and lastly
a gathering at the Mitchell house with almost everyone in the front
yard.</p>

<p style="line-height: 100%; margin-bottom: 0in">All in all, it gives
almost all of us the chance to see our parents in their primes. Quite
amazing 50 years later. Richard bought that camera when we couldn’t
afford it but by gosh, he did something right by providing all these
memories. 
</p>

<p style="line-height: 100%; margin-bottom: 0in">Yes, memories,
memories. That’s what old age is comprised of. I loved the film and
hope you do too.</p>

<p style="line-height: 100%; margin-bottom: 0in">Enjoy.</p>

<p style="line-height: 100%; margin-bottom: 0in">Bonnie 
</p>

  <video width="1024" height="800" controls>
    <source src="data/Mitchells-8MM.mp4" type="video/mp4">
    Your browser does not support the video tag.<br>
    <a href="data/Mitchells-8MM.mp4">View (maybe)</a><br>
  </video>
<form method="post">
<button id="download" name="page" value="download">Download File</button>
</form>
<hr>
$bottom
EOF;
