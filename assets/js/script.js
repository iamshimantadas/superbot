// responsible to show the chatbot 
const chatbotToggler = document.querySelector(".chatbot-toggler");
const closeBtn = document.querySelector(".close-btn");
closeBtn.addEventListener("click", () => document.body.classList.remove("show-chatbot"));
chatbotToggler.addEventListener("click", () => document.body.classList.toggle("show-chatbot"));
// end of chatbot toggle


jQuery(document).ready(function () {

    jQuery('#send-btn').on('click', function (e) {
        e.preventDefault();

        const userInput = jQuery('.chat-input textarea').val().trim();
        if (!userInput) return;

        // Append user's message to chatbox
        const userMessage = `<li class="chat outgoing"><p>${userInput}</p></li>`;
        jQuery('.chatbox').append(userMessage);

        // Clear the input textarea
        jQuery('.chat-input textarea').val('');

        // Append "Thinking..." message while waiting for response
        const thinkingMessage = `<li class="chat incoming"><span class="material-symbols-outlined">smart_toy</span><p>Thinking...</p></li>`;
        jQuery('.chatbox').append(thinkingMessage);

        jQuery.ajax({
            type: 'POST',
            url: mc_bot_ajax.ajax_url,
            data: {
                action: 'search_answer',
                userInput: userInput
            },
            success: function (response) {
                // Remove "Thinking..." message
                jQuery('.chatbox li.incoming:last').remove();

                // Append bot's response to chatbox
                const botResponse = `<li class="chat incoming"><span class="material-symbols-outlined">smart_toy</span><div>${response} </div> </li>`;
                jQuery('.chatbox').append(botResponse);

                // Scroll to the bottom of the chatbox
                jQuery('.chatbox').scrollTop(jQuery('.chatbox')[0].scrollHeight);
            }
        });
    });
});




/** admin/replies.php SCRIPT START */
// this basically gets parameters from the page url and display inside add query modal - examplemodal query field
jQuery(document).ready(function () {
    const urlParams = new URLSearchParams(window.location.search);
    const queryParam = urlParams.get('query');
    jQuery('#query').val(queryParam);
    if (urlParams.get('showModal') === 'true') {
        var myModal = new bootstrap.Modal(document.getElementById('exampleModal'));
        myModal.show();
    }
});


// import csv
jQuery(document).ready(function () {
    jQuery('#import-csv-btn').click(function () {
        jQuery('#csv-file-input').click(); // Trigger the hidden file input click
    });

    jQuery('#csv-file-input').change(function (e) {
        let file = e.target.files[0];

        if (file) {
            let formData = new FormData();
            formData.append('file', file);
            formData.append('action', 'import_csv'); // Action name for AJAX

            jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                data: formData,
                processData: false, // Prevent jQuery from processing the data
                contentType: false, // Prevent jQuery from setting content type
                success: function (res) {
                    alert('CSV imported successfully!');
                    window.location.reload();
                },
                error: function (res) {
                    console.error(res);
                    alert('Failed to import CSV.');
                }
            });
        }
    });
});


// export csv 
jQuery(document).ready(function () {
    jQuery('#export-csv-btn').click(function () {
        jQuery.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'export_csv' },
            success: function (res) {
                // Create a download link and trigger it automatically
                let blob = new Blob([res], { type: 'text/csv;charset=utf-8;' });
                let link = document.createElement("a");
                let url = URL.createObjectURL(blob);
                link.setAttribute("href", url);
                link.setAttribute("download", "data.csv");
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            },
            error: function (res) {
                console.error(res);
            }
        });
    });
});



jQuery('#document').ready(function ($) {
    // loading summernote on mount
    $('#editor1').summernote();
    jQuery('#myTable').DataTable({
        order: [[0, 'desc']]
    });

    // tags containers active
    jQuery('#query_tags').on('keypress', function (event) {
        if (event.key === ',' || event.key === 'Enter') {
            event.preventDefault();
            let inputValue = $(this).val().trim();
            if (inputValue) {
                addTag(inputValue);
                jQuery(this).val('');
            }
        }
    });

    function addTag(tagText) {
        let tagHtml = `<span class="tag">${tagText}<span class="remove-tag">&times;</span></span>`;
        jQuery('#tags-container').append(tagHtml);
    }
    jQuery('#tags-container').on('click', '.remove-tag', function () {
        jQuery(this).parent('.tag').remove();
    });
});


jQuery('#save_query_btn').click(function () {
    let query = jQuery('#query').val();
    let editor = jQuery('#editor1').summernote('code');
    let tags = [];
    jQuery('#tags-container .tag').each(function () {
        tags.push(jQuery(this).clone().children().remove().end().text().trim());
    });
    let allTags = tags.join(', ');
    if (allTags.length == 0) {
        allTags = null;
    }


    if (query && editor) {
        jQuery.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'save_query', query: query, editor: editor, tags: allTags },
            success: function (res) {
                console.log(res);
                if (res == "success") {

                    Swal.fire({
                        title: "Record Saved!",
                        icon: "success"
                    });

                    jQuery('#exampleModal').modal('hide');
                    jQuery('#query').val("");
                    window.location.reload();
                    // redirecting to the this page again! after successful data insert!
                    window.location.href = REDIRECT_PAGE;


                } else if (res == "error") {
                    Swal.fire({
                        title: "Record Not Saved!",
                        icon: "error"
                    });
                }
            },
            error: function (res) {
                console.error(res);
            }
        });
    } else {
        if (!jQuery('#query').val()) {
            alert("Please enter query");
        }

        if (!jQuery('#editor1').summernote('code')) {
            alert("Please enter response");
        }
    }


});


jQuery('#update_query_btn').click(function () {
    let query = jQuery('#chat-question').val();
    let editor = jQuery('#editor2').summernote('code');
    let tags = [];
    jQuery('#update-tags-container .tag').each(function () {
        tags.push(jQuery(this).clone().children().remove().end().text().trim());
    });
    let allTags = tags.join(', ');
    if (allTags.length == 0) {
        allTags = null;
    }
    let chatid = jQuery('#chat-id').val();

    // console.warn("update data: ", query, editor, allTags, chatid);

    if (query && editor) {
        jQuery.ajax({
            url: ajaxurl,
            method: 'POST',
            data: { action: 'update_query', query: query, editor: editor, tags: allTags, chatid: chatid },
            success: function (res) {
                console.warn(res);
                if (res == "success") {

                    Swal.fire({
                        title: "Record Updated!",
                        icon: "success"
                    });
                    window.location.reload();
                    jQuery('#updateChatModal').modal('hide');
                    jQuery('#query2').val("");


                } else if (res == "error") {
                    Swal.fire({
                        title: "Record Not Updated!",
                        icon: "error"
                    });
                }
            },
            error: function (res) {
                console.error(res);
            }
        });
    } else {
        if (!jQuery('#chat-question').val()) {
            alert("Please enter query");
        }

        if (!jQuery('#editor2').summernote('code')) {
            alert("Please enter response");
        }
    }
});

function updateForm(id) {

    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
            action: 'get_reply',
            id: id
        },
        success: function (response) {
            // Parse the JSON response
            const data = JSON.parse(response);
            if (data.error) {
                alert('Error: ' + data.error);
                return;
            }

            // Populate the modal with the retrieved data
            jQuery('#chat-id').val(id);
            jQuery('#chat-question').val(data.question);
            jQuery('#editor2').summernote('code', data.answer);

            jQuery('#update-tags-container').empty();

            // Populate tags
            data.tags.forEach(tag => {
                addUpdateTag(tag);
            });

            // Show the modal
            jQuery('#updateChatModal').modal('show');
        },
        error: function (xhr, status, error) {
            console.error('Error fetching chat details:', error);
            alert('An error occurred while fetching chat details.');
        }
    });



    // Function to add a tag to the update modal
    function addUpdateTag(tagText) {
        let tagHtml = `<span class="tag">${tagText}<span class="remove-tag">&times;</span></span>`;
        jQuery('#update-tags-container').append(tagHtml);
    }

    // Handle tag input in the update modal
    jQuery('#chat-tags').on('keypress', function (event) {
        if (event.key === ',' || event.key === 'Enter') {
            event.preventDefault();
            let inputValue = jQuery(this).val().trim();
            if (inputValue) {
                addUpdateTag(inputValue);
                jQuery(this).val('');
            }
        }
    });

    // Remove tag when clicking on the remove icon
    jQuery('#update-tags-container').on('click', '.remove-tag', function () {
        jQuery(this).parent('.tag').remove();
    });
}

function deleteForm(id, chatquestion) {
    let chatid = id;


    const swalWithBootstrapButtons = Swal.mixin({
        customClass: {
            confirmButton: "btn btn-success",
            cancelButton: "btn btn-danger custom-cancel-button"
        },
        buttonsStyling: false
    });
    swalWithBootstrapButtons.fire({
        title: "Delete Query: " + chatquestion,
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "No, cancel!",
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {

            // ajax for delete record
            jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                data: { action: 'delete_query', chatid: chatid },
                success: function (res) {
                    console.warn(res);
                    if (res == "success") {
                        swalWithBootstrapButtons.fire({
                            title: "Deleted!",
                            icon: "success"
                        });

                    } else if (res == "error") {
                        Swal.fire({
                            title: "Record Not Deleted!",
                            icon: "error"
                        });
                    }
                    window.location.reload();
                },
                error: function (res) {
                    console.error(res);
                }
            });

        } else if (
            /* Read more about handling dismissals below */
            result.dismiss === Swal.DismissReason.cancel
        ) {
            swalWithBootstrapButtons.fire({
                title: "Record Not Deleted",
                // text: "Your imaginary file is safe :)",
                icon: "error"
            });

        }
    });

}

/** admin/replies.php SCRIPT END */


/**
 * admin/dashboard.php START 
 */



/** admin/dashboard.php END  */


/** settings.php ajax script start */
jQuery(document).ready(function () {
    // Fetch latest record if present
    jQuery.ajax({
        url: ajaxurl,
        method: 'POST',
        data: { action: 'view_settings' },
        success: function (res) {
            let obj = JSON.parse(res)[0];
            jQuery('#gemini-key').val(obj.gemini_key);
            jQuery('#contact-us-page').val(obj.contact_us_link);
            jQuery('#business-name').val(obj.business_name);
            jQuery('#business-description').val(obj.business_description);
            jQuery('#restrictions').val(obj.restriction);
        },
        error: function () {
            console.error("An error occurred! Contact system admin.");
        }
    });

    // Save settings
    jQuery('#save-btn').click(function () {
        let data = {
            action: 'save_settings',
            key: jQuery('#gemini-key').val(),
            contact: jQuery('#contact-us-page').val(),
            business_name: jQuery('#business-name').val(),
            description: jQuery('#business-description').val(),
            restriction: jQuery('#restrictions').val()
        };

        jQuery.ajax({
            url: ajaxurl,
            method: 'POST',
            data: data,
            success: function (res) {
                if (res === "success") {
                    Swal.fire({ title: "New Record Saved!", icon: "success" });
                    window.location.reload();
                } else {
                    Swal.fire({ title: "Record Not Saved!", icon: "error" });
                }
            },
            error: function (res) {
                console.error(res);
            }
        });
    });
});
/** settings.php ajax script end */