# Wednesday Bridge Club App for Bonnie Burch

This project is made of of the following files which are in the **/var/www/bonnieburch.com directory**:

```
    addAttendance.php
    addDonation.js
    addDonation.php
    bonnie3.jpg
    editAttendance.php
    editBridgeNames.php
    editDonation.php
    index.php
    mysitemap.json
    readBridgeRaw.php
    showAttendanceTotals.php
    showDonationTotals.php
    spreadAttendance.php
    spreadDonation.php
    startup.i.php
```

The LIVE location is <b>https://bonnieburch.com</b>. There is an <i>index.php</i> there.

The WORKING location is <b>https://www.bartonphillips.com/bridgetest</b>. Also an <i>index.php</i>.

The WORKING directory MUST always push to github and the LIVE directory should always pull form github.
There is a <i>.gitignore</i> in the WORKING directory and it says do not push 
<i>mysitemap.json</i> or <i>.gitignore</i>. Therefore if I do a 'push --force' from LIVE and NEVER do a 'pull' from
WORKING everything still works OK. I have the modified <i>mysitemap.json</i> on github and never pollute WORKING with it.

