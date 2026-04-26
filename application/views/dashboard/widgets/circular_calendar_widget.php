<style>
/* ─────────────────────────────────────────
 * Circular Calendar / Clock Widget (Pure CSS)
 * Scoped to .calendar-widget-wrapper
 * ───────────────────────────────────────── */

.calendar-widget-wrapper,
.calendar-widget-wrapper * {
  box-sizing: border-box;
}

/* Outer container so absolute elements are scoped */
.calendar-widget-wrapper {
  position: relative;
  min-height: 520px;
  background: #292929; /* was $background-grey */
  border-radius: 0.75rem;
  overflow: hidden;
  font-family: 'Roboto Mono', monospace;
  color: #fff;
}

/* Optional: heading styles inside widget */
.calendar-widget-wrapper h1 {
  color: #555;
  font-size: 25px;
  margin: 0;
}

.calendar-widget-wrapper h2 {
  color: #555;
  font-size: 15px;
  margin: 0;
}

/* Shared ring text behaviour (replaces %ring-text + @extend) */
.calendar-widget-wrapper .center-preview span,
.calendar-widget-wrapper .day-name-preview span,
.calendar-widget-wrapper .day-name-text span,
.calendar-widget-wrapper .month-preview span,
.calendar-widget-wrapper .month-text span,
.calendar-widget-wrapper .day-preview span,
.calendar-widget-wrapper .day-text span,
.calendar-widget-wrapper .hand-container {
  text-align: center;
  transform-origin: center center;
}

/* Center dial (main clock) */
.calendar-widget-wrapper .center-dial {
  position: absolute;
  top: calc(50% - 75px);
  left: calc(50% - 75px);
  width: 150px;  /* $center-dial-size */
  height: 150px;
  background-color: #202020;
  border-radius: 50%;
  color: #000;
  box-shadow: 0 2px 2px #000;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.5s;
}

/* HELLO preview around center head */
.calendar-widget-wrapper .center-preview span {
  position: absolute;
  top: 0;
  left: calc(50% - 12.5px);
  height: 150px;   /* center dial size */
  width: 25px;     /* $h1-size */
}

/* initial center text invisible (fades in JS) */
.calendar-widget-wrapper .center-preview {
  opacity: 0;
}

/* Head + torso inside center dial */
.calendar-widget-wrapper .head {
  position: relative;
  top: 50%;
  left: 50%;
  width: 50px;
  height: 50px;
  transform: translate(-50%, -50%);
  background: #fff;
  border-radius: 50%;
}

.calendar-widget-wrapper .torso {
  position: relative;
  top: calc(50% - 20px);
  left: calc(50% - 50px);
  width: 100px;
  height: 100px;
  background: #fff;
  border-radius: 50%;
}

/* Clock hands container */
.calendar-widget-wrapper .hand-container {
  position: absolute;
  top: 0;
  left: calc(50% - 12.5px);
  width: 25px;      /* $h1-size */
  height: 150px;    /* $center-dial-size */
  opacity: 0;
}

/* Hour, minute, second hands */
.calendar-widget-wrapper .hour-hand {
  width: 10px;
  height: 50px;
  position: relative;
  top: calc(50% - 45px);
  left: calc(50% - 5px);
  background: #fff;
  border-radius: 5px;
  transition: all 0.5s;
}

.calendar-widget-wrapper .minute-hand {
  width: 10px;
  height: 70px;
  position: relative;
  top: calc(50% - 65px);
  left: calc(50% - 5px);
  background: #ccc;
  border-radius: 5px;
}

.calendar-widget-wrapper .second-hand {
  width: 2px;
  height: 70px;
  position: relative;
  top: calc(50% - 69px);
  left: calc(50% - 1px);
  background: #aaa;
  border-radius: 1px;
}

/* Day name ring (MON TUE WED...) */
.calendar-widget-wrapper .day-name-dial {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 250px;  /* $day-name-size */
  height: 250px;
  transform: translate(-50%, -50%);
  transition: all 0.5s;
}

.calendar-widget-wrapper .day-name-preview span {
  position: absolute;
  top: calc(-25% - 5px);
  left: calc(50% - 12.5px);
  height: 250px;
  width: 25px;  /* $h1-size */
}

.calendar-widget-wrapper .day-name-preview {
  opacity: 0;
}

.calendar-widget-wrapper .day-name-text span {
  position: absolute;
  top: calc(-25% + 5px);
  left: calc(50% - 6px);
  height: 232px;
  width: 12px; /* $h2-size ~= 12px */
}

.calendar-widget-wrapper .day-name-text {
  opacity: 0;
}

/* Month ring */
.calendar-widget-wrapper .month-dial {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 350px; /* $month-size */
  height: 350px;
  transform: translate(-50%, -50%);
  transition: all 0.5s;
}

.calendar-widget-wrapper .month-preview span {
  position: absolute;
  top: calc(-25% + 20px);
  left: calc(50% - 12.5px);
  height: 350px;
  width: 25px;
}

.calendar-widget-wrapper .month-preview {
  opacity: 0;
}

.calendar-widget-wrapper .month-text span {
  position: absolute;
  top: calc(-25% + 30px);
  left: calc(50% - 6px);
  height: 332px;
  width: 12px;
}

.calendar-widget-wrapper .month-text {
  opacity: 0;
}

/* Day-of-month ring (01..31) */
.calendar-widget-wrapper .day-dial {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 450px;
  height: 450px;
  transform: translate(-50%, -50%);
  transition: all 0.5s;
}

.calendar-widget-wrapper .day-preview span {
  position: absolute;
  top: calc(-25% + 45px);
  left: calc(50% - 12.5px);
  height: 450px;
  width: 25px;
}

.calendar-widget-wrapper .day-preview {
  opacity: 0;
}

.calendar-widget-wrapper .day-text span {
  position: absolute;
  top: calc(-25% + 55px);
  left: calc(50% - 6px);
  height: 432px;
  width: 12px;
}

.calendar-widget-wrapper .day-text {
  opacity: 0;
}

/* Background ring + front ring */
.calendar-widget-wrapper .ring-back {
  opacity: 0.1;
  width: 100%;
  height: 100%;
  border: solid 10px transparent;
  border-radius: 50%;
  position: relative;
}

.calendar-widget-wrapper .ring-back::before {
  content: "";
  position: absolute;
  top: 5px;
  left: 5px;
  right: 5px;
  bottom: 5px;
  border-radius: 50%;
  border: solid 35px #ffffff; /* $ring-back */
}

.calendar-widget-wrapper .ring {
  position: relative;
  top: -100%;
  width: 100%;
  height: 100%;
  border: solid 45px #202020;
  border-radius: 50%;
  border-bottom-color: transparent;
  box-shadow: 0 -2px 2px #000;
  transition: all 0.5s;
}

/* Side rings (weather + steps) */
.calendar-widget-wrapper .side-ring {
  width: 200px;  /* $side-ring-size */
  height: 200px;
  background-color: #202020;
  border-radius: 50%;
  box-shadow: 0 2px 2px #000;
  color: #000;
  overflow: hidden;
  position: absolute;
  transition: all 0.5s;
}

.calendar-widget-wrapper #weather {
  top: calc(50% - 100px);
  left: calc(20% - 100px);
}

.calendar-widget-wrapper #steps {
  top: calc(50% - 100px);
  left: calc(80% - 100px);
}

/* Weather icon + temp */
.calendar-widget-wrapper .fa-cloud {
  opacity: 0;
  position: absolute;
  top: calc(50% - 40px);
  left: calc(50% - 40px);
  color: #555;
  font-size: 80px;
}

.calendar-widget-wrapper .temperature {
  opacity: 0;
  position: absolute;
  top: 10%;
  left: 55%;
  color: #ffcc00;
  font-size: 20px;
}

/* Steps bars */
.calendar-widget-wrapper .bars {
  opacity: 0;
  position: relative;
  top: calc(50% - 70px);
  left: calc(50% - 65px);
  width: 140px;
  height: 140px;
}

.calendar-widget-wrapper .bar {
  width: 18px;
  height: 140px;
  margin: 0 -4px;
  position: absolute;
}

/* Expanded from SCSS @for: .bar nth-child positions */
.calendar-widget-wrapper .bar:nth-child(1) { top: 0; left: 0; }
.calendar-widget-wrapper .bar:nth-child(2) { top: 0; left: 20px; }
.calendar-widget-wrapper .bar:nth-child(3) { top: 0; left: 40px; }
.calendar-widget-wrapper .bar:nth-child(4) { top: 0; left: 60px; }
.calendar-widget-wrapper .bar:nth-child(5) { top: 0; left: 80px; }
.calendar-widget-wrapper .bar:nth-child(6) { top: 0; left: 100px; }
.calendar-widget-wrapper .bar:nth-child(7) { top: 0; left: 120px; }

/* Day letters under bars */
.calendar-widget-wrapper .day-letter {
  position: relative;
  top: 110px;
  color: #555;
  font-size: 18px;
  text-align: center;
}

/* Bar fill elements (x1..x7) */
.calendar-widget-wrapper .x {
  position: absolute;
  bottom: 30px;
  left: 1px;
  width: 16px;
  height: 2px;
  transition: all 0.5s;
}

/* Expanded from SCSS @each on $bar-colors */
.calendar-widget-wrapper #x1 { background: #ff3b30; }  /* Mon */
.calendar-widget-wrapper #x2 { background: #ff9500; }
.calendar-widget-wrapper #x3 { background: #ffcc00; }
.calendar-widget-wrapper #x4 { background: #4cd964; }
.calendar-widget-wrapper #x5 { background: #5ac8fa; }
.calendar-widget-wrapper #x6 { background: #007aff; }
.calendar-widget-wrapper #x7 { background: #5856d6; }
</style>

<div class="card">
  <div class="card-body">
    <div class="calendar-widget-wrapper">
      <div class="center-dial">
        <h1 class="center-preview">HELLO</h1>
        <div class="head"></div>
        <div class="torso"></div>
        <div class="hand-container" id="minutes">
          <div class="minute-hand"></div>
        </div>
        <div class="hand-container" id="hours">
          <div class="hour-hand"></div>
        </div>
        <div class="hand-container" id="seconds">
          <div class="second-hand"></div>
        </div>
      </div>

      <div class="day-name-dial">
        <div class="ring-back"></div>
        <div class="ring" id="r1">
          <h1 class="day-name-preview">DAY NAME</h1>
          <h2 class="day-name-text">MON TUE WED THU FRI SAT SUN</h2>
        </div>
      </div>

      <div class="month-dial">
        <div class="ring-back"></div>
        <div class="ring" id="r2">
          <h1 class="month-preview">MONTH</h1>
          <h2 class="month-text">JAN FEB MAR APR MAY JUN JUL AUG SEP OCT NOV DEC</h2>
        </div>
      </div>

      <div class="day-dial">
        <div class="ring-back"></div>
        <div class="ring" id="r3">
          <h1 class="day-preview">DAY</h1>
          <h2 class="day-text">
            01 02 03 04 05 06 07 08 09 10
            11 12 13 14 15 16 17 18 19 20
            21 22 23 24 25 26 27 28 29 30 31
          </h2>
        </div>
      </div>

      <div class="side-ring" id="weather">
        <div class="fa fa-cloud"></div>
        <p class="temperature">14&#176;C</p>
      </div>

      <div class="side-ring" id="steps">
        <div class="bars">
          <div class="bar">
            <div class="day-letter">M</div>
            <div class="x" id="x1"></div>
          </div>
          <div class="bar">
            <div class="day-letter">T</div>
            <div class="x" id="x2"></div>
          </div>
          <div class="bar">
            <div class="day-letter">W</div>
            <div class="x" id="x3"></div>
          </div>
          <div class="bar">
            <div class="day-letter">T</div>
            <div class="x" id="x4"></div>
          </div>
          <div class="bar">
            <div class="day-letter">F</div>
            <div class="x" id="x5"></div>
          </div>
          <div class="bar">
            <div class="day-letter">S</div>
            <div class="x" id="x6"></div>
          </div>
          <div class="bar">
            <div class="day-letter">S</div>
            <div class="x" id="x7"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
/*
 * Circular Calendar Display
 * Requires: jQuery, optional: lettering.js, Font Awesome (for .fa-cloud)
 */

(function () {
  if (typeof window.jQuery === 'undefined') {
    console.warn('Calendar widget: jQuery not found; widget script skipped.');
    return;
  }
  var $ = window.jQuery;

  $(function () {
    var date, dayName, day, month;
    var range = 270,
      sectionsDayName = 7,
      sectionsDay = 31,
      sectionsMonth = 12,
      charactersDayName = 3,
      charactersDay = 2,
      charactersMonth = 3,
      dayColor = '#FF2D55',
      monthColor = '#007AFF',
      dayNameColor = '#4CD964';

    // Rotate ring + color characters
    function rotateRing(input, sections, characters, ring, text, color) {
      var sectionWidth = range / sections;
      var initialRotation = 135 - sectionWidth / 2;
      var rotateAmount = initialRotation - sectionWidth * (input - 1);
      var start = characters * (input - 1) + (input - 1) + 1;

      $(ring).css({
        '-webkit-transform': 'rotate(' + rotateAmount + 'deg)',
        '-moz-transform': 'rotate(' + rotateAmount + 'deg)',
        '-ms-transform': 'rotate(' + rotateAmount + 'deg)',
        'transform': 'rotate(' + rotateAmount + 'deg)'
      });

      for (var i = start; i < start + characters; i++) {
        $(text).children('.char' + i).css('color', color);
      }
    }

    // Animate clock hands
    function clockRotation() {
      setInterval(function () {
        var d = new Date();
        var seconds = d.getSeconds();
        var minutes = d.getMinutes();
        var hours = d.getHours();

        var secondsRotation = seconds * 6;
        var minutesRotation = minutes * 6;
        var hoursRotation = hours * 30 + minutes / 2;

        $('#seconds').css({
          '-webkit-transform': 'rotate(' + secondsRotation + 'deg)',
          '-moz-transform': 'rotate(' + secondsRotation + 'deg)',
          '-ms-transform': 'rotate(' + secondsRotation + 'deg)',
          'transform': 'rotate(' + secondsRotation + 'deg)'
        });
        $('#minutes').css({
          '-webkit-transform': 'rotate(' + minutesRotation + 'deg)',
          '-moz-transform': 'rotate(' + minutesRotation + 'deg)',
          '-ms-transform': 'rotate(' + minutesRotation + 'deg)',
          'transform': 'rotate(' + minutesRotation + 'deg)'
        });
        $('#hours').css({
          '-webkit-transform': 'rotate(' + hoursRotation + 'deg)',
          '-moz-transform': 'rotate(' + hoursRotation + 'deg)',
          '-ms-transform': 'rotate(' + hoursRotation + 'deg)',
          'transform': 'rotate(' + hoursRotation + 'deg)'
        });
      }, 1000);
    }

    // Random heights for step bars (up to current weekday)
    function loadBars() {
      for (var i = 1; i <= dayName; i++) {
        var newHeight = Math.floor(Math.random() * 85) + 5;
        $('#x' + i).css('height', newHeight + 'px');
      }
    }

    function init() {
      // lettering.js – if available
      if ($.fn.lettering) {
        $('.center-preview').lettering();
        $('.day-name-preview').lettering();
        $('.day-name-text').lettering();
        $('.day-preview').lettering();
        $('.day-text').lettering();
        $('.month-preview').lettering();
        $('.month-text').lettering();
      }

      // Show initial preview text
      $('.day-preview').fadeTo(10, 1);
      $('.month-preview').fadeTo(10, 1);
      $('.day-name-preview').fadeTo(10, 1);
      $('.center-preview').fadeTo(10, 1);

      // Date parts
      date = new Date();
      dayName = date.getDay();  // 0-6 (Sun-Sat)
      day = date.getDate();     // 1-31
      month = date.getMonth() + 1; // 1-12

      // convert Sunday (0) → 7 for ring
      if (dayName === 0) {
        dayName = 7;
      }

      // Day-of-month ring
      setTimeout(function () {
        $('.day-preview').fadeTo(500, 0);
        $('.day-text').fadeTo(500, 1, function () {
          rotateRing(day, sectionsDay, charactersDay, '#r3', '.day-text', dayColor);
        });
      }, 500);

      // Month ring + side widgets
      setTimeout(function () {
        $('.month-preview').fadeTo(500, 0);
        $('.fa-cloud').fadeTo(500, 1);
        $('.temperature').fadeTo(500, 1);
        $('.bars').fadeTo(500, 1);
        $('.month-text').fadeTo(500, 1, function () {
          rotateRing(month, sectionsMonth, charactersMonth, '#r2', '.month-text', monthColor);
          loadBars();
        });
      }, 1000);

      // Day-name ring
      setTimeout(function () {
        $('.day-name-preview').fadeTo(500, 0);
        $('.day-name-text').fadeTo(500, 1, function () {
          rotateRing(dayName, sectionsDayName, charactersDayName, '#r1', '.day-name-text', dayNameColor);
        });
      }, 1500);

      // Center dial → clock hands
      setTimeout(function () {
        $('.center-preview').fadeTo(500, 0);
        $('.head').fadeTo(500, 0);
        $('.torso').fadeTo(500, 0);
        $('.hand-container').fadeTo(500, 1, function () {
          // clock becomes visible
        });
      }, 2000);

      clockRotation();
    }

    init();
  });
})();
</script>
