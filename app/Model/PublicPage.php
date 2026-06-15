<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PublicPage extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'short_title',
        'content',
        'page_order',
        'shown_in_footer',
        'show_last_update_date',
        'is_tos',
        'is_privacy',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var list<string>
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'title' => 'array',
        'short_title' => 'array',
        'content' => 'array',
        'shown_in_footer' => 'boolean',
        'show_last_update_date' => 'boolean',
        'is_tos' => 'boolean',
        'is_privacy' => 'boolean',
        'page_order' => 'integer',
    ];

    public function translated(string $field, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $fallback = config('app.fallback_locale', 'en');

        $data = (array) ($this->{$field} ?? []);

        return (string) ($data[$locale] ?? $data[$fallback] ?? '');
    }
}
