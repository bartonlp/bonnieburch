<?php
// This file is a substitute for Sitemap.xml. This file is RewriteRuled in
// .htaccess to read Sitemap.xml and output it. It also writes a record into the bots tables and
// logagent.

$_site = require_once(getenv("SITELOADNAME"));
$_site->noTrack = true;
$S = new Database($_site);

$map = BOTS_SITEMAP;

if(!file_exists($S->path . "/Sitemap.xml")) {
  echo "<h1>404 - FILE NOT FOUND</h1>";
  exit();
}

$sitemap = file_get_contents($S->path."/Sitemap.xml");
echo $sitemap . "# From sitemap.php\n";

if($S->isMe()) return;

// Get info from myip
// Check to see if this ip is in the myip table.

$ip = $S->ip;
$agent = $S->agent;

try {
  // BLP 2021-12-26 -- robots is 4 for insert and robots=robots|8 for update.

  $S->sql("insert into $S->masterdb.bots (ip, agent, count, robots, site, creation_time, lasttime) ".
            "values('$ip', '$agent', 1, $map, '$S->siteName', now(), now())");
} catch(Exception $e) {
  if($e->getCode() == 1062) { // duplicate key
    $S->sql("select site from $S->masterdb.bots where ip='$ip'");

    $who = $S->fetchrow('num')[0];

    if(!$who) {
      $who = $S->siteName;
    }
    if(strpos($who, $S->siteName) === false) {
      $who .= ", $S->siteName";
    }
    $S->sql("update $S->masterdb.bots set robots=robots|$map, count=count+1, site='$who', lasttime=now() ".
              "where ip='$ip' and agent='$agent'");
  } else {
    error_log("robots: ".print_r($e, true));
  }
}

// BLP 2021-11-12 -- 4 for sitemap
// BLP 2021-12-26 -- bots2 primary key is 'ip, agent, date, site, which'.

$S->sql("insert into $S->masterdb.bots2 (ip, agent, date, site, which, count, lasttime) ".
          "values('$ip', '$agent', now(), '$S->siteName', $map, 1, now()) ".
          "on duplicate key update count=count+1, lasttime=now()");

// Insert or update logagent

$S->sql("insert into $S->masterdb.logagent (site, ip, agent, count, created, lasttime) values('$S->siteName', '$ip', '$agent', 1, now(), now()) ".
          "on duplicate key update count=count+1, lasttime=now()");
