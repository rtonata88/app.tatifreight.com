Bare text input. There is no box - the label above and the hairline below do the framing.

```jsx
<Input value={ref} onChange={e => setRef(e.target.value)} placeholder="INV-2026-0117" mono />
<Input value={amount} onChange={onAmount} prefix="NAD" align="right" mono />
```

Money, hours and reference codes always get `mono`; money is right-aligned. Focus turns the hairline brass, `invalid` turns it oxblood.
