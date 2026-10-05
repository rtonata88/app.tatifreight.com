The Nexus dossier line - use it wherever a record is being *read* rather than scanned: approval trails, invoice terms, verification details.

```jsx
<Card title="Approval trail" padding="lg">
  <DossierRow label="Submitted by" meta="14 Feb 2026 09:12">Tangeni Shipanga</DossierRow>
  <DossierRow label="Supervisor" meta="14 Feb 2026 16:40">Selma Kaunatjike</DossierRow>
  <DossierRow label="Finance sign-off" meta="—" last>Awaiting review</DossierRow>
</Card>
```

Choose `DossierRow` for a narrative sequence and `DataGrid` for a block of independent facts. Set `last` on the final row.
