<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public const STATUSES = ['new', 'in_progress', 'resolved', 'closed'];

    protected $fillable = [
        'created_by',
        'assigned_to',
        'category_id',
        'title',
        'description',
        'priority',
        'category',
        'status',
        'due_at',
        'summary',
        'suggested_next_action',
        'summary_source',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'assigned_to' => 'integer',
        'category_id' => 'integer',
        'due_at' => 'datetime',
    ];

    protected $appends = [
        'requires_escalation',
    ];

    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $builder, string $status) => $builder->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $builder, string $priority) => $builder->where('priority', $priority))
            ->when($filters['category_id'] ?? null, fn (Builder $builder, string|int $categoryId) => $builder->where('category_id', $categoryId));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    protected function requiresEscalation(): Attribute
    {
        return Attribute::get(function (): bool {
            if (! in_array($this->status, ['new', 'in_progress'], true)) {
                return false;
            }

            if ($this->due_at?->isPast() && in_array($this->priority, ['high', 'critical'], true)) {
                return true;
            }

            return in_array($this->priority, ['high', 'critical'], true)
                && $this->created_at?->lt(now()->subHours(24));
        });
    }
}
