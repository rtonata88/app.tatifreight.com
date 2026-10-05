Consumption and completion: leave entitlement, budget vs actual, project progress, collection rate.

```jsx
<ProgressMeter label="Annual leave" value={18} max={24} display="18 of 24 days" tone="positive" />
<ProgressMeter label="Budget consumed" value={91} tone="negative" meta="NAD 1.82m of NAD 2.00m" />
```

Tone states the reading, not the metric: a leave balance running out is `negative`, a healthy one `positive`. Default `brass` for neutral progress.
