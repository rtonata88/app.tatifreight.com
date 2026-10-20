<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    protected $fillable = [
        'value',
        'label',
    ];

    /**
     * Select options for the expense screens, in alphabetical order with "Other" last.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return self::orderByRaw("value = 'other'")
            ->orderBy('label')
            ->get(['value', 'label'])
            ->map(fn (self $category) => ['value' => $category->value, 'label' => $category->label])
            ->all();
    }
}
