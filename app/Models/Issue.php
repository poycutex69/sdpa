<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Issue extends Model
{
    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public const CATEGORIES = ['technical', 'billing', 'operations', 'other'];

    public const STATUSES = ['new', 'in_progress', 'resolved', 'closed'];

    protected $fillable = [
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
            ->when($filters['category'] ?? null, fn (Builder $builder, string $category) => $builder->where('category', $category));
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
