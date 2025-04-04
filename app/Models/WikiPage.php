<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\LogsActivity;
use App\Services\WikiContentService;
use Carbon\Carbon;
class WikiPage extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_PUBLISHED = 'published';
    
    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'created_by',
        'last_edited_by',
        'published_at',
        'view_count',
        'current_version',
    ];
    protected $casts = [
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'current_version' => 'integer',
    ];
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PENDING => '等待审核',
            self::STATUS_PUBLISHED => '已发布',
        ];
    }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
                    ->whereNotNull('published_at');
    }
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(WikiCategory::class, 'wiki_page_category');
    }
    public function revisions(): HasMany
    {
        return $this->hasMany(WikiPageRevision::class, 'wiki_page_id')->orderBy('version', 'desc');
    }
    public function issues(): HasMany
    {
        return $this->hasMany(WikiPageIssue::class, 'wiki_page_id');
    }
    public function createRevision(?string $comment = null): WikiPageRevision
    {
        $latestVersion = $this->current_version + 1;
        $previousRevision = $this->revisions()->latest('version')->first();
        $previousContent = $previousRevision ? $previousRevision->content : null;
        $revision = $this->revisions()->create([
            'title' => $this->title,
            'content' => $this->content,
            'comment' => $comment,
            'created_by' => auth()->id(),
            'version' => $latestVersion
        ]);
        $revision->changes = $revision->calculateChanges($previousContent);
        $revision->save();
        $this->update(['current_version' => $latestVersion]);
        return $revision;
    }
    public function revertToVersion(int $version): WikiPageRevision
    {
        $revision = $this->revisions()->where('version', $version)->firstOrFail();
        $this->update([
            'title' => $revision->title,
            'content' => $revision->content,
            'last_edited_by' => auth()->id()
        ]);
        return $this->createRevision("Reverted to version {$version}");
    }
    public function updateReferences(): void
    {
        $contentService = app(WikiContentService::class);
        $this->outgoingReferences()->delete();
        $links = $contentService->parseWikiLinks($this->content);
        foreach ($links as $title) {
            $referencedPage = self::where('title', $title)->first();
            if ($referencedPage && $referencedPage->id !== $this->id) {
                WikiPageReference::create([
                    'source_page_id' => $this->id,
                    'target_page_id' => $referencedPage->id,
                    'context' => $contentService->extractReferenceContext($this->content, $title)
                ]);
            }
        }
    }
    public function outgoingReferences(): HasMany
    {
        return $this->hasMany(WikiPageReference::class, 'source_page_id');
    }
    public function incomingReferences(): HasMany
    {
        return $this->hasMany(WikiPageReference::class, 'target_page_id');
    }
    public function referencedPages(): BelongsToMany
    {
        return $this->belongsToMany(
            WikiPage::class,
            'wiki_page_references',
            'source_page_id',
            'target_page_id'
        )->withTimestamps();
    }
    public function referencedByPages(): BelongsToMany
    {
        return $this->belongsToMany(
            WikiPage::class,
            'wiki_page_references',
            'target_page_id',
            'source_page_id'
        )->withTimestamps();
    }
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wiki_page_follows')
            ->withTimestamps();
    }
    public function isFollowedByUser(?int $userId): bool
    {
        if (!$userId) return false;
        return $this->followers()->where('user_id', $userId)->exists();
    }
    public function getRelatedPages(int $limit = 5): Collection
    {
        return WikiPage::where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->whereHas('categories', function ($q) {
                    $q->whereIn('wiki_categories.id', $this->categories->pluck('id'));
                })
                ->orWhereIn('id', function ($q) {
                    $q->select('target_page_id')
                        ->from('wiki_page_references')
                        ->whereIn('source_page_id', $this->referencedByPages->pluck('id'));
                });
            })
            ->withCount([
                'incomingReferences',
                'categories' => function ($query) {
                    $query->whereIn('wiki_categories.id', $this->categories->pluck('id'));
                }
            ])
            ->orderByDesc('categories_count')
            ->orderByDesc('incoming_references_count')
            ->limit($limit)
            ->get();
    }
    public function getFormattedCreatedAtAttribute(): ?string
    {
        return $this->created_at ? Carbon::parse($this->created_at)->format('Y-m-d H:i:s') : null;
    }
    public function getFormattedUpdatedAtAttribute(): ?string
    {
        return $this->updated_at ? Carbon::parse($this->updated_at)->format('Y-m-d H:i:s') : null;
    }
    public function getFormattedPublishedAtAttribute(): ?string
    {
        return $this->published_at ? Carbon::parse($this->published_at)->format('Y-m-d H:i:s') : null;
    }
    public function getStatusTextAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }
}