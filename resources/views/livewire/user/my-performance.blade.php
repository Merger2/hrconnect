<div class="user-page-shell">
    <div class="user-page-container user-page-container--wide">
        <section aria-labelledby="my-performance-title" class="user-page-surface performance-page" @unless($showSelfAssessmentModal) wire:poll.visible.20s @endunless>
            <x-user.page-header
                :back-href="route('home')"
                :title="__('My Performance')"
                title-id="my-performance-title"
                class="border-b-0">
                <x-slot name="actions">
                    <span class="performance-live-pill" aria-label="{{ __('Auto refresh') }}">
                        <span></span>
                        {{ __('Live') }}
                    </span>
                </x-slot>
            </x-user.page-header>

            <div class="user-page-body pt-0">
                <x-feedback.alert-messages />

                @php
                    $latestAppraisal = $appraisals->first();
                    $completedCount = $appraisals->where('status', 'completed')->count();
                    $pendingCount = $appraisals->whereIn('status', ['draft', 'self_assessment', 'manager_review', '1on1_scheduled'])->count();
                    $latestScore = $latestAppraisal?->status === 'completed' ? (int) ($latestAppraisal->final_score ?? 0) : null;
                @endphp

                <div class="performance-hero">
                    <div class="performance-hero__content">
                        <p class="performance-eyebrow">{{ __('Performance') }}</p>
                        <h2 class="performance-hero__title">
                            {{ $latestAppraisal ? \Carbon\Carbon::createFromDate($latestAppraisal->period_year, $latestAppraisal->period_month, 1)->translatedFormat('F Y') : __('No review period yet') }}
                        </h2>
                        <p class="performance-hero__copy">
                            {{ __('Track review progress, self assessment, 1-on-1 schedule, and final score from one place.') }}
                        </p>
                    </div>

                    <div class="performance-score-ring" aria-label="{{ __('Final Score') }}">
                        <span>{{ $latestScore !== null ? $latestScore : '—' }}</span>
                        <small>{{ __('Score') }}</small>
                    </div>
                </div>

                <div class="performance-summary" aria-label="{{ __('Performance summary') }}">
                    <div class="performance-summary__item">
                        <span>{{ __('Total') }}</span>
                        <strong>{{ $appraisals->count() }}</strong>
                    </div>
                    <div class="performance-summary__item">
                        <span>{{ __('In Progress') }}</span>
                        <strong>{{ $pendingCount }}</strong>
                    </div>
                    <div class="performance-summary__item">
                        <span>{{ __('Completed') }}</span>
                        <strong>{{ $completedCount }}</strong>
                    </div>
                </div>

                @if($appraisals->isEmpty())
                    <div class="user-empty-state">
                        <div class="user-empty-state__icon">
                            <x-heroicon-o-chart-pie class="h-8 w-8" />
                        </div>
                        <h3 class="user-empty-state__title">{{ __('No performance reviews found.') }}</h3>
                        <p class="user-empty-state__copy">{{ __('Your managers have not initiated any appraisals yet.') }}</p>
                    </div>
                @else
                    <div class="performance-timeline">
                        @foreach($appraisals as $appraisal)
                            @php
                                $period = \Carbon\Carbon::createFromDate($appraisal->period_year, $appraisal->period_month, 1);
                                $statusTone = match ($appraisal->status) {
                                    'completed' => 'performance-status--success',
                                    'manager_review', '1on1_scheduled' => 'performance-status--info',
                                    'self_assessment' => 'performance-status--warning',
                                    default => 'performance-status--neutral',
                                };
                                $statusLabel = match ($appraisal->status) {
                                    'self_assessment' => __('Self Assessment'),
                                    'manager_review' => __('Manager Reviewing'),
                                    '1on1_scheduled' => __('1-on-1 Meeting Scheduled'),
                                    'completed' => __('Completed'),
                                    default => __(str($appraisal->status)->replace('_', ' ')->headline()->toString()),
                                };
                                $score = $appraisal->status === 'completed' ? (int) ($appraisal->final_score ?? 0) : null;
                            @endphp

                            <article class="performance-card">
                                <div class="performance-card__top">
                                    <div class="performance-card__period">
                                        <span>{{ $period->format('M') }}</span>
                                        <strong>{{ $period->format('Y') }}</strong>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="performance-card__heading">
                                            <h3>{{ $period->translatedFormat('F Y') }}</h3>
                                            <span class="performance-status {{ $statusTone }}">{{ $statusLabel }}</span>
                                        </div>
                                        <p class="performance-card__meta">
                                            <x-heroicon-o-user-circle class="h-4 w-4" />
                                            {{ __('Evaluator') }}:
                                            <strong>{{ $appraisal->evaluator?->name ?? __('Not assigned yet') }}</strong>
                                        </p>
                                    </div>
                                </div>

                                <div class="performance-card__details">
                                    <div>
                                        <span>{{ __('Final Score') }}</span>
                                        <strong>{{ $score !== null ? $score.'/100' : '—' }}</strong>
                                    </div>
                                    <div>
                                        <span>{{ __('Meeting') }}</span>
                                        <strong>{{ $appraisal->meeting_date?->translatedFormat('d M Y') ?? __('Not scheduled') }}</strong>
                                    </div>
                                </div>

                                @if($appraisal->meeting_link || ($appraisal->status === 'completed' && $appraisal->employee_acknowledgement))
                                    <div class="performance-card__note">
                                        @if($appraisal->meeting_link)
                                            <a href="{{ $appraisal->meeting_link }}" target="_blank" rel="noopener noreferrer">
                                                <x-heroicon-o-video-camera class="h-4 w-4" />
                                                {{ __('Join Link') }}
                                            </a>
                                        @endif

                                        @if($appraisal->status === 'completed' && $appraisal->employee_acknowledgement)
                                            <span>
                                                <x-heroicon-o-check-badge class="h-4 w-4" />
                                                {{ __('Acknowledged') }}
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                <div class="performance-card__actions">
                                    @if($appraisal->status === 'self_assessment')
                                        <button type="button" wire:click="openSelfAssessment({{ $appraisal->id }})" class="performance-action performance-action--primary">
                                            <x-heroicon-o-pencil-square class="h-4 w-4" />
                                            {{ __('Assessment') }}
                                        </button>
                                    @elseif($appraisal->status === 'completed' && !$appraisal->employee_acknowledgement)
                                        <button type="button" wire:click="acknowledge({{ $appraisal->id }})" class="performance-action performance-action--primary">
                                            <x-heroicon-o-check class="h-4 w-4" />
                                            {{ __('Acknowledge') }}
                                        </button>
                                    @endif

                                    @if($appraisal->status === 'completed')
                                        <a href="{{ route('appraisal.export-pdf', $appraisal) }}" class="performance-action">
                                            <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                            {{ __('PDF') }}
                                        </a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </div>

    <x-overlays.dialog-modal wire:model.live="showSelfAssessmentModal" maxWidth="4xl">
        <x-slot name="title">
            <div class="performance-modal-title">
                <div class="performance-modal-title__icon">
                    <x-heroicon-o-clipboard-document-check class="h-5 w-5" />
                </div>
                <div>
                    <p>{{ __('Self Assessment') }}</p>
                    <span>{{ __('Please complete your self assessment') }}</span>
                </div>
            </div>
        </x-slot>

        <x-slot name="content">
            <div class="performance-modal">
                <div class="performance-modal__notice">
                    <x-heroicon-o-information-circle class="h-5 w-5" />
                    <p>{{ __('Please rate your performance for each KPI. Use a scale of 1-5. Provide clear details of evidence of achievement to make it easier for the Manager to provide the final evaluation.') }}</p>
                </div>

                @php
                    $evalsToList = $activeAppraisal ? $activeAppraisal->evaluations : collect([]);
                    $groupedEvals = collect($evalsToList)->groupBy(fn($e) => $e->kpiTemplate->kpi_group_id ?? 'ungrouped');
                @endphp

                <div class="performance-assessment-list">
                    @foreach($groupedEvals as $groupId => $groupEvals)
                        @php
                            $group = $groupEvals->first()?->kpiTemplate?->kpiGroup;
                        @endphp

                        <section class="performance-assessment-group">
                            <div class="performance-assessment-group__header">
                                <div>
                                    <p>{{ $group ? $group->name : __('General') }}</p>
                                    <span>{{ __('Weight') }} {{ $group ? $group->weight : 100 }}%</span>
                                </div>
                                <strong>{{ $groupEvals->count() }}</strong>
                            </div>

                            <div class="space-y-3">
                                @foreach($groupEvals as $evaluation)
                                    <article class="performance-kpi-card">
                                        <div class="performance-kpi-card__header">
                                            <h4>{{ $evaluation->kpiTemplate->name ?? __('KPI') }}</h4>
                                            <span>{{ $evaluation->kpiTemplate->weight ?? 0 }}%</span>
                                        </div>

                                        @if($evaluation->kpiTemplate && $evaluation->kpiTemplate->indicator_description)
                                            <div class="performance-kpi-card__indicator">
                                                @foreach(explode("\n", $evaluation->kpiTemplate->indicator_description) as $line)
                                                    @php $line = trim($line); @endphp
                                                    @if($line !== '')
                                                        <p>{{ ltrim($line, '- ') }}</p>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="performance-kpi-card__form">
                                            <div>
                                                <x-forms.label :for="'evidence-'.$evaluation->id" value="{{ __('Evidence of Achievement') }}" class="mb-1.5 block" />
                                                <x-forms.textarea :id="'evidence-'.$evaluation->id" wire:model="evidenceDescriptions.{{ $evaluation->id }}" rows="3"
                                                    placeholder="{{ __('Description of the results you have achieved...') }}" />
                                                <x-forms.input-error for="evidenceDescriptions.{{ $evaluation->id }}" class="mt-1" />
                                            </div>

                                            <div>
                                                <x-forms.label :for="'score-'.$evaluation->id" value="{{ __('Your Score') }}" class="mb-1.5 block" />
                                                <x-forms.select :id="'score-'.$evaluation->id" wire:model="selfScores.{{ $evaluation->id }}">
                                                    <option value="">{{ __('Select Scale') }}</option>
                                                    <option value="1">1 · {{ __('Very Poor') }}</option>
                                                    <option value="2">2 · {{ __('Poor') }}</option>
                                                    <option value="3">3 · {{ __('Fair') }}</option>
                                                    <option value="4">4 · {{ __('Good') }}</option>
                                                    <option value="5">5 · {{ __('Outstanding') }}</option>
                                                </x-forms.select>
                                                <x-forms.input-error for="selfScores.{{ $evaluation->id }}" class="mt-1" />
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                <div class="performance-notes">
                    <x-forms.label for="employeeNotes" value="{{ __('Employee Notes') }}" class="mb-1.5 block" />
                    <x-forms.textarea id="employeeNotes" wire:model="employeeNotes" rows="4"
                        placeholder="{{ __('Your opinion on overall performance achievements, challenges, and your expectations going forward...') }}" />
                    <x-forms.input-error for="employeeNotes" class="mt-1" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <div class="performance-modal-actions">
                <button type="button" wire:click="$set('showSelfAssessmentModal', false)" class="user-secondary-action">
                    {{ __('Close') }}
                </button>

                <button type="button" class="user-primary-action" wire:click="submitSelfAssessment" wire:confirm="{{ __('Are you sure? Once submitted, you cannot change this assessment.') }}" aria-label="{{ __('Submit') }}">
                    <x-heroicon-o-paper-airplane class="h-4 w-4" />
                    {{ __('Submit') }}
                </button>
            </div>
        </x-slot>
    </x-overlays.dialog-modal>
</div>
