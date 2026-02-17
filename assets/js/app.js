/**
 * RentO - Main JavaScript File
 * ==============================
 * Custom client-side interactivity for the Equipment Rental Management System.
 *
 * Features:
 *   1. Auto-dismiss flash messages after 4 seconds
 *   2. Client-side form validation (login, equipment, user forms)
 *   3. Delete confirmation dialogs
 *   4. Auto-submit search filters on dropdown change
 *   5. Rental duration due date calculator
 *   6. Character counter for textarea fields
 *   7. Table row hover highlight
 *   8. Print button functionality
 *
 * Tech: Vanilla JavaScript only — no jQuery, no frameworks.
 * Author: Tony (CMM007 Coursework)
 */

// Wait until the page is fully loaded before running any JS
document.addEventListener('DOMContentLoaded', function () {

    // =========================================================
    // 1. AUTO-DISMISS FLASH MESSAGES
    // =========================================================
    // Flash messages (Bootstrap alerts) will fade out and close
    // automatically after 4 seconds so the user doesn't have to
    // manually dismiss them.

    var alerts = document.querySelectorAll('.alert-dismissible');

    alerts.forEach(function (alert) {
        setTimeout(function () {
            // Add a fade-out animation using CSS opacity transition
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';

            // After the fade finishes (500ms), use Bootstrap's API to close it
            setTimeout(function () {
                // Check the alert still exists in the DOM before closing
                if (alert && document.body.contains(alert)) {
                    var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }
            }, 500); // Wait for fade animation to finish
        }, 4000); // Start fading after 4 seconds
    });


    // =========================================================
    // 2. CLIENT-SIDE FORM VALIDATION
    // =========================================================
    // These validate forms before submission to give instant
    // feedback. The server still validates everything too —
    // this is just for a better user experience.

    // ---------------------------------------------------------
    // 2a. Login Form Validation
    // ---------------------------------------------------------
    // Check that username, password, and role are all filled in.
    // The login form is on index.php (the main page).

    var loginForm = document.querySelector('form[action="index.php"]');

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            var isValid = true;

            // Get the input fields
            var username = document.getElementById('username');
            var password = document.getElementById('password');
            var role     = document.getElementById('role');

            // Check username is not empty
            if (username && username.value.trim() === '') {
                username.classList.add('is-invalid');
                isValid = false;
            } else if (username) {
                username.classList.remove('is-invalid');
            }

            // Check password is not empty
            if (password && password.value.trim() === '') {
                password.classList.add('is-invalid');
                isValid = false;
            } else if (password) {
                password.classList.remove('is-invalid');
            }

            // Check a role has been selected (not the disabled placeholder)
            if (role && (role.value === '' || role.value === null)) {
                role.classList.add('is-invalid');
                isValid = false;
            } else if (role) {
                role.classList.remove('is-invalid');
            }

            // If any field is invalid, stop the form from submitting
            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // ---------------------------------------------------------
    // 2b. Equipment Form Validation (Add / Edit)
    // ---------------------------------------------------------
    // The equipment form appears on admin/equipment.php when
    // action=add or action=edit. We check required fields and
    // make sure quantity is at least 1.

    var equipmentForm = document.querySelector('form[action*="equipment.php?action="]');

    if (equipmentForm) {
        equipmentForm.addEventListener('submit', function (e) {
            var isValid = true;

            // Get the input fields
            var name         = document.getElementById('name');
            var category     = document.getElementById('category');
            var serialNumber = document.getElementById('serial_number');
            var totalQty     = document.getElementById('total_quantity');

            // Check equipment name
            if (name && name.value.trim() === '') {
                name.classList.add('is-invalid');
                isValid = false;
            } else if (name) {
                name.classList.remove('is-invalid');
            }

            // Check category
            if (category && category.value.trim() === '') {
                category.classList.add('is-invalid');
                isValid = false;
            } else if (category) {
                category.classList.remove('is-invalid');
            }

            // Check serial number
            if (serialNumber && serialNumber.value.trim() === '') {
                serialNumber.classList.add('is-invalid');
                isValid = false;
            } else if (serialNumber) {
                serialNumber.classList.remove('is-invalid');
            }

            // Check total quantity is at least 1
            if (totalQty && (parseInt(totalQty.value) < 1 || totalQty.value.trim() === '')) {
                totalQty.classList.add('is-invalid');
                isValid = false;
            } else if (totalQty) {
                totalQty.classList.remove('is-invalid');
            }

            // Stop the form from submitting if there are errors
            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // ---------------------------------------------------------
    // 2c. User Form Validation (Add / Edit)
    // ---------------------------------------------------------
    // The user form appears on admin/users.php when action=add
    // or action=edit. We check that passwords match and that
    // the email address looks valid.

    var userForm = document.querySelector('form[action*="users.php?action="]');

    if (userForm) {
        userForm.addEventListener('submit', function (e) {
            var isValid = true;

            // Get the input fields
            var name           = document.getElementById('name');
            var email          = document.getElementById('email');
            var username       = document.getElementById('username');
            var password       = document.getElementById('password');
            var confirmPassword = document.getElementById('confirm_password');

            // Check name is not empty
            if (name && name.value.trim() === '') {
                name.classList.add('is-invalid');
                isValid = false;
            } else if (name) {
                name.classList.remove('is-invalid');
            }

            // Check email format using a simple regex
            // This checks for something@something.something
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email.value.trim())) {
                email.classList.add('is-invalid');
                isValid = false;
            } else if (email) {
                email.classList.remove('is-invalid');
            }

            // Check username is not empty
            if (username && username.value.trim() === '') {
                username.classList.add('is-invalid');
                isValid = false;
            } else if (username) {
                username.classList.remove('is-invalid');
            }

            // Check passwords match (only if the password field has a value)
            // On the edit form, password is optional — only validate if typed
            if (password && confirmPassword) {
                if (password.value !== '' || confirmPassword.value !== '') {
                    if (password.value !== confirmPassword.value) {
                        password.classList.add('is-invalid');
                        confirmPassword.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        password.classList.remove('is-invalid');
                        confirmPassword.classList.remove('is-invalid');
                    }
                }
            }

            // Stop the form from submitting if there are errors
            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // ---------------------------------------------------------
    // 2d. Clear validation styles when user starts typing
    // ---------------------------------------------------------
    // When a user corrects a field, remove the red border
    // immediately so they get instant feedback.

    var allInputs = document.querySelectorAll('.form-control, .form-select');

    allInputs.forEach(function (input) {
        // Listen for both 'input' (typing) and 'change' (dropdowns)
        input.addEventListener('input', function () {
            this.classList.remove('is-invalid');
        });
        input.addEventListener('change', function () {
            this.classList.remove('is-invalid');
        });
    });


    // =========================================================
    // 3. DELETE CONFIRMATION DIALOGS
    // =========================================================
    // Any link or button with the class "btn-delete" will show
    // a confirmation dialog before navigating. If the user
    // clicks Cancel, the action is stopped.
    //
    // Note: The existing PHP pages already use inline onclick
    // handlers for delete buttons. This code provides an
    // additional way to add confirmations using a CSS class,
    // which is useful if new delete buttons are added later.

    var deleteButtons = document.querySelectorAll('.btn-delete');

    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            // Show a browser confirmation dialog
            var confirmed = confirm('Are you sure you want to delete this? This action cannot be undone.');

            // If the user clicked Cancel, prevent the navigation
            if (!confirmed) {
                e.preventDefault();
            }
        });
    });


    // =========================================================
    // 4. AUTO-SUBMIT SEARCH FILTERS ON DROPDOWN CHANGE
    // =========================================================
    // On the browse.php page, when the user changes a filter
    // dropdown (category or condition), the form auto-submits
    // so they don't have to click the Search button.
    // The search form uses GET method and action="browse.php".

    var browseForm = document.querySelector('form[action="browse.php"]');

    if (browseForm) {
        // Get the filter dropdowns inside the browse form
        var categoryFilter  = browseForm.querySelector('#category');
        var conditionFilter = browseForm.querySelector('#condition_filter');

        // Auto-submit when category dropdown changes
        if (categoryFilter) {
            categoryFilter.addEventListener('change', function () {
                browseForm.submit();
            });
        }

        // Auto-submit when condition dropdown changes
        if (conditionFilter) {
            conditionFilter.addEventListener('change', function () {
                browseForm.submit();
            });
        }
    }


    // =========================================================
    // 5. RENTAL DURATION DUE DATE CALCULATOR
    // =========================================================
    // On the rent form (browse.php?action=rent), when the user
    // selects a rental duration from the dropdown, we calculate
    // and display the due date in the preview field.
    //
    // Note: browse.php already has an inline <script> for this,
    // but we include it here too as a centralised fallback that
    // works even if the inline script is removed.

    var durationSelect  = document.getElementById('duration');
    var dueDatePreview  = document.getElementById('due_date_preview');

    if (durationSelect && dueDatePreview) {
        /**
         * Calculate the due date based on the selected duration
         * and display it in a nice, readable format.
         */
        function calculateDueDate() {
            var days    = parseInt(durationSelect.value);
            var dueDate = new Date();

            // Add the selected number of days to today's date
            dueDate.setDate(dueDate.getDate() + days);

            // Format the date nicely (e.g., "24 Feb 2026")
            var options = { day: '2-digit', month: 'short', year: 'numeric' };
            dueDatePreview.value = dueDate.toLocaleDateString('en-GB', options);
        }

        // Calculate on page load (so it shows the default selection's due date)
        calculateDueDate();

        // Recalculate whenever the duration dropdown changes
        durationSelect.addEventListener('change', calculateDueDate);
    }


    // =========================================================
    // 6. CHARACTER COUNTER FOR TEXTAREA FIELDS
    // =========================================================
    // Adds a live character count below any <textarea> on the
    // page. This gives users a sense of how much they've typed,
    // which is helpful for description fields.

    var textareas = document.querySelectorAll('textarea.form-control');

    textareas.forEach(function (textarea) {
        // Create a small text element to show the character count
        var counter = document.createElement('div');
        counter.className = 'form-text text-muted';
        counter.textContent = textarea.value.length + ' characters';

        // Insert the counter right after the textarea
        textarea.parentNode.insertBefore(counter, textarea.nextSibling);

        // Update the counter as the user types
        textarea.addEventListener('input', function () {
            counter.textContent = this.value.length + ' characters';
        });
    });


    // =========================================================
    // 7. TABLE ROW HOVER HIGHLIGHT
    // =========================================================
    // Adds a subtle pointer cursor to table rows to make them
    // feel more interactive. Bootstrap's table-hover already
    // handles the background colour change, but this adds the
    // cursor style for extra visual feedback.

    var tableRows = document.querySelectorAll('.table-hover tbody tr');

    tableRows.forEach(function (row) {
        row.style.cursor = 'default';

        // If the row contains a link or button, show a pointer
        // cursor to hint that it's interactive
        if (row.querySelector('a, button:not([disabled])')) {
            row.style.cursor = 'pointer';
        }
    });


    // =========================================================
    // 8. PRINT BUTTON FUNCTIONALITY
    // =========================================================
    // If there's a button on the page with the class "btn-print",
    // clicking it will open the browser's print dialog. This is
    // useful for printing rental receipts, reports, etc.

    var printButtons = document.querySelectorAll('.btn-print');

    printButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();  // Prevent default link behaviour
            window.print();      // Open the browser print dialog
        });
    });


    // =========================================================
    // CONSOLE LOG (for debugging during development)
    // =========================================================
    console.log('RentO app.js loaded successfully.');

});
