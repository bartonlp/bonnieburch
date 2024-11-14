<?php

$_site = require_once getenv("SITELOADNAME");

$S = new SiteClass($_site);

if(isset($_POST['submit'])) {
  vardump("POST", $_POST);
  exit();
}
          
$S->css =<<<EOF
.invalid { background: red; color: white; }
EOF;

$S->h_script = <<<EOF
  <script>
function clearInvalid(self) {
  $(self).removeClass('invalid');
  $(self).data('invalid', false);
}

// Set invalid

function setInvalid(self) {
  $(self).addClass('invalid');
  $(self).data('invalid', true);
}

// Date logic

function doDate(self, e) {
  const original = $(self).val();
  const inputValue = original.replace(/\D/g, '');
  let day;

  // We have removed any /s so d=1, dd=2, m=3, mm=4, y=5, yy=6, yyy=7 and yyyy=8

  if(e.key == "Backspace") {
    self.value = self.value.slice(0);
    return false;
  }

  // Validation before formatting

  // Check if self year is before the current date or after the current year

  const propName = $(self).attr("when");
  const month = parseInt(inputValue.slice(0, 2));

  let sliceValue = parseInt(inputValue.slice(0, 1));

  if(sliceValue > 1) {
    setInvalid(self);
    return false;
  }

  sliceValue = parseInt(inputValue.slice(0, 2));

  if(sliceValue > 12) {
    setInvalid(self);
    return false;
  }

  const dayTens = parseInt(inputValue.slice(2, 3));

  if(dayTens && dayTens > daysInMonth[month -1].slice(0, 1)) {
    setInvalid(self);
    return false;
  }

  day = parseInt(inputValue.slice(2, 4));

  if(day && day > daysInMonth[month -1]) {
    setInvalid(self);
    return false;
  }

  if(month !== 0 && propName === "current") {
    const currMo = new Date().getMonth() + 1; // Month 1-12, getMonth() is zero bassed.
    if(month !== currMo) {
      setInvalid(self);
      return false;
    }
  }
  
  if(inputValue.length === 8) {
    const currDate = new Date().getFullYear();
    let startDate, endDate;

    if(propName === "after") {
      startDate = currDate;
      endDate = 2050;
    } else if(propName === "before") {
      startDate = 1900;
      endDate = currDate;
    } else if(propName === "current") {
      startDate = currDate;
      endDate = currDate + 1;
    }

    const year = parseInt(inputValue.slice(4));

    if(year < startDate || year >= endDate) {
      setInvalid(self);
      return false;
    }

    // Now check for leep year

    let leap;

    if(year % 4 === 0 && (year % 100 !== 0 || year % 400 === 0)) {
      leap = true;
    } else {
      leap = false;
    }

    // Check the day again

    if(month === 2) {
      if(leap && day > 29) {
        setInvalid(self);
        return false;
      } else if(!leap && day > 28) {
        setInvalid(self);
        return false;
      }
    } else if(day > daysInMonth[month -1]) {
        setInvalid(self);
        return false;
    }
  }
  return true;
}
    
const dateRegex = /^(0[1-9]|1[0-2])\/(0[1-9]|1\d|2\d|3[01])\/(19|20)\d{2}$/; 
const daysInMonth = ["31", "29", "31", "30", "31", "30", "31", "31", "30", "31", "30", "31"];
//const beforeAfter = { "ExpirationDate": "after", "DateOfBirth": "before", "SigDate": "current" };

jQuery(document).ready(function($) {
  $(".phone").mask("(999) 999-9999");
  $(".ss").mask("999-99-9999");
  $(".date").mask("99/99/9999");
  $(".zip").mask("99999");

  $(".date").on("keyup", function(e) {
    const original = $(this).val();
    const inputValue = original.replace(/\D/g, '');;

    if(doDate(this, e) === true) {
      clearInvalid(this);
    }

    if(inputValue.length === 8 && doDate(this, e)) {
      // Additional check to clear invalid class if the full date is valid

      if(dateRegex.test(original)) {
        clearInvalid(this);
      }
    }
  });

  $('.ss, .phone, .zip').on("keyup", function(e) {
    clearInvalid(this);
  });
    
  $('.phone, .ss, .date, .money, .zip').on('blur', function(e) {
    if($(this).data('invalid') === true) return;
  
    let parts = $(this).val().split(".");
    let dec = parts[1]

    let inputValue = $(this).val().replace(/\D/g, '');
    let len;

    if(inputValue == '') {
      return;
    }

    if($(this).hasClass('ss')) {
      len = 9;
    } else if($(this).hasClass('phone')) {
      len = 10;
    } else if($(this).hasClass('date')) {
      len = 8;
    } else if($(this).hasClass('zip')) {
      len = 5;
    } else if($(this).hasClass('money')) {
      if(dec.length < 2) {
        setInvalid(this);
      }
      return;
    }

    if(inputValue.length < len) {
      setInvalid(this);
      return;
    } else if(len === 5 && inputValue === "00000") {
      setInvalid(this);
      return;
    }
    
    clearInvalid(this);
  });

  $("form").on('submit', function(e) {
    // When the form is submitted check all of the classes to see if
    // the 'data' value 'invalid' is set to true. If it is the length
    // will be greater than 0.
    
    const ret = $('.date, .phone, .zip, .ss').filter(function() {
      return $(this).data('invalid');
    }).length > 0;

    if(ret === true) {
      $('#errorMsg').html("<h1>Error</h1>");
      return false;
    } else {
      $('#errorMsg').html("");
    }
  });
});
  </script>
  <script src="./test.js"></script>
EOF;

[$top, $footer] = $S->getPageTopBottom();

echo <<<EOF
$top
<div id='errorMsg'></div>   
<form method='post'>   
Social Security $: <input class="ss" data-form-type='other' name="ss" placeholder="nnn-nn-nnnn"><br>
Driver' Licence Experation Date: <input class="date" data-form-type='other' when='after' name="ExpirationDate" placeholder="mm/dd/yyyy"><br>
Birth Date: <input class="date" data-form-type='other' when='before' name="yesterday" placeholder="mm/dd/yyyy"><br>
A date within this monrth and year: <input class="date" data-form-type='other' when='current' name="now" placeholder="mm/dd/yyyy"><br>
Pone Number: <input class="phone" data-form-type='other' name="phone" placeholder="(nnn) nnn-nnnn"><br>
Zip Code: <input class="zip" data-form-type='other' name="zip" placeholder="nnnnn"><br>

<button type='submit' name='submit'>Submit</button>
</form>
$footer
EOF;
