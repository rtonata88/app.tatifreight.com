Replaces the app's `toastr` flash messages. One sentence, past tense, with the record reference.

```jsx
<div style={{ position: 'fixed', right: 20, bottom: 20, display: 'flex', flexDirection: 'column', gap: 10 }}>
  <Toast onDismiss={dismiss}>Quotation QT-2026-0184 approved. Project NX-0231 created.</Toast>
</div>
```

Toasts confirm; they never ask. Anything requiring a decision is a `Modal`.
