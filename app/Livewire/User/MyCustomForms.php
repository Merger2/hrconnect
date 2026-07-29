<?php

namespace App\Livewire\User;

use App\Models\CustomFormSubmission;
use App\Models\CustomFormTemplate;
use App\Support\CustomFormBuilderService;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Jetstream\InteractsWithBanner;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class MyCustomForms extends Component
{
    use InteractsWithBanner;

    protected CustomFormBuilderService $forms;

    public string $selectedTemplateId = '';

    /**
     * @var array<string, mixed>
     */
    public array $responseValues = [];

    public function boot(CustomFormBuilderService $forms): void
    {
        $this->forms = $forms;
    }

    public function selectTemplate(int $templateId): void
    {
        $template = $this->queryTemplates()->whereKey($templateId)->firstOrFail();

        $this->selectedTemplateId = (string) $template->id;
        $this->responseValues = collect($template->fields)
            ->mapWithKeys(fn (array $field): array => [$field['key'] => ''])
            ->all();
    }

    public function submit(): void
    {
        $template = $this->queryTemplates()->whereKey($this->selectedTemplateId)->firstOrFail();

        $this->forms->submit(auth()->user(), $template, $this->responseValues);

        $this->reset(['selectedTemplateId', 'responseValues']);
        $this->banner(__('Form submitted.'));
    }

    public function render()
    {
        $templates = $this->queryTemplates()
            ->with('company:id,name')
            ->orderBy('title')
            ->get();

        $selectedTemplate = $templates->firstWhere('id', (int) $this->selectedTemplateId);

        $submissions = CustomFormSubmission::query()
            ->with('template:id,title,category')
            ->where('submitted_by', auth()->id())
            ->latest()
            ->take(10)
            ->get();

        return view('livewire.user.my-custom-forms', [
            'pollingEnabled' => $this->selectedTemplateId === '',
            'templates' => $templates,
            'selectedTemplate' => $selectedTemplate,
            'submissions' => $submissions,
        ]);
    }

    private function queryTemplates(): Builder
    {
        $user = auth()->user();

        return CustomFormTemplate::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($user): void {
                if ($user->company_id === null) {
                    return;
                }

                $query->where('company_id', $user->company_id);
            });
    }
}
