jQuery(document).ready(function ($) {
    $('a.delete[data-slug="super-bot"]').on('click', function (e) {
        e.preventDefault();
        let confirmDelete = confirm("Warning: This will delete all Super Bot data and tables permanently! Are you sure?");
        if (confirmDelete) {
            window.location.href = $(this).attr('href'); // Proceed with deletion
        }
    });
});
