<x-layouts::app.sidebar>
    <flux:heading size="xl" level="1" class="mb-4">{{ __('Clock In / Out') }}</flux:heading>
    <flux:subheading class="mb-6">{{ __('Record your daily attendance') }}</flux:subheading>

    <div class="mx-auto max-w-md text-center">
        <flux:card class="p-8">
            <div class="mb-4 text-5xl font-display font-medium text-ink">08:02 AM</div>
            <p class="text-sm text-body">{{ __('Tuesday, June 23, 2026') }}</p>

            <div class="mt-6 flex justify-center gap-4">
                <flux:button variant="primary" class="min-w-[140px]">
                    {{ __('Clock In') }}
                </flux:button>
                <flux:button class="min-w-[140px]">
                    {{ __('Clock Out') }}
                </flux:button>
            </div>
        </flux:card>

        <flux:card class="mt-6 p-6 text-left">
            <flux:heading size="sm" class="mb-2">{{ __('Today\'s Activity') }}</flux:heading>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-body">{{ __('Clock In') }}</span>
                    <span class="font-medium text-ink">08:02 AM</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-body">{{ __('Break') }}</span>
                    <span class="font-medium text-ink">12:00 - 01:00 PM</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-body">{{ __('Status') }}</span>
                    <flux:badge color="success">{{ __('Active') }}</flux:badge>
                </div>
            </div>
        </flux:card>

        <flux:link href="{{ route('attendance.index') }}" class="mt-4 inline-block" wire:navigate>
            {{ __('View Attendance History') }}
        </flux:link>
    </div>
</x-layouts::app.sidebar>
