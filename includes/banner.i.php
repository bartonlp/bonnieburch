<?php
// BLP 2021-06-06 -- Modifed to use tracker in https://bartonphillips.net/tracker.php.
// The main logo image (id="logo") has the attribute "data-image" which has the image. This is
// usually from the mysitemap.json file and is "trackerImg1" and "trackerImg2", but it can be any
// image we want to use.
// $image2 has the 'normal' image and we put the &image= set to that image (usually trackerImg2).
// $image3 is the 'noscript' image and it always is a blank so we do not need to specify an image.
// The https://bartonphillips.net/js/trackerjs file uses the data-image attribute to add the image
// onto the file
// "https://bartonphillips.net/tracker.php?page=script&id=$this-LAST_ID&image={image}.
// {image} is value of the data-image attribute.
// Like wise in head.i.php the csstest is added by tracker.js. See comments in head.i.php
// BLP 2021-03-26 -- add nodb logic

return <<<EOF
<!-- body tag is added by SiteClass::getPageBanner() -->
<header>
  <a href="https://www.bartonphillips.com">
    <!-- The logo line is changes by tracker.js -->
$image1
$image2
$mainTitle
<noscript>
<p style='color: red; background-color: #FFE4E1; padding: 10px'>
$image3
Your browser either does not support <b>JavaScripts</b> or you have JavaScripts disabled, in either case your browsing
experience will be significantly impaired. If your browser supports JavaScripts but you have it disabled consider enabaling
JavaScripts conditionally if your browser supports that. Sorry for the inconvienence.</p>
</noscript>
</header>
EOF;
