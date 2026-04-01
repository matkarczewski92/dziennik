<?php

namespace App\Livewire\Admin;

use App\Models\Feed;
use App\Services\ActivityLogger;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class FeedsPanel extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingFeedId = null;

    public ?int $deleteFeedId = null;

    public string $name = '';

    public string $feedingInterval = '';

    public string $amount = '';

    public string $lastPrice = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function feeds()
    {
        return Feed::query()
            ->withCount('feedings')
            ->when($this->search !== '', function ($query): void {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->orderBy('id')
            ->paginate(12);
    }

    public function startEdit(int $id): void
    {
        $this->authorizeAdmin();

        $feed = Feed::query()->findOrFail($id);

        $this->editingFeedId = $feed->id;
        $this->name = (string) $feed->name;
        $this->feedingInterval = (string) $feed->feeding_interval;
        $this->amount = (string) $feed->amount;
        $this->lastPrice = number_format((float) $feed->last_price, 2, '.', '');
    }

    public function cancelEdit(): void
    {
        $this->editingFeedId = null;
        $this->resetForm();
    }

    public function save(ActivityLogger $activityLogger): void
    {
        $admin = $this->authorizeAdmin();

        $this->lastPrice = str_replace(',', '.', trim($this->lastPrice));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'feedingInterval' => ['required', 'integer', 'min:0'],
            'amount' => ['required', 'integer', 'min:0'],
            'lastPrice' => ['required', 'numeric', 'min:0'],
        ], [
            'name.required' => 'Podaj nazwe karmy.',
            'name.max' => 'Nazwa karmy moze miec maksymalnie :max znakow.',
            'feedingInterval.required' => 'Podaj interwal karmienia.',
            'feedingInterval.integer' => 'Interwal karmienia musi byc liczba calkowita.',
            'feedingInterval.min' => 'Interwal karmienia nie moze byc mniejszy niz :min.',
            'amount.required' => 'Podaj stan magazynowy.',
            'amount.integer' => 'Stan magazynowy musi byc liczba calkowita.',
            'amount.min' => 'Stan magazynowy nie moze byc mniejszy niz :min.',
            'lastPrice.required' => 'Podaj ostatnia cene.',
            'lastPrice.numeric' => 'Ostatnia cena musi byc liczba.',
            'lastPrice.min' => 'Ostatnia cena nie moze byc mniejsza niz :min.',
        ]);

        $feed = $this->editingFeedId
            ? Feed::query()->findOrFail($this->editingFeedId)
            : new Feed();

        $isNew = ! $feed->exists;
        $oldValues = $feed->exists ? $feed->only(['name', 'feeding_interval', 'amount', 'last_price']) : [];

        $feed->forceFill([
            'name' => trim((string) $validated['name']),
            'feeding_interval' => (int) $validated['feedingInterval'],
            'amount' => (int) $validated['amount'],
            'last_price' => number_format((float) $validated['lastPrice'], 2, '.', ''),
        ])->save();

        $activityLogger->log(
            $isNew ? 'admin.feed.create' : 'admin.feed.update',
            $admin,
            $admin,
            $feed,
            $isNew
                ? [
                    'name' => $feed->name,
                    'feeding_interval' => $feed->feeding_interval,
                    'amount' => $feed->amount,
                    'last_price' => $feed->last_price,
                ]
                : [
                    'old' => $oldValues,
                    'new' => $feed->only(['name', 'feeding_interval', 'amount', 'last_price']),
                ],
        );

        $this->cancelEdit();
        session()->flash('success', $isNew ? 'Nowy wpis karmy zostal dodany.' : 'Wpis karmy zostal zaktualizowany.');
    }

    public function confirmDelete(int $id): void
    {
        $this->authorizeAdmin();

        $this->deleteFeedId = $id;
    }

    public function cancelDelete(): void
    {
        $this->deleteFeedId = null;
    }

    public function deleteFeed(ActivityLogger $activityLogger): void
    {
        $admin = $this->authorizeAdmin();
        abort_if(! $this->deleteFeedId, 404);

        $feed = Feed::query()
            ->withCount('feedings')
            ->findOrFail($this->deleteFeedId);

        $activityLogger->log('admin.feed.delete', $admin, $admin, $feed, [
            'name' => $feed->name,
            'feeding_interval' => $feed->feeding_interval,
            'amount' => $feed->amount,
            'last_price' => $feed->last_price,
            'feedings_count' => $feed->feedings_count,
        ]);

        $feed->delete();

        if ($this->editingFeedId === $this->deleteFeedId) {
            $this->cancelEdit();
        }

        $this->cancelDelete();
        session()->flash('success', 'Wpis karmy zostal usuniety.');
    }

    public function render()
    {
        $deleteTarget = $this->deleteFeedId
            ? Feed::query()->withCount('feedings')->find($this->deleteFeedId)
            : null;

        return view('livewire.admin.feeds-panel', [
            'feeds' => $this->feeds,
            'deleteTarget' => $deleteTarget,
        ]);
    }

    protected function authorizeAdmin()
    {
        $admin = auth()->user();

        abort_unless($admin && $admin->hasRole('admin'), 403);

        return $admin;
    }

    protected function resetForm(): void
    {
        $this->name = '';
        $this->feedingInterval = '';
        $this->amount = '';
        $this->lastPrice = '';
    }
}
