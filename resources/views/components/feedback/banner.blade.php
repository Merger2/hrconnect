@props(['style' => session('flash.bannerStyle', 'success'), 'message' => session('flash.banner')])

<div
    x-data="{{ json_encode(['style' => $style, 'message' => $message]) }}"
    x-on:banner-message.window="
        style = event.detail.style || 'info';
        message = event.detail.message || '';

        if (message) {
            window.PasPapanAlert?.toast({ icon: style, title: message });
        }
    "
    class="hidden"
    aria-hidden="true"
></div>
