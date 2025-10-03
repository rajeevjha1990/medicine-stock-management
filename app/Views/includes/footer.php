</div> <!-- end main-container -->
<footer class="footer">
    <p>&copy; <?= date("Y") ?> MyMedicine</p>
</footer>

<script src="<?php echo base_url(); ?>/assets/js/jquery-3.6.0.min.js"></script>
<script src="<?php echo base_url(); ?>/assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo base_url(); ?>/assets/js/dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>/assets/js/sidebar.js"></script>
<script src="<?php echo base_url(); ?>/assets/js/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>/assets/js/notify.min.js"></script>
<script src="<?php echo base_url();?>/assets/js/select2.min.js"></script>
</body>
</html>
<script>
$('#myTable').DataTable({
    "pageLength": 5,
    "lengthMenu": [5, 10, 25, 50, 100],
    "searching": true,
    "ordering": true,
});

// ================= Medicine Form Submit =================
$(document).ready(function () {
    // Generic form submit handler
    $(document).on("submit", "form:not([no-jquery])", function (e) {
        e.preventDefault();

        var $form = $(this);
        var formData = new FormData(this);

        $form.find('[type="submit"]').prop("disabled", true).data("original-text", $form.find('[type="submit"]').text()).text("Processing...");

        $.ajax({
            url: $form.attr("action"),
            method: $form.attr("method") || "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (res) {
                $form.find('[type="submit"]').prop("disabled", false).text($form.find('[type="submit"]').data("original-text"));

                if (res.status) {
                    $.notify(res.message, "success");

                    if (res.reload) {
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    }

                    if (res.redirect) {
                        setTimeout(function () {
                            window.location.href = res.redirect;
                        }, 1500);
                    }

                } else {
                    if (res.err) {
                        $.each(res.err, function (key, val) {
                            $.notify(val, "error");
                        });
                    } else {
                        $.notify(res.message || "Something went wrong", "error");
                    }
                }
            },
            error: function (xhr) {
                $form.find('[type="submit"]').prop("disabled", false).text($form.find('[type="submit"]').data("original-text"));

                if (xhr.responseJSON && xhr.responseJSON.error) {
                    $.each(xhr.responseJSON.error, function (key, val) {
                        $.notify(val, "error");
                    });
                } else {
                    $.notify("Something went wrong", "error");
                }

                console.error(xhr.responseText);
            }
        });
    });
});

// ================= Delete Logic =================

// Step 1 — First click on "Delete"
$(document).on('click', '.delete_data', function(e) {
    e.preventDefault();
    $(this).addClass('confirm_delete').text("Confirm").removeClass('delete_data');
});

// Step 2 — Second click on "Confirm"
$(document).on('click', '.confirm_delete', function(e) {
    e.preventDefault();

    var $this = $(this);
    var deletedrow = $this.closest('tr'); // row to delete
    var link = $this.attr('href'); // URL for delete request

    // Call apiRequest for delete
    apiRequest($this, link, '', 'GET', function(response) {
        if (response.success) {
            removeRow(deletedrow);
            $.notify(response.message, "success");
        } else {
            $.notify(response.err || "Delete failed", "error");
        }
    });
});

// Step 3 — Remove Row Function
function removeRow(deletedrow) {
    $(deletedrow).fadeOut('slow', function() {
        $(this).remove();
    });
}

// Step 4 — Updated apiRequest with callback support
function apiRequest($form, url, data, type = "POST", callback) {
    $.ajax({
        type: type,
        url: url,
        data: data || '',
        processData: false,
        contentType: false,
        success: function(data) {
            try {
                if (typeof data === "string") {
                    data = JSON.parse(data);
                }
            } catch (e) {
                console.error("Invalid JSON response", e);
            }

            if (typeof callback === "function") {
                callback(data);
            }
        },
        error: function(jqXHR, textStatus) {
            $.notify('System error. Please try again.', "error");
        },
        timeout: 60000,
        dataType: 'json'
    });
}
</script>
