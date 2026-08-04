@props(['id' => 'addon-lock-modal'])

<div x-data="{
    show: false,
    title: '',
    message: '',
    supportUrl: @js(route('enterprise-support.whatsapp')),
    hwid: '{{ \App\Console\Commands\EnterpriseHwId::generate() }}',
    contactSupport() {
        const text = encodeURIComponent(`Hi, I'm interested in unlocking this premium add-on.\nHWID: ${this.hwid}`);
        window.open(`${this.supportUrl}?text=${text}`, '_blank');
        this.show = false;
    }
}"
@addon-lock.window="show = true; title = $event.detail.title; message = $event.detail.message"
x-cloak
x-show="show"
style="display: none;"
class="relative z-50"
aria-labelledby="modal-title" role="dialog" aria-modal="true">
    
    <div x-show="show" x-transition.opacity class="fixed inset-0 bg-gray-500/75 backdrop-blur-sm transition-opacity"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="show" 
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-on:click.away="show = false"
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-sm border border-gray-100">
                
                <div class="bg-gradient-to-br from-orange-500 to-red-600 px-5 py-4 sm:px-6">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm shadow-inner shrink-0">
                            <x-heroicon-o-lock-closed class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold leading-6 text-white" id="modal-title" x-text="title"></h3>
                            <p class="mt-1 text-sm text-orange-50" x-text="message"></p>
                        </div>
                    </div>
                </div>

                <div class="px-5 py-5 sm:p-6 text-center">
                    <p class="text-sm text-gray-600 mb-4">{{ __('Ready to unlock this premium add-on and streamline your HR operations? Contact support to upgrade today.') }}</p>
                    <button type="button" x-on:click="contactSupport()"
                        class="inline-flex w-full justify-center items-center gap-2 rounded-xl bg-green-500 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-green-600 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600">
                        <x-heroicon-o-chat-bubble-left-ellipsis class="h-5 w-5" />
                        {{ __('Contact Support to Unlock') }}
                    </button>
                    <button type="button" x-on:click="show = false"
                        class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-4 transition">
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
