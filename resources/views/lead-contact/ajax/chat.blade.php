<style>
    .lead-chat-card { width: 100%; min-height: calc(100vh - 190px); margin: 0; border: 0; border-radius: 0; box-shadow: none; background: transparent; overflow: visible; display: flex; flex-direction: column; }
    .lead-chat-header { padding: 8px 4px 14px; background: transparent; border-bottom: 1px solid #e7edf6; }
    .lead-chat-header h5 { margin: 0; color: #1d2b43; font-weight: 700; }
    .lead-chat-body { min-height: 0; padding: 14px 0 0; display: flex; flex: 1; flex-direction: column; }
    .lead-chat-thread { min-height: 230px; max-height: none; overflow-y: auto; padding: 4px 2px 14px; flex: 1; }
    .lead-chat-empty { min-height: 210px; display: flex; align-items: center; justify-content: center; color: #8a98ad; font-size: 13px; }
    .lead-chat-bubble-row { display: flex; margin-bottom: 10px; }
    .lead-chat-bubble-row.outbound { justify-content: flex-end; }
    .lead-chat-bubble { max-width: 78%; padding: 8px 10px; border-radius: 12px; background: #f1f5fb; color: #243650; font-size: 13px; overflow-wrap: anywhere; }
    .lead-chat-bubble-row.outbound .lead-chat-bubble { background: #e8efff; border-bottom-right-radius: 4px; }
    .lead-chat-bubble-row.inbound .lead-chat-bubble { border-bottom-left-radius: 4px; }
    .lead-chat-photo { display: block; max-width: 270px; max-height: 300px; border-radius: 8px; cursor: pointer; }
    .lead-chat-caption { margin-top: 6px; white-space: pre-wrap; }
    .lead-chat-bubble-meta { display: block; margin-top: 4px; color: #7b8ba4; font-size: 10px; text-align: right; }
    .lead-chat-compose { border: 1px solid #dbe4f1; border-radius: 20px; padding: 7px 9px; background: #fff; box-shadow: 0 2px 8px rgba(22, 44, 87, .06); }
    .lead-chat-compose-bar { display: flex; align-items: center; gap: 6px; }
    .lead-chat-compose input[type=text] { flex: 1; min-width: 0; border: 0; box-shadow: none; height: 38px; padding: 0 6px; }
    .lead-chat-compose input[type=text]:focus { outline: 0; box-shadow: none; }
    .lead-chat-icon { border: 0; background: transparent; color: #5e6d82; font-size: 18px; width: 32px; height: 34px; padding: 0; }
    .lead-chat-icon:hover { color: #1d6fe8; }
    .lead-chat-photo-name { display: none; margin: 6px 5px 2px; color: #49617f; font-size: 11px; }
    .lead-chat-fallback { padding: 12px 14px; border-radius: 10px; background: #fff8e6; color: #7d5b00; font-size: 13px; }
    @media (max-width: 767px) {
        .lead-chat-card { min-height: calc(100vh - 150px); }
        .lead-chat-thread { min-height: 300px; }
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="lead-chat-card">
            <div class="lead-chat-header d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <h5><i class="fa fa-comments text-primary mr-1"></i> Chat with {{ $leadContact->client_name ?: 'Lead' }}</h5>
                    <small class="text-muted">WhatsApp conversation</small>
                </div>
                @if ($chatPhone)
                    <span class="text-muted f-13 mt-2 mt-md-0"><i class="fa fa-phone mr-1"></i>{{ $chatNumber }}</span>
                @endif
            </div>

            <div class="lead-chat-body">
                @if (!$chatPhone)
                    <div class="alert alert-warning mb-0">This lead does not have a mobile number for WhatsApp chat.</div>
                @else
                    @if (!$chatGatewayConfigured)
                        <div class="lead-chat-fallback mb-3">WhatsApp service is not connected. Start the WhatsApp service to send messages.</div>
                    @endif

                    <div class="lead-chat-thread" id="lead-chat-thread" data-messages-url="{{ route('lead-contact.chat.messages', $leadContact->id) }}">
                        @forelse ($chatMessages as $chatMessage)
                            @php
                                $chatTime = $chatMessage->message_at
                                    ? $chatMessage->message_at->timezone(company()->timezone)->format(company()->date_format . ' ' . company()->time_format)
                                    : '--';
                                $chatMediaUrl = data_get($chatMessage->metadata, 'media_path')
                                    ? route('lead-contact.chat.media', [$leadContact->id, $chatMessage->id])
                                    : null;
                            @endphp
                            <div class="lead-chat-bubble-row {{ $chatMessage->direction }}">
                                <div class="lead-chat-bubble">
                                    @if ($chatMediaUrl)
                                        <a href="{{ $chatMediaUrl }}" target="_blank" rel="noopener"><img class="lead-chat-photo" src="{{ $chatMediaUrl }}" alt="WhatsApp photo"></a>
                                    @endif
                                    @if ($chatMessage->message)
                                        <div class="lead-chat-caption">{{ $chatMessage->message }}</div>
                                    @endif
                                    <span class="lead-chat-bubble-meta">{{ $chatMessage->direction === 'outbound' ? 'You' : $leadContact->client_name }} &middot; {{ $chatTime }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="lead-chat-empty">No messages yet. Type a message or attach a photo to start the conversation.</div>
                        @endforelse
                    </div>

                    <form class="lead-chat-compose js-lead-chat-form" action="{{ route('lead-contact.chat.send', $leadContact->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="photo" class="d-none js-lead-chat-photo" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="lead-chat-compose-bar">
                            <button type="button" class="lead-chat-icon js-lead-chat-attach" title="Attach photo"><i class="fa fa-plus"></i></button>
                            <button type="button" class="lead-chat-icon js-lead-chat-emoji" title="Add emoji">&#9786;</button>
                            <input type="text" name="message" class="form-control" maxlength="1000" placeholder="Type a message">
                            <button type="submit" class="btn btn-primary rounded-pill px-3 js-lead-chat-send" @disabled(!$chatGatewayConfigured)><i class="fa fa-paper-plane"></i></button>
                        </div>
                        <div class="lead-chat-photo-name js-lead-chat-photo-name"></div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const $thread = $('#lead-chat-thread');
        let lastMessageSignature = null;
        let refreshInProgress = false;

        const escapeHtml = function(value) { return $('<div>').text(value || '').html(); };
        const formatTime = function(value) {
            if (!value) return '--';
            const date = new Date(value);
            return Number.isNaN(date.getTime()) ? '--' : date.toLocaleString();
        };

        const renderMessages = function(messages, forceScroll) {
            const thread = $thread[0];
            const wasNearBottom = thread
                ? thread.scrollHeight - thread.scrollTop - thread.clientHeight < 80
                : true;

            if (!messages.length) {
                $thread.html('<div class="lead-chat-empty">No messages yet. Type a message or attach a photo to start the conversation.</div>');
                return;
            }

            $thread.html(messages.map(function(item) {
                const direction = item.direction === 'outbound' ? 'outbound' : 'inbound';
                const sender = direction === 'outbound' ? 'You' : @json($leadContact->client_name ?: 'Lead');
                const photo = item.media_url ? '<a href="' + escapeHtml(item.media_url) + '" target="_blank" rel="noopener"><img class="lead-chat-photo" src="' + escapeHtml(item.media_url) + '" alt="WhatsApp photo"></a>' : '';
                const caption = item.message ? '<div class="lead-chat-caption">' + escapeHtml(item.message) + '</div>' : '';

                return '<div class="lead-chat-bubble-row ' + direction + '"><div class="lead-chat-bubble">' + photo + caption +
                    '<span class="lead-chat-bubble-meta">' + escapeHtml(sender) + ' &middot; ' + escapeHtml(formatTime(item.message_at)) + '</span></div></div>';
            }).join(''));
            if (forceScroll || wasNearBottom) {
                $thread.scrollTop($thread[0].scrollHeight);
            }
        };

        const refreshMessages = function(forceScroll) {
            if (!$thread.length || !document.documentElement.contains($thread[0])) {
                window.clearInterval(window.leadChatRefreshTimer);
                return;
            }
            if (refreshInProgress || document.hidden) return;
            refreshInProgress = true;
            $.ajax({
                url: $thread.data('messages-url'),
                type: 'GET',
                cache: false
            }).done(function(response) {
                if (!response.success || !Array.isArray(response.messages)) return;

                const signature = response.messages.map(function(item) {
                    return [item.id, item.status, item.message_at, item.message, item.media_url].join('|');
                }).join('::');

                if (signature !== lastMessageSignature) {
                    lastMessageSignature = signature;
                    renderMessages(response.messages, !!forceScroll);
                }
            }).always(function() {
                refreshInProgress = false;
            });
        };

        if ($thread.length) {
            $thread.scrollTop($thread[0].scrollHeight);
            refreshMessages(true);
            window.clearInterval(window.leadChatRefreshTimer);
            window.leadChatRefreshTimer = window.setInterval(function() {
                refreshMessages(false);
            }, 2000);

            $(document).off('visibilitychange.leadChat').on('visibilitychange.leadChat', function() {
                if (!document.hidden) refreshMessages(true);
            });
            $(window).off('focus.leadChat').on('focus.leadChat', function() {
                refreshMessages(false);
            });
        }

        $('body').off('click.leadChatAttach').on('click.leadChatAttach', '.js-lead-chat-attach', function() {
            $(this).closest('form').find('.js-lead-chat-photo').trigger('click');
        });

        $('body').off('change.leadChatPhoto').on('change.leadChatPhoto', '.js-lead-chat-photo', function() {
            const file = this.files && this.files[0];
            const $name = $(this).closest('form').find('.js-lead-chat-photo-name');
            $name.text(file ? ('Photo selected: ' + file.name) : '').toggle(!!file);
        });

        $('body').off('click.leadChatEmoji').on('click.leadChatEmoji', '.js-lead-chat-emoji', function() {
            const $input = $(this).closest('form').find('input[name="message"]');
            $input.val($input.val() + '🙂').trigger('focus');
        });

        $('body').off('submit.leadChat').on('submit.leadChat', '.js-lead-chat-form', function(event) {
            event.preventDefault();
            const form = this;
            const $button = $(form).find('.js-lead-chat-send');
            const file = $(form).find('.js-lead-chat-photo')[0].files[0];
            const message = $(form).find('input[name="message"]').val().trim();

            if (!file && !message) {
                if (typeof toastr !== 'undefined') toastr.warning('Type a message or select a photo first.');
                return;
            }

            $button.prop('disabled', true);
            $.ajax({
                url: form.action,
                type: 'POST',
                data: new FormData(form),
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            }).done(function(response) {
                if (response.status === 'success') {
                    form.reset();
                    $(form).find('.js-lead-chat-photo-name').hide().text('');
                    refreshMessages(true);
                    if (typeof toastr !== 'undefined') toastr.success(response.message || 'Message sent successfully.');
                } else if (typeof toastr !== 'undefined') {
                    toastr.error(response.message || 'Unable to send message.');
                }
            }).fail(function(xhr) {
                const validationErrors = xhr.responseJSON && xhr.responseJSON.errors;
                const firstValidationError = validationErrors ? Object.values(validationErrors).flat()[0] : null;
                const error = firstValidationError || (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error));
                if (typeof toastr !== 'undefined') toastr.error(error || 'Unable to send message.');
            }).always(function() { $button.prop('disabled', false); });
        });
    })();
</script>
