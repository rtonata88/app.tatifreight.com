import React from 'react';

export function DashboardScreen({ onNavigate }) {
  const NX = window.__nxNS();
  const { PageHeader, MetricCard, Card, StatCard, ProgressMeter, ActivityList, Button, SectionHeader, DataTable, StatusPill, Badge } = NX;
  const KIT = window.NexusKit || {};
  const Icon = KIT.Icon || (() => null);

  const cashflow = [
    { m: 'Sep', rev: 3.1, exp: 2.4 }, { m: 'Oct', rev: 3.6, exp: 2.6 },
    { m: 'Nov', rev: 3.4, exp: 2.9 }, { m: 'Dec', rev: 2.8, exp: 2.5 },
    { m: 'Jan', rev: 3.9, exp: 2.7 }, { m: 'Feb', rev: 4.18, exp: 2.96 },
  ];
  const max = 5;

  const overdue = [
    { id: 1, ref: 'INV-2026-0092', client: 'Ohangwena Regional Council', amount: '214,800.00', age: '48 days' },
    { id: 2, ref: 'INV-2026-0101', client: 'City of Windhoek', amount: '78,500.00', age: '31 days' },
    { id: 3, ref: 'INV-2026-0107', client: 'NamWater', amount: '112,940.00', age: '19 days' },
  ];

  return (
    <>
      <PageHeader
        eyebrow="Dashboard"
        title="Analytics"
        subtitle="Practice performance for February 2026, to the close of business yesterday."
        actions={
          <>
            <Button icon={<Icon name="download" size={15} />}>Export</Button>
            <Button variant="primary" onClick={() => onNavigate('expenses')}>Record expense</Button>
          </>
        }
      />

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(190px, 1fr))', gap: 'var(--nx-s-4)', marginBottom: 'var(--nx-section-gap)' }}>
        <MetricCard label="Monthly revenue" value="NAD 4.18m" delta="+12.4% MoM" deltaTone="positive" emphasis />
        <MetricCard label="Monthly expenses" value="NAD 2.96m" delta="+9.6% MoM" deltaTone="negative" />
        <MetricCard label="Outstanding" value="NAD 2.94m" meta="17 invoices · 6 overdue" />
        <MetricCard label="Active projects" value="27" delta="+3" deltaTone="positive" meta="Across 11 clients" />
        <MetricCard label="Pending quotations" value="9" meta="NAD 6.42m in scope" />
        <MetricCard label="On leave today" value="4" meta="Of 63 employees" />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'minmax(0, 1.9fr) minmax(0, 1fr)', gap: 'var(--nx-s-5)', marginBottom: 'var(--nx-section-gap)' }}>
        <Card title="Cash flow" subtitle="Revenue against expenses, last six months" actions={<Badge mono>NAD m</Badge>}>
          <div style={{ display: 'flex', alignItems: 'flex-end', gap: 'var(--nx-s-6)', height: 190, padding: '8px 0 0' }}>
            {cashflow.map(function (d) {
              return (
                <div key={d.m} style={{ flex: 1, display: 'flex', flexDirection: 'column', alignItems: 'center', gap: 8, minWidth: 0 }}>
                  <div style={{ flex: 1, width: '100%', display: 'flex', alignItems: 'flex-end', justifyContent: 'center', gap: 6 }}>
                    <div title={'Revenue ' + d.rev} style={{ width: 16, height: (d.rev / max * 100) + '%', background: 'var(--nx-brass)' }} />
                    <div title={'Expenses ' + d.exp} style={{ width: 16, height: (d.exp / max * 100) + '%', background: 'var(--nx-rule-strong)' }} />
                  </div>
                  <span className="nx-mono" style={{ color: 'var(--muted-foreground)' }}>{d.m}</span>
                </div>
              );
            })}
          </div>
          <div style={{ display: 'flex', gap: 'var(--nx-s-5)', marginTop: 'var(--nx-s-4)', paddingTop: 'var(--nx-s-4)', borderTop: '1px solid var(--border)' }}>
            <span style={{ display: 'flex', alignItems: 'center', gap: 7 }}><span style={{ width: 9, height: 9, background: 'var(--nx-brass)' }} /><span className="nx-meta">Revenue</span></span>
            <span style={{ display: 'flex', alignItems: 'center', gap: 7 }}><span style={{ width: 9, height: 9, background: 'var(--nx-rule-strong)' }} /><span className="nx-meta">Expenses</span></span>
            <span className="nx-meta" style={{ marginLeft: 'auto' }}>Net February · NAD 1.22m</span>
          </div>
        </Card>

        <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--nx-s-5)', minWidth: 0 }}>
          <Card title="Monthly performance">
            <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--nx-s-5)' }}>
              <ProgressMeter label="Quotation conversion" value={62} tone="brass" />
              <ProgressMeter label="Collection rate" value={78} tone="positive" />
              <ProgressMeter label="Budget consumed" value={91} tone="negative" meta="NAD 1.82m of NAD 2.00m" />
            </div>
          </Card>
          <Card title="Quick stats">
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 'var(--nx-s-4)' }}>
              <StatCard value="34" label="Avg days to payment" />
              <StatCard value="6" label="Overdue invoices" tone="negative" />
              <StatCard value="1,842" label="Billable hours, Feb" />
              <StatCard value="29%" label="Profit margin" tone="positive" />
            </div>
          </Card>
        </div>
      </div>

      <SectionHeader title="Needs attention" meta="6 overdue invoices"
        action={<Button variant="text" iconAfter={<Icon name="chevronRight" size={14} />} onClick={() => onNavigate('invoices')}>All invoices</Button>} />
      <div style={{ display: 'grid', gridTemplateColumns: 'minmax(0, 1.4fr) minmax(0, 1fr)', gap: 'var(--nx-s-5)' }}>
        <Card bodyPad={false} title="Overdue receivables" actions={<StatusPill status="overdue">NAD 406,240 total</StatusPill>}>
          <DataTable
            dense
            onRowClick={() => onNavigate('invoices')}
            columns={[
              { key: 'ref', label: 'Reference', mono: true, width: '150px' },
              { key: 'client', label: 'Client' },
              { key: 'amount', label: 'Amount (NAD)', align: 'right', mono: true },
              { key: 'age', label: 'Age', align: 'right', mono: true, muted: true, width: '90px' },
            ]}
            rows={overdue}
          />
        </Card>
        <Card title="Recent activity">
          <ActivityList items={[
            { title: 'Invoice INV-2026-0117 issued', description: 'Roads Authority · NAD 486,200.00', time: '14 Feb 09:12', tone: 'positive' },
            { title: 'Quotation QT-2026-0184 approved', description: 'Project NX-0231 created', time: '13 Feb 16:40', tone: 'brass' },
            { title: 'Leave application declined', description: 'T. Shipanga · 3 days annual', time: '12 Feb 11:04', tone: 'negative' },
            { title: 'Trip request submitted', description: 'Walvis Bay · 2 nights · NAD 4,180', time: '11 Feb 08:22', tone: 'warning' },
          ]} />
        </Card>
      </div>
    </>
  );
}
