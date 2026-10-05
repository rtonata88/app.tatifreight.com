Dashboard headline figures. Lay them out in a grid of `repeat(auto-fit, minmax(200px, 1fr))` with `gap: 16px`.

```jsx
<MetricCard label="Monthly revenue" value="NAD 4.18m" delta="+12.4% MoM" deltaTone="positive" emphasis />
<MetricCard label="Outstanding invoices" value="NAD 2.94m" meta="17 invoices, 6 overdue" />
```

Figures are set in condensed tabular numerals - format them before passing them in (`NAD 4.18m`, not `4180000`). Never put an icon in a metric card; the label carries the meaning.
