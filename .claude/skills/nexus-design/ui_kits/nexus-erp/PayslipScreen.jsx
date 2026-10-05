import React from 'react';

const EARNINGS = [
  ['Basic salary', '52,000.00'],
  ['Housing allowance', '9,600.00'],
  ['Travel allowance', '4,200.00'],
  ['Site allowance · 4 days', '2,600.00'],
];
const DEDUCTIONS = [
  ['PAYE', '11,842.60'],
  ['Social Security Commission', '81.00'],
  ['Pension fund · 7.5%', '3,900.00'],
  ['Medical aid · NHP Platinum', '2,480.00'],
];

export function PayslipScreen({ onNavigate }) {
  const NX = window.__nxNS();
  const { PageHeader, Button, Badge, StatusPill, Card, DossierRow } = NX;
  const KIT = window.NexusKit || {};
  const Icon = KIT.Icon || (() => null);

  const line = function (label, value, opts) {
    const o = opts || {};
    return (
      <div key={label} style={{
        display: 'flex', justifyContent: 'space-between', gap: 16,
        padding: '7px 0', borderBottom: o.last ? 'none' : '1px dotted var(--nx-rule-paper)',
      }}>
        <span style={{ fontSize: 13, fontWeight: o.strong ? 700 : 500 }}>{label}</span>
        <span style={{
          fontFamily: 'var(--nx-font-mono)', fontSize: 13, fontVariantNumeric: 'tabular-nums',
          fontWeight: o.strong ? 600 : 400, color: o.tone || 'inherit',
        }}>{value}</span>
      </div>
    );
  };

  return (
    <>
      <PageHeader
        eyebrow="Payroll · Payslips"
        title="February 2026 payslip"
        subtitle="Documents print on paper: warm stock, ink type, hairline rules. Nothing else changes."
        meta={<><StatusPill status="paid" /><Badge mono>TWY-0142</Badge><span className="nx-meta">Paid 25 Feb 2026</span></>}
        actions={
          <>
            <Button icon={<Icon name="arrowLeft" size={15} />} onClick={function () { onNavigate('employee'); }}>Back</Button>
            <Button icon={<Icon name="printer" size={15} />}>Print</Button>
            <Button variant="primary" icon={<Icon name="download" size={15} />}>Download PDF</Button>
          </>
        }
      />

      <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--nx-s-5)', alignItems: 'flex-start' }}>
      <div className="nx-paper" style={{
        flex: '1 1 660px', minWidth: 0, maxWidth: 'var(--nx-doc-max)',
        padding: 'var(--nx-s-9) var(--nx-s-9) var(--nx-s-8)',
        borderRadius: 'var(--nx-r-2)',
      }}>
        <header style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 24, marginBottom: 'var(--nx-s-7)' }}>
          <div>
            <div style={{ fontFamily: 'var(--nx-font-display)', fontWeight: 800, fontSize: 22, letterSpacing: '0.02em' }}>Nexus</div>
            <div style={{ fontSize: 12, color: 'var(--nx-pfg-2)', marginTop: 4 }}>Consulting engineers · Windhoek, Namibia</div>
          </div>
          <div style={{ textAlign: 'right' }}>
            <div className="nx-eyebrow">Payslip</div>
            <div style={{ fontFamily: 'var(--nx-font-mono)', fontSize: 13, marginTop: 4 }}>PS-2026-02-0142</div>
          </div>
        </header>

        <hr style={{ border: 0, borderTop: '1px solid var(--nx-rule-brass)', margin: '0 0 var(--nx-s-6)' }} />

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, minmax(0, 1fr))', gap: 'var(--nx-s-5)', marginBottom: 'var(--nx-s-7)' }}>
          {[
            ['Employee', 'Tangeni Shipanga'],
            ['Employee number', 'TWY-0142'],
            ['Position', 'Senior civil engineer'],
            ['Pay period', '01–28 Feb 2026'],
            ['Payment date', '25 Feb 2026'],
            ['Bank', 'Bank Windhoek ••• 8021'],
          ].map(function (p) {
            return (
              <div key={p[0]}>
                <div className="nx-eyebrow" style={{ marginBottom: 3 }}>{p[0]}</div>
                <div style={{ fontSize: 13, fontWeight: 500 }}>{p[1]}</div>
              </div>
            );
          })}
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 'var(--nx-s-7)', marginBottom: 'var(--nx-s-7)' }}>
          <section>
            <div className="nx-eyebrow" style={{ marginBottom: 'var(--nx-s-3)' }}>Earnings</div>
            {EARNINGS.map(function (r) { return line(r[0], r[1]); })}
            {line('Gross pay', '68,400.00', { strong: true, last: true })}
          </section>
          <section>
            <div className="nx-eyebrow" style={{ marginBottom: 'var(--nx-s-3)' }}>Deductions</div>
            {DEDUCTIONS.map(function (r) { return line(r[0], r[1]); })}
            {line('Total deductions', '18,303.60', { strong: true, last: true })}
          </section>
        </div>

        <hr style={{ border: 0, borderTop: '1px solid var(--nx-rule-brass)', margin: '0 0 var(--nx-s-4)' }} />

        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', marginBottom: 'var(--nx-s-7)' }}>
          <span className="nx-eyebrow">Net pay</span>
          <span className="nx-num" style={{ fontSize: 34, color: 'var(--nx-brass-lo)' }}>NAD 50,096.40</span>
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, minmax(0, 1fr))', gap: 'var(--nx-s-5)' }}>
          {[
            ['Year to date gross', '136,800.00'],
            ['Year to date PAYE', '23,685.20'],
            ['Leave balance', '18.0 days'],
          ].map(function (p) {
            return (
              <div key={p[0]}>
                <div className="nx-eyebrow" style={{ marginBottom: 3 }}>{p[0]}</div>
                <div style={{ fontFamily: 'var(--nx-font-mono)', fontSize: 13 }}>{p[1]}</div>
              </div>
            );
          })}
        </div>

        <p style={{ fontSize: 11, color: 'var(--nx-pfg-2)', marginTop: 'var(--nx-s-7)', marginBottom: 0, textWrap: 'pretty' }}>
          This payslip is a record of payment made in terms of the Labour Act. Retain it for your tax return.
          Queries must be raised with payroll within 30 days of the payment date.
        </p>
      </div>

      <aside style={{ flex: '1 1 300px', minWidth: 0, display: 'flex', flexDirection: 'column', gap: 'var(--nx-s-5)' }}>
        <Card title="Payroll run" icon={<Icon name="calculator" />}>
          <DossierRow label="Run">PR-2026-02</DossierRow>
          <DossierRow label="Period">01–28 Feb 2026</DossierRow>
          <DossierRow label="Status"><StatusPill status="paid" /></DossierRow>
          <DossierRow label="Approved by" meta="24 Feb 15:02">Selma Kaunatjike</DossierRow>
          <DossierRow label="Employees in run" last>63</DossierRow>
        </Card>

        <Card title="Totals" icon={<Icon name="money" />}>
          <DossierRow label="Gross pay">NAD 68,400.00</DossierRow>
          <DossierRow label="Deductions">-NAD 18,303.60</DossierRow>
          <DossierRow label="Employer contributions" meta="Not paid to you">NAD 4,981.00</DossierRow>
          <DossierRow label="Net pay" last>
            <span className="nx-num" style={{ fontSize: 'var(--nx-fs-xl)', color: 'var(--nx-brass-hi)' }}>NAD 50,096.40</span>
          </DossierRow>
        </Card>

        <Card title="Payment" icon={<Icon name="card" />}>
          <DossierRow label="Method">Bank transfer</DossierRow>
          <DossierRow label="Bank">Bank Windhoek</DossierRow>
          <DossierRow label="Account" meta="Verified">••• 8021</DossierRow>
          <DossierRow label="Paid on" last>25 Feb 2026</DossierRow>
        </Card>
      </aside>
      </div>
    </>
  );
}
