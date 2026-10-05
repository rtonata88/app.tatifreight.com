Bare select for the app's many list-managed taxonomies (expense categories, leave types, activities, payment methods).

```jsx
<Select value={cat} onChange={e => setCat(e.target.value)} placeholder="Select a category"
        options={['Travel', 'Subsistence', 'Subconsultants']} />
```

For more than about 12 options, pair it with a search input above rather than reaching for a custom combobox.
