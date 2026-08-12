

"use strict";

/* Every feature checks that its elements exist before running,
   so this single file can be linked on all pages safely. */

document.addEventListener("DOMContentLoaded", function () {
  initWelcomeMessage();   // Section A — Home page only
  initThemeToggle();      // Section C — all pages
  initShowHide();         // Section D — Home page only
  initGalleryFlip();      // Section E — Gallery page only
  initFormValidation();   // Sections B + F — Register page only
});

/* ============================================================
   SECTION A — WELCOME MESSAGE (5 marks)
   Prompts the visitor for their name and displays a
   personalised welcome banner on the Home page.
   ============================================================ */
function initWelcomeMessage() {
  var banner = document.getElementById("welcome-banner");
  if (!banner) return; // not on the Home page

  var visitorName = "";

  // Remember the name for this browser session so the prompt
  // does not reappear every time the user returns to Home.
  try {
    visitorName = sessionStorage.getItem("visitorName") || "";
  } catch (e) {
    visitorName = "";
  }

  if (!visitorName) {
    var answer = prompt("Welcome to the Cybersecurity Skills Hub!\nWhat is your name?");
    // prompt() returns null if the user presses Cancel
    visitorName = answer ? answer.trim() : "";
    try {
      if (visitorName) sessionStorage.setItem("visitorName", visitorName);
    } catch (e) { /* storage unavailable — ignore */ }
  }

  if (visitorName) {
    banner.textContent = "👋 Welcome, " + visitorName +
      "! Great to have you at the Cybersecurity Skills Hub.";
  } else {
    banner.textContent = "👋 Welcome, guest! Great to have you at the Cybersecurity Skills Hub.";
  }
  banner.classList.add("visible");
}

/* ============================================================
   SECTION B — FORM VALIDATION (10 marks)
   Validates the registration form from the HTML lab.
   Blocks submission and shows an error message for every
   required field that is blank or invalid.
   ============================================================ */
function initFormValidation() {
  var form = document.getElementById("register-form");
  if (!form) return; // not on the Register page

  form.setAttribute("novalidate", "novalidate"); // let JS do the work

  form.addEventListener("submit", function (event) {
    event.preventDefault(); // stop submission until checks pass
    clearErrors(form);

    var errors = [];

    // --- Text / email / password / phone fields ---
    errors = errors.concat(
      checkFilled(form, "fullname", "Please enter your full name."),
      checkFilled(form, "email", "Please enter your email address."),
      checkFilled(form, "password", "Please choose a password."),
      checkFilled(form, "phone", "Please enter your phone number.")
    );

    // Extra format checks (only if the field is not blank)
    var email = form.elements["email"];
    if (email && email.value.trim() && email.value.indexOf("@") === -1) {
      errors.push(markInvalid(email, "Email must contain an @ symbol."));
    }
    var pass = form.elements["password"];
    if (pass && pass.value && pass.value.length < 6) {
      errors.push(markInvalid(pass, "Password must be at least 6 characters."));
    }

    // --- Radio group: experience level ---
    var levelChecked = form.querySelector('input[name="level"]:checked');
    if (!levelChecked) {
      var radioGroup = document.getElementById("level-group");
      errors.push(markInvalid(radioGroup, "Please select your experience level."));
    }

    // --- Select: preferred course ---
    var course = form.elements["course"];
    if (course && course.value === "") {
      errors.push(markInvalid(course, "Please choose a course."));
    }

    // --- Checkbox: terms and conditions ---
    var terms = form.elements["terms"];
    if (terms && !terms.checked) {
      errors.push(markInvalid(terms.closest(".field") || terms,
        "You must accept the terms to register."));
    }

    // --- Result ---
    var summary = document.getElementById("form-summary");
    if (errors.length > 0) {
      if (summary) {
        summary.textContent = "⚠ Please fix the " + errors.length +
          " highlighted field" + (errors.length > 1 ? "s" : "") + " below.";
        summary.className = "form-summary error";
      }
      // Move focus to the first invalid field for accessibility
      var firstInvalid = form.querySelector(".invalid input, input.invalid, select.invalid, textarea.invalid");
      if (firstInvalid) firstInvalid.focus();
    } else {
      // Every client-side check passed — hand the data to PHP.
      // form.submit() is the native DOM method: it does NOT re-fire
      // this "submit" listener, so there is no loop. We deliberately
      // do NOT call form.reset() here, because that would wipe the
      // fields before the browser sends them to the server.
      if (summary) {
        summary.textContent = "✅ Details look valid — sending to the server…";
        summary.className = "form-summary success";
      }
      form.submit(); // POSTs the data to process_register.php
    }
  });

  initCharCounter(form); // Section F lives with the form
}

/* Helper: require that a named field is not blank */
function checkFilled(form, name, message) {
  var field = form.elements[name];
  if (field && field.value.trim() === "") {
    return [markInvalid(field, message)];
  }
  return [];
}

/* Helper: highlight a field and print its error message */
function markInvalid(element, message) {
  if (!element) return message;
  var wrapper = element.classList ? element : null;
  if (wrapper) wrapper.classList.add("invalid");

  // Find (or create) the .error-msg element that belongs to this field
  var container = element.closest ? (element.closest(".field") || element) : element;
  var msg = container.querySelector ? container.querySelector(".error-msg") : null;
  if (!msg && container.insertAdjacentHTML) {
    container.insertAdjacentHTML("beforeend", '<p class="error-msg"></p>');
    msg = container.querySelector(".error-msg");
  }
  if (msg) msg.textContent = message;
  return message;
}

/* Helper: wipe previous error state before re-validating */
function clearErrors(form) {
  var msgs = form.querySelectorAll(".error-msg");
  for (var i = 0; i < msgs.length; i++) msgs[i].textContent = "";
  var bad = form.querySelectorAll(".invalid");
  for (var j = 0; j < bad.length; j++) bad[j].classList.remove("invalid");
}

/* ============================================================
   SECTION C — DYNAMIC CONTENT 1: THEME / COLOUR TOGGLE
   Changes the colours of every page by switching the CSS
   custom properties (adds a .dark-mode class to <body>).
   ============================================================ */
function initThemeToggle() {
  var toggle = document.getElementById("theme-toggle");
  if (!toggle) return;

  // Restore the visitor's last choice for this session
  try {
    if (sessionStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark-mode");
      toggle.textContent = "☀ Light mode";
    }
  } catch (e) { /* ignore */ }

  toggle.addEventListener("click", function () {
    var dark = document.body.classList.toggle("dark-mode");
    toggle.textContent = dark ? "☀ Light mode" : "🌙 Dark mode";
    try {
      sessionStorage.setItem("theme", dark ? "dark" : "light");
    } catch (e) { /* ignore */ }
  });
}

/* ============================================================
   SECTION D — DYNAMIC CONTENT 2: SHOW / HIDE EXTRA INFO
   A button on the Home page reveals or hides additional
   information about the hub, and updates its own label.
   ============================================================ */
function initShowHide() {
  var button = document.getElementById("more-info-btn");
  var panel = document.getElementById("more-info");
  if (!button || !panel) return;

  button.addEventListener("click", function () {
    var isHidden = panel.classList.toggle("hidden");
    button.textContent = isHidden ? "Learn more about the hub ▾" : "Show less ▴";
  });
}

/* ============================================================
   SECTION E — DYNAMIC CONTENT 3: INTERACTIVE GALLERY CARDS
   Clicking / tapping a gallery card flips it (works on
   touch screens where the CSS hover flip cannot), and adds
   a highlighted "selected" border to the active card.
   ============================================================ */
function initGalleryFlip() {
  var cards = document.querySelectorAll(".flip-card");
  if (cards.length === 0) return;

  cards.forEach(function (card) {
    card.addEventListener("click", function () {
      // Only one card highlighted at a time
      cards.forEach(function (c) {
        if (c !== card) c.classList.remove("flipped", "selected");
      });
      card.classList.toggle("flipped");
      card.classList.toggle("selected");
    });
  });
}

/* ============================================================
   SECTION F — DYNAMIC CONTENT 4: LIVE CHARACTER COUNTER
   Updates a counter under the "learning goals" textarea as
   the user types, and warns when the limit is near.
   ============================================================ */
function initCharCounter(form) {
  var textarea = form.elements["goals"];
  var counter = document.getElementById("goals-counter");
  if (!textarea || !counter) return;

  var LIMIT = 200;

  textarea.addEventListener("input", function () {
    var length = textarea.value.length;
    counter.textContent = length + " / " + LIMIT + " characters";
    counter.style.color = length > LIMIT ? "#d64550" : "";
  });
}
