Square hairline icon button - row actions (view / edit / download / print / delete) in tables and list items.

```jsx
<IconButton label="View payslip"><Eye size={14} /></IconButton>
<IconButton label="Download PDF" tone="positive"><Download size={14} /></IconButton>
<IconButton label="Delete" tone="negative"><Trash2 size={14} /></IconButton>
```

Always pass `label` - these are the only unlabelled controls in the system. Group them in a flex row with `gap: 6px`.
