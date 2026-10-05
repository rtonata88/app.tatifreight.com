Nexus button - use for every action; `primary` is reserved for the single most important action in a region.

```jsx
<Button variant="primary" icon={<Plus size={15} />}>New quotation</Button>
<Button>Export</Button>
<Button variant="text" iconAfter={<ArrowRight size={14} />}>View all</Button>
```

Variants: `primary` (solid brass, ink text), `ghost` (hairline, brass on hover - the workhorse), `text` (inline link), `danger` (oxblood outline for destructive confirms). Sizes sm (28px, table toolbars) / md (36px, default) / lg (44px, auth and empty states). Hover only changes colour - never transform.