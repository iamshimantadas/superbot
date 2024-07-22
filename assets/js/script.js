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
            url: superbot_ajax.ajax_url,
            data: {
                action: 'search_answer',
                userInput: userInput
            },
            success: function (response) {
                // Remove "Thinking..." message
                jQuery('.chatbox li.incoming:last').remove();

                // Append bot's response to chatbox
                const botResponse = `<li class="chat incoming"><span class="material-symbols-outlined">smart_toy</span><p>${response}</p></li>`;
                jQuery('.chatbox').append(botResponse);

                // Scroll to the bottom of the chatbox
                jQuery('.chatbox').scrollTop(jQuery('.chatbox')[0].scrollHeight);
            }
        });
    });
});