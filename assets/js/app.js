document.addEventListener('DOMContentLoaded', function () {

    // auto-dismiss alerts after 4 seconds
    var alerts = document.querySelectorAll('.alert-dismissible');

    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';

            setTimeout(function () {
                if (alert && document.body.contains(alert)) {
                    var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }
            }, 500);
        }, 4000);
    });


    // login form validation
    var loginForm = document.querySelector('form[action="index.php"]');

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            var isValid = true;

            var username = document.getElementById('username');
            var password = document.getElementById('password');
            var role     = document.getElementById('role');

            if (username && username.value.trim() === '') {
                username.classList.add('is-invalid');
                isValid = false;
            } else if (username) {
                username.classList.remove('is-invalid');
            }

            if (password && password.value.trim() === '') {
                password.classList.add('is-invalid');
                isValid = false;
            } else if (password) {
                password.classList.remove('is-invalid');
            }

            if (role && (role.value === '' || role.value === null)) {
                role.classList.add('is-invalid');
                isValid = false;
            } else if (role) {
                role.classList.remove('is-invalid');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // equipment form validation
    var equipmentForm = document.querySelector('form[action*="equipment.php?action="]');

    if (equipmentForm) {
        equipmentForm.addEventListener('submit', function (e) {
            var isValid = true;

            var name         = document.getElementById('name');
            var category     = document.getElementById('category');
            var serialNumber = document.getElementById('serial_number');
            var totalQty     = document.getElementById('total_quantity');

            if (name && name.value.trim() === '') {
                name.classList.add('is-invalid');
                isValid = false;
            } else if (name) {
                name.classList.remove('is-invalid');
            }

            if (category && category.value.trim() === '') {
                category.classList.add('is-invalid');
                isValid = false;
            } else if (category) {
                category.classList.remove('is-invalid');
            }

            if (serialNumber && serialNumber.value.trim() === '') {
                serialNumber.classList.add('is-invalid');
                isValid = false;
            } else if (serialNumber) {
                serialNumber.classList.remove('is-invalid');
            }

            if (totalQty && (parseInt(totalQty.value) < 1 || totalQty.value.trim() === '')) {
                totalQty.classList.add('is-invalid');
                isValid = false;
            } else if (totalQty) {
                totalQty.classList.remove('is-invalid');
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // user form validation - check passwords match and email format
    var userForm = document.querySelector('form[action*="users.php?action="]');

    if (userForm) {
        userForm.addEventListener('submit', function (e) {
            var isValid = true;

            var name           = document.getElementById('name');
            var email          = document.getElementById('email');
            var username       = document.getElementById('username');
            var password       = document.getElementById('password');
            var confirmPassword = document.getElementById('confirm_password');

            if (name && name.value.trim() === '') {
                name.classList.add('is-invalid');
                isValid = false;
            } else if (name) {
                name.classList.remove('is-invalid');
            }

            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email.value.trim())) {
                email.classList.add('is-invalid');
                isValid = false;
            } else if (email) {
                email.classList.remove('is-invalid');
            }

            if (username && username.value.trim() === '') {
                username.classList.add('is-invalid');
                isValid = false;
            } else if (username) {
                username.classList.remove('is-invalid');
            }

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

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    // clear red borders when user starts typing again
    var allInputs = document.querySelectorAll('.form-control, .form-select');

    allInputs.forEach(function (input) {
        input.addEventListener('input', function () {
            this.classList.remove('is-invalid');
        });
        input.addEventListener('change', function () {
            this.classList.remove('is-invalid');
        });
    });


    var deleteButtons = document.querySelectorAll('.btn-delete');

    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            var confirmed = confirm('Are you sure you want to delete this? This action cannot be undone.');

            if (!confirmed) {
                e.preventDefault();
            }
        });
    });


    var browseForm = document.querySelector('form[action="browse.php"]');

    if (browseForm) {
        var categoryFilter  = browseForm.querySelector('#category');
        var conditionFilter = browseForm.querySelector('#condition_filter');

        if (categoryFilter) {
            categoryFilter.addEventListener('change', function () {
                browseForm.submit();
            });
        }

        if (conditionFilter) {
            conditionFilter.addEventListener('change', function () {
                browseForm.submit();
            });
        }
    }


    // due date calculator for rental form
    var durationSelect  = document.getElementById('duration');
    var dueDatePreview  = document.getElementById('due_date_preview');

    if (durationSelect && dueDatePreview) {
        function calculateDueDate() {
            var days    = parseInt(durationSelect.value);
            var dueDate = new Date();

            dueDate.setDate(dueDate.getDate() + days);

            var options = { day: '2-digit', month: 'short', year: 'numeric' };
            dueDatePreview.value = dueDate.toLocaleDateString('en-GB', options);
        }

        calculateDueDate();

        durationSelect.addEventListener('change', calculateDueDate);
    }


    var textareas = document.querySelectorAll('textarea.form-control');

    textareas.forEach(function (textarea) {
        var counter = document.createElement('div');
        counter.className = 'form-text text-muted';
        counter.textContent = textarea.value.length + ' characters';

        textarea.parentNode.insertBefore(counter, textarea.nextSibling);

        textarea.addEventListener('input', function () {
            counter.textContent = this.value.length + ' characters';
        });
    });


    var tableRows = document.querySelectorAll('.table-hover tbody tr');

    tableRows.forEach(function (row) {
        row.style.cursor = 'default';

        if (row.querySelector('a, button:not([disabled])')) {
            row.style.cursor = 'pointer';
        }
    });


    var printButtons = document.querySelectorAll('.btn-print');

    printButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            window.print();
        });
    });


    // --- auto-refresh availability on browse page ---
    // polls the server every 30 seconds so the user sees up-to-date stock
    var badges = document.querySelectorAll('.availability-badge');

    if (badges.length > 0) {
        function refreshAvailability() {
            fetch('ajax_availability.php')
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    if (!json.success) return;

                    json.data.forEach(function (item) {
                        var card = document.querySelector('[data-equipment-id="' + item.id + '"]');
                        if (!card) return;

                        var badge = card.querySelector('.availability-badge');
                        if (!badge) return;

                        var total = badge.getAttribute('data-total');

                        // update the text and colour
                        if (item.available_quantity > 0) {
                            badge.textContent = item.available_quantity + ' / ' + total;
                            badge.className = 'availability-badge text-success';
                        } else {
                            badge.textContent = '0 / ' + total;
                            badge.className = 'availability-badge text-danger';
                        }

                        // update the rent / out of stock button too
                        var actionDiv = card.querySelector('.rent-action');
                        if (actionDiv) {
                            if (item.available_quantity > 0) {
                                actionDiv.innerHTML = '<a href="browse.php?action=rent&id=' + item.id + '" class="btn btn-primary w-100">' +
                                    '<i class="bi bi-bag-plus"></i> Rent</a>';
                            } else {
                                actionDiv.innerHTML = '<button class="btn btn-secondary w-100" disabled>' +
                                    '<i class="bi bi-x-circle"></i> Out of Stock</button>';
                            }
                        }
                    });
                })
                .catch(function () {
                    // silently fail - no point bothering the user if the poll fails
                });
        }

        // poll every 30 seconds
        setInterval(refreshAvailability, 30000);
    }


    console.log('RentO app.js loaded successfully.');

});
