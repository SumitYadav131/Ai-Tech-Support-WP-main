jQuery(document).ready(function ($) {

    const $toggle = $('#ai-chat-toggle');
    const $container = $('#ai-chat-container');
    const $askBtn = $('#ai-ask');
    // const $messages = $('#ai-chat-messages');

    // made temporary change 
    // const $messages = $('#ai-chat-messages');
    const $messages = $('#ai-chat-messagess');
    const $textarea = $('#ai-question');

    let currentSessionId = null;

    // Toggle chat window
    $toggle.on('click', function () {
        $container.toggleClass('ai-chat-active');
    });

    // Ask button click
    $askBtn.on('click', function () {

        const question = $textarea.val().trim();

        if (!question) return;

        console.log(question);

        $messages.append(
            '<div class="ai-user"><strong>You:</strong> ' + question + '</div>'
        );

        $textarea.val('');

        $.ajax({
            url: ai_support_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ai_support_request',
                security: ai_support_obj.nonce,
                question: question,
                session_id: currentSessionId
            },
            success: function (data) {

                if (data.success) {
                    currentSessionId = data.data.session_id;
                    $messages.append(
                        '<div class="ai-bot"><strong>AI:</strong> ' +
                        formatAIResponse(data.data.response) +
                        '</div>'
                    );
                } else {
                    $messages.append(
                        '<div class="ai-bot"><strong>Error:</strong> ' +
                        formatAIResponse(data.data.response) +
                        '</div>'
                    );
                }

                $textarea.val('');
                $messages.scrollTop($messages[0].scrollHeight);
            },
            error: function () {
                $messages.append(
                    '<div class="ai-bot"><strong>Error:</strong> Something went wrong.</div>'
                );
            }
        });

    });

    // Send on Enter
    $textarea.on('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            $askBtn.trigger('click');
        }
    });

    // Format AI Response
    function formatAIResponse(text) {

        // Remove AI prefix
        text = text.replace(/^AI:\s*/i, '');

        // Horizontal rules ---
        text = text.replace(/^---$/gm, '<hr>');

        // Headings ### Title
        text = text.replace(/### (.*?)\n/g, '<h3>$1</h3>');

        // Bold **text**
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // Code blocks ``` ```
        text = text.replace(/```([\s\S]*?)```/g, '<pre>$1</pre>');

        // Numbered lists
        text = text.replace(/(?:^|\n)(\d+\..+(?:\n\d+\..+)*)/g, function (match) {
            let items = match.trim().split('\n');
            let list = '<ol>';
            $.each(items, function (_, item) {
                list += '<li>' + item.replace(/^\d+\.\s*/, '') + '</li>';
            });
            list += '</ol>';
            return list;
        });

        // Bullet lists
        text = text.replace(/(?:^|\n)(- .+(?:\n- .+)*)/g, function (match) {
            let items = match.trim().split('\n');
            let list = '<ul>';
            $.each(items, function (_, item) {
                list += '<li>' + item.replace('- ', '') + '</li>';
            });
            list += '</ul>';
            return list;
        });

        // Line breaks
        text = text.replace(/\n/g, '<br>');

        return text;
    }

    $('.ai-option').on('click', function () {

        $selectedSupport = $(this).data('type');

        $('.ai-option').removeClass('active');

        $(this).addClass('active');

        if ($(this).hasClass('active')) {
            $('#ai-support-options').hide();
        }

        let defaultQuestion = '';

        switch ($selectedSupport) {

            case 'technical':
                defaultQuestion = 'How do I fix a broken WordPress site?';
                break;

            case 'broken':
                defaultQuestion = 'My site is broken. What should I do?';
                break;

            case 'speed':
                defaultQuestion = 'How can I improve my WordPress site speed?';
                break;

            case 'security':
                defaultQuestion = 'How do I secure my WordPress site?';
                break;
        }


        if (!defaultQuestion) return;

        console.log(defaultQuestion);

        $messages.append(
            '<div class="ai-user"><strong>You:</strong> ' + defaultQuestion + '</div>'
        );

        $.ajax({
            url: ai_support_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ai_support_request',
                security: ai_support_obj.nonce,
                question: defaultQuestion,
                supportType: $selectedSupport,
                session_id: currentSessionId
            },
            beforeSend: function () {

                // Remove any old loader (safety)
                $('.ai-typing').remove();

                // Show typing indicator
                $messages.append(
                    '<div class="ai-bot ai-typing"><strong>AI:</strong> Typing...</div>'
                );

                $messages.scrollTop($messages[0].scrollHeight);
            },
            success: function (data) {

                if (data.success) {
                    currentSessionId = data.data.session_id;

                    $('.ai-typing').remove();
                    $messages.append(
                        '<div class="ai-bot"><strong>AI:</strong> ' +
                        formatAIResponse(data.data.response) +
                        '</div>'
                    );

                } else {
                    $('.ai-typing').remove();
                    $messages.append(
                        '<div class="ai-bot"><strong>Error:</strong> ' +
                        formatAIResponse(data.data.response) +
                        '</div>'
                    );
                }

                $textarea.val('');
                $messages.scrollTop($messages[0].scrollHeight);
            },
            error: function () {
                $messages.append(
                    '<div class="ai-bot"><strong>Error:</strong> Something went wrong.</div>'
                );
            }
        });

    });

    // Load previous sessions
    function load_sessions() {

        $.ajax({
            url: ai_support_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_sessions'
            },
            success: function (res) {

                var sidebar = $('#ai-chat-sidebar');
                sidebar.html('');

                if (res.success && res.data.length > 0) {

                    res.data.forEach(function (session, index) {

                        sidebar.append(
                            '<div class="support-session-item" data-id="' + session.id + '">' +
                            '<div class="session-avatar">' +
                            '<img src="https://i.pravatar.cc/40?u=' + session.id + '">' +
                            '</div>' +

                            '<div class="session-content">' +
                            '<div class="session-title">' +
                            (session.first_message ? session.first_message : 'Chat ' + session.id) +

                            '<div class="session-support-type">' + '(' + (session.support_type ? session.support_type : 'General') + ')' + '</div>' +
                            '</div>' +
                            '</div>' + '</div>'
                        );

                    });

                } else {
                    sidebar.append('<div class="no-session">No sessions found</div>');
                }

            },
            error: function (error) {
                console.log('AJAX Error:', error);
            }
        });

    }
    load_sessions();

    // Click session to open messages
    jQuery(document).on('click', '.support-session-item', function () {
        var sessionId = jQuery(this).data('id');
        jQuery.ajax({
            url: ai_support_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_session_messages',
                session_id: sessionId,
            },
            success: function (res) {
                if (res.success) {
                    var messages = res.data;
                    var $messages = jQuery('#ai-chat-messages');
                    var sidebar = jQuery('#ai-chat-sidebar');
                    $messages.html('');
                    sidebar.css('display', 'none');
                    messages.forEach(function (msg) {
                        $messages.append(
                            '<div class="ai-' + msg.role + '">' +
                            '<strong>' + (msg.role === 'user' ? 'You:' : 'AI:') + '</strong> ' +
                            formatAIResponse(msg.message) +
                            '</div>'
                        );
                    });

                    $messages.scrollTop($messages[0].scrollHeight);
                }
            },
            error: function (error) {
                console.log('Error fetching session messages:', error);
            }
        });

    });
    jQuery('.back-btn-arrow').hide();
    jQuery(document).on('click', '#ai-toogle-btn', async function (e) {
        await jQuery('#ai-chat-sidebar').toggle();
        jQuery('#ai-chat-messages').hide();
        jQuery('.back-btn-arrow').hide();
    });

    jQuery(document).on('click', '.support-session-item', async function (e) {
        await jQuery('#ai-chat-sidebar').toggle();
        jQuery('#ai-chat-messages').show();
        jQuery('.back-btn-arrow').toggle();
    });


});




