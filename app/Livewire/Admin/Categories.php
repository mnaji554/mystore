<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\ImageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class Categories extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    #[Url(as: 'edit')]
    public ?int $categoryToEdit = null;

    public array $form = [];

    public $image = null;

    public ?string $currentImage = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Category::class);
        $categoryToEdit = $this->categoryToEdit;
        $this->resetForm();

        if ($categoryToEdit) {
            $this->edit($categoryToEdit);
        }
    }

    public function resetForm(): void
    {
        $this->form = [
            'name_ar' => '', 'name_en' => '', 'slug' => '', 'parent_id' => '', 'description' => '',
            'sort_order' => 0, 'is_active' => true, 'seo_title' => '', 'seo_description' => '', 'seo_keywords' => '',
        ];
        $this->editingId = null;
        $this->categoryToEdit = null;
        $this->image = null;
        $this->currentImage = null;
        $this->showForm = false;
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->authorize('create', Category::class);
        $this->resetForm();
        $this->form['sort_order'] = (int) Category::max('sort_order') + 1;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->authorize('update', $category);

        $this->resetForm();
        foreach (array_keys($this->form) as $key) {
            $this->form[$key] = $category->{$key} ?? '';
        }
        $this->form['parent_id'] = (string) ($category->parent_id ?? '');
        $this->editingId = $id;
        $this->categoryToEdit = $id;
        $this->currentImage = $category->image;
        $this->showForm = true;
    }

    protected function rules(): array
    {
        return [
            'form.name_ar' => ['required', 'string', 'max:150'],
            'form.name_en' => ['nullable', 'string', 'max:150'],
            'form.slug' => ['nullable', 'string', 'max:190', 'regex:/^[\p{L}\p{N}\-_]+$/u', Rule::unique('categories', 'slug')->ignore($this->editingId)],
            'form.parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'form.description' => ['nullable', 'string', 'max:2000'],
            'form.sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
            'form.is_active' => ['boolean'],
            'form.seo_title' => ['nullable', 'string', 'max:190'],
            'form.seo_description' => ['nullable', 'string', 'max:500'],
            'form.seo_keywords' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => 'هذا الحقل مطلوب.', 'unique' => 'الرابط مستخدم مسبقاً.', 'max' => 'القيمة طويلة جداً.',
            'regex' => 'صيغة الرابط غير صحيحة.', 'image' => 'الملف يجب أن يكون صورة.', 'mimes' => 'صيغة الصورة غير مدعومة.',
        ];
    }

    public function save(ImageService $images): void
    {
        $category = $this->editingId ? Category::findOrFail($this->editingId) : null;
        $this->authorize($category ? 'update' : 'create', $category ?? Category::class);

        $this->validate();

        $parentId = $this->form['parent_id'] !== '' ? (int) $this->form['parent_id'] : null;

        if ($category && $parentId && $category->hasInTree($parentId)) {
            $this->addError('form.parent_id', 'لا يمكن جعل التصنيف أباً لنفسه أو لأحد فروعه.');

            return;
        }

        $slug = trim($this->form['slug']) !== ''
            ? Str::lower(trim($this->form['slug']))
            : Str::slug($this->form['name_en'] ?: $this->form['name_ar'], '-', 'ar');
        $base = $slug ?: 'category';
        $candidate = $base;
        $i = 2;
        while (Category::withTrashed()->where('slug', $candidate)->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))->exists()) {
            $candidate = $base.'-'.$i++;
        }

        $data = [
            'parent_id' => $parentId,
            'name_ar' => strip_tags($this->form['name_ar']),
            'name_en' => $this->form['name_en'] ?: null,
            'slug' => $candidate,
            'description' => $this->form['description'] ? strip_tags($this->form['description']) : null,
            'sort_order' => (int) $this->form['sort_order'],
            'is_active' => (bool) $this->form['is_active'],
            'seo_title' => $this->form['seo_title'] ?: null,
            'seo_description' => $this->form['seo_description'] ?: null,
            'seo_keywords' => $this->form['seo_keywords'] ?: null,
        ];

        if ($this->image) {
            $stored = $images->store($this->image, 'categories', 800, 300);
            $images->delete($stored['thumb_path'], $category?->image);
            $data['image'] = $stored['path'];
        }

        ($category ?? new Category)->fill($data)->save();

        $this->resetForm();
        $this->dispatch('notify', message: 'تم حفظ التصنيف', type: 'success');
    }

    public function removeImage(): void
    {
        $category = Category::findOrFail($this->editingId);
        $this->authorize('update', $category);
        app(ImageService::class)->delete($category->image);
        $category->update(['image' => null]);
        $this->currentImage = null;
    }

    public function move(int $id, string $direction): void
    {
        $category = Category::findOrFail($id);
        $this->authorize('update', $category);

        $siblings = Category::where('parent_id', $category->parent_id)->orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $siblings->search(fn ($c) => $c->id === $id);
        $swap = $direction === 'up' ? $index - 1 : $index + 1;

        if ($swap < 0 || $swap >= $siblings->count()) {
            return;
        }

        $ids = $siblings->pluck('id')->all();
        [$ids[$index], $ids[$swap]] = [$ids[$swap], $ids[$index]];

        foreach ($ids as $position => $siblingId) {
            Category::whereKey($siblingId)->update(['sort_order' => $position + 1]);
        }

        Cache::forget('categories.rows');
    }

    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->authorize('delete', $category);

        if ($category->children()->exists()) {
            $this->dispatch('notify', message: 'لا يمكن حذف تصنيف يحتوي على تصنيفات فرعية.', type: 'error');

            return;
        }

        app(ImageService::class)->delete($category->image);
        $category->delete();
        $this->dispatch('notify', message: 'تم حذف التصنيف', type: 'success');
    }

    public function render(CategoryRepository $repository)
    {
        $counts = Category::query()->withCount('products')->pluck('products_count', 'id');

        return view('livewire.admin.categories', [
            'tree' => $repository->flat(),
            'parents' => $repository->flat($this->editingId),
            'counts' => $counts,
            'categories' => Category::query()->get()->keyBy('id'),
        ])->layout('components.layouts.admin', ['title' => 'التصنيفات']);
    }
}
