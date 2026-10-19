/* Tokens are rendered by the server, so cookies can remain HttpOnly. */
$(function () {
    var name = $('meta[name="csrf-token-name"]').attr('content');
    var token = $('meta[name="csrf-token"]').attr('content');
    if (!name || !token) return;
    function updateForms() {
        $('form').each(function () {
            if (this.method.toLowerCase() === 'get') return;
            var input = $(this).find('input[name="' + name + '"]');
            if (!input.length) input = $('<input>', { type: 'hidden', name: name }).appendTo(this);
            input.val(token);
        });
    }
    updateForms();
    $.ajaxPrefilter(function (options, original, xhr) {
        if (options.crossDomain || /^(GET|HEAD|OPTIONS)$/i.test(options.type)) return;
        if (window.FormData && options.data instanceof FormData) {
            options.data.set(name, token);
        } else if (typeof options.data === 'string') {
            var params = new URLSearchParams(options.data);
            params.set(name, token);
            options.data = params.toString();
        } else {
            options.data = options.data || {};
            options.data[name] = token;
        }
        xhr.always(function () {
            var next = xhr.getResponseHeader('X-CSRF-Token');
            if (next) {
                token = next;
                $('meta[name="csrf-token"]').attr('content', next);
                updateForms();
            }
        });
    });
});
