import React from 'react';

/* Recreates resources/views/personnel/show.blade.php — the view its own design-system PRD
   names as the reference implementation. Structure kept one-for-one: hero, quick stats,
   three fact cards, leave entitlement grid, notes + documents lists, payroll details. */

const ENTITLEMENTS = [
  { type: 'Annual', cycle: 'Jan 2026 – Dec 2026', total: 24, used: 6, available: 18, current: true },
  { type: 'Sick', cycle: 'Jan 2024 – Dec 2026', total: 30, used: 4, available: 26, current: true },
  { type: 'Family responsibility', cycle: 'Jan 2026 – Dec 2026', total: 3, used: 3, available: 0, current: true },
];

const NOTES = [
  { title: 'Mid-year performance review', date: '18 Jan', type: 'performance',
    body: 'Exceeded on delivery of the MR44 condition report. Development area: delegation on multi-discipline packages.' },
  { title: 'Registration renewal reminder', date: '04 Jan', type: 'general', private: true,
    body: 'ECN professional registration lapses 31 Dec 2026. Practice pays the renewal on submission of proof.' },
];

const DOCUMENTS = [
  { title: 'Employment contract', date: '03 Feb', file: 'contract-shipanga-signed.pdf', category: 'Contract', verified: true, required: true },
  { title: 'ECN professional registration', date: '11 Dec', file: 'ecn-registration-2026.pdf', category: 'Registration', verified: true },
  { title: 'Medical certificate of fitness', date: '02 Jun', file: 'medical-fitness.pdf', category: 'Medical', required: true },
];

export function EmployeeScreen({ onNavigate }) {
  const NX = window.__nxNS();
  const { PageHeader, Card, DataGrid, StatCard, ProgressMeter, StatusPill, Badge, Button, Avatar, SectionHeader, EmptyState } = NX;
  const KIT = window.NexusKit || {};
  const Icon = KIT.Icon || function () { return null; };

  const metaItem = function (icon, children, strong) {
    return (
      <span style={{ display: 'inline-flex', alignItems: 'center', gap: 7, fontSize: 'var(--nx-fs-body)', color: 'var(--muted-foreground)' }}>
        <span style={{ color: 'var(--nx-fg-4)', display: 'flex' }}><Icon name={icon} size={14} /></span>
        <span style={{ color: strong ? 'var(--foreground)' : 'inherit', fontWeight: strong ? 600 : 400 }}>{children}</span>
      </span>
    );
  };

  return (
    <>
      {/* Hero — avatar, name, position, meta row, badges, actions */}
      <PageHeader
        eyebrow="People management · Employees"
        title="Tangeni Shipanga"
        subtitle="Senior civil engineer · Roads and stormwater · Windhoek office."
        meta={
          <>
            {metaItem('idCard', 'TWY-0142', true)}
            {metaItem('mail', 't.shipanga@example.na')}
            {metaItem('phone', '+264 81 ••• 4422')}
            {metaItem('calendar', 'Joined 03 Feb 2021')}
          </>
        }
        actions={
          <>
            <Button icon={<Icon name="arrowLeft" size={15} />} onClick={function () { onNavigate('quotations'); }}>Back</Button>
            <Button icon={<Icon name="calculator" size={15} />} onClick={function () { onNavigate('payslip'); }}>Payroll</Button>
            <Button variant="primary" icon={<Icon name="edit" size={15} />}>Edit</Button>
          </>
        }
      />

      <div style={{ display: 'flex', gap: 'var(--nx-s-4)', flexWrap: 'wrap', alignItems: 'center', marginTop: 'calc(var(--nx-section-gap) * -1 + var(--nx-s-2))', marginBottom: 'var(--nx-section-gap)' }}>
        <StatusPill status="active" />
        <StatusPill status="approved">Confirmed</StatusPill>
        <Badge tone="brass">Permanent</Badge>
      </div>

      {/* Quick stats — tenure, leave days, documents, notes */}
      <Card padding="md" style={{ marginBottom: 'var(--nx-section-gap)' }}>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))', gap: 'var(--nx-s-6)' }}>
          <StatCard value="5yr 0mo" label="Tenure" />
          <StatCard value="44.0" label="Leave days available" tone="positive" />
          <StatCard value="3" label="Documents" />
          <StatCard value="2" label="Notes" />
        </div>
      </Card>

      {/* Three fact cards */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))', gap: 'var(--nx-s-5)', marginBottom: 'var(--nx-section-gap)' }}>
        <Card title="Personal info" icon={<Icon name="users" />}>
          <DataGrid columns={2} items={[
            { label: 'Gender', value: 'Male' },
            { label: 'Date of birth', value: '14 Jun 1989', mono: true },
            { label: 'National ID', value: '890614 0042 1', mono: true },
            { label: 'Age', value: '36 years' },
          ]} />
        </Card>
        <Card title="Employment" icon={<Icon name="briefcase" />}>
          <DataGrid columns={2} items={[
            { label: 'Supervisor', value: 'Selma Kaunatjike' },
            { label: 'User account', value: 't.shipanga' },
            { label: 'Start date', value: '03 Feb 2021', mono: true },
            { label: 'Type', value: 'Permanent' },
          ]} />
        </Card>
        <Card title="Compensation" icon={<Icon name="money" />} actions={<StatusPill status="active" />}>
          <DataGrid columns={1} items={[
            { label: 'Basic salary', value: 'NAD 52,000.00', mono: true, emphasis: true },
          ]} />
          <div style={{ marginTop: 'var(--nx-s-5)' }}>
            <DataGrid columns={2} items={[
              { label: 'Tax number', value: '••• 428 91', mono: true },
              { label: 'SSC number', value: '••• 7734', mono: true },
            ]} />
          </div>
        </Card>
      </div>

      {/* Leave entitlements */}
      <SectionHeader title="Leave entitlements" meta="Cycle ending 31 Dec 2026"
        action={<Button variant="text" iconAfter={<Icon name="chevronRight" size={14} />}>Manage</Button>} />
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: 'var(--nx-s-4)', marginBottom: 'var(--nx-section-gap)' }}>
        {ENTITLEMENTS.map(function (e) {
          const pct = e.total ? Math.min(100, (e.used / e.total) * 100) : 0;
          const tone = pct > 80 ? 'negative' : pct > 50 ? 'warning' : 'positive';
          return (
            <Card key={e.type} padding="md">
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', gap: 12, marginBottom: 'var(--nx-s-4)' }}>
                <div style={{ minWidth: 0 }}>
                  <div style={{ fontSize: 'var(--nx-fs-md)', fontWeight: 600 }}>{e.type}</div>
                  {e.current ? <div style={{ marginTop: 6 }}><Badge tone="positive">Current</Badge></div> : null}
                </div>
                <div className="nx-mono" style={{ flex: 'none', textAlign: 'right', color: 'var(--muted-foreground)', fontSize: 11 }}>{e.cycle}</div>
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 'var(--nx-s-3)', marginBottom: 'var(--nx-s-4)' }}>
                {[['Total', e.total, 'neutral'], ['Used', e.used, 'warning'], ['Available', e.available, 'positive']].map(function (s) {
                  return (
                    <div key={s[0]} style={{
                      background: 'var(--secondary)', borderRadius: 'var(--nx-r-3)',
                      padding: 'var(--nx-s-3) var(--nx-s-2)', textAlign: 'center',
                    }}>
                      <div className="nx-num" style={{
                        fontSize: 'var(--nx-fs-lg)',
                        color: s[2] === 'positive' ? 'var(--nx-pos)' : s[2] === 'warning' ? 'var(--nx-warn)' : 'var(--foreground)',
                      }}>{Number(s[1]).toFixed(1)}</div>
                      <div className="nx-eyebrow" style={{ fontSize: 10, marginTop: 2 }}>{s[0]}</div>
                    </div>
                  );
                })}
              </div>
              <ProgressMeter label="Used" value={e.used} max={e.total} display={e.used + ' of ' + e.total + ' days'} tone={tone} />
            </Card>
          );
        })}
      </div>

      {/* Notes and documents */}
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(340px, 1fr))', gap: 'var(--nx-s-5)', marginBottom: 'var(--nx-section-gap)' }}>
        <div style={{ minWidth: 0 }}>
          <SectionHeader title="Notes" meta="2 recorded"
            action={<Button variant="text" iconAfter={<Icon name="chevronRight" size={14} />}>View all</Button>} />
          <Card bodyPad={false}>
            <div>
              {NOTES.map(function (n, i) {
                return (
                  <div key={n.title} style={{
                    padding: 'var(--nx-s-4) var(--nx-card-pad)',
                    borderBottom: i === NOTES.length - 1 ? 'none' : '1px solid var(--border)',
                  }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, marginBottom: 4 }}>
                      <span style={{ fontSize: 'var(--nx-fs-body)', fontWeight: 600 }}>{n.title}</span>
                      <span className="nx-mono" style={{ flex: 'none', color: 'var(--muted-foreground)' }}>{n.date}</span>
                    </div>
                    <p className="nx-meta" style={{ margin: '0 0 8px', textWrap: 'pretty' }}>{n.body}</p>
                    <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                      <Badge tone={n.type === 'performance' ? 'warning' : 'neutral'}>{n.type}</Badge>
                      {n.private ? <Badge>Private</Badge> : null}
                    </div>
                  </div>
                );
              })}
            </div>
          </Card>
        </div>

        <div style={{ minWidth: 0 }}>
          <SectionHeader title="Documents" meta="3 uploaded"
            action={<Button variant="text" iconAfter={<Icon name="chevronRight" size={14} />}>View all</Button>} />
          <Card bodyPad={false}>
            <div>
              {DOCUMENTS.map(function (d, i) {
                return (
                  <div key={d.title} style={{
                    padding: 'var(--nx-s-4) var(--nx-card-pad)',
                    borderBottom: i === DOCUMENTS.length - 1 ? 'none' : '1px solid var(--border)',
                  }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', gap: 12, marginBottom: 4 }}>
                      <span style={{ fontSize: 'var(--nx-fs-body)', fontWeight: 600 }}>{d.title}</span>
                      <span className="nx-mono" style={{ flex: 'none', color: 'var(--muted-foreground)' }}>{d.date}</span>
                    </div>
                    <p className="nx-meta" style={{ margin: '0 0 8px', fontFamily: 'var(--nx-font-mono)', fontSize: 11 }}>{d.file}</p>
                    <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
                      <Badge>{d.category}</Badge>
                      {d.verified ? <Badge tone="positive">Verified</Badge> : null}
                      {d.required ? <Badge tone="negative">Required</Badge> : null}
                    </div>
                  </div>
                );
              })}
            </div>
          </Card>
        </div>
      </div>

      {/* Payroll details */}
      <SectionHeader title="Payroll details"
        action={<Button variant="text" iconAfter={<Icon name="chevronRight" size={14} />} onClick={function () { onNavigate('payslip'); }}>Manage payroll</Button>} />
      <Card padding="lg">
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--nx-s-4)', marginBottom: 'var(--nx-s-7)' }}>
          {[['Basic salary', 'NAD 52,000.00', 'neutral'], ['Total allowances', 'NAD 16,400.00', 'positive'], ['Fixed deductions', 'NAD 6,380.00', 'negative']].map(function (s) {
            return (
              <div key={s[0]} style={{
                background: 'var(--secondary)', borderRadius: 'var(--nx-r-4)',
                padding: 'var(--nx-s-5)', textAlign: 'center',
              }}>
                <div className="nx-num" style={{
                  fontSize: 'var(--nx-fs-xl)',
                  color: s[2] === 'positive' ? 'var(--nx-pos)' : s[2] === 'negative' ? 'var(--nx-neg)' : 'var(--foreground)',
                }}>{s[1]}</div>
                <div className="nx-eyebrow" style={{ marginTop: 6 }}>{s[0]}</div>
              </div>
            );
          })}
        </div>

        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))', gap: 'var(--nx-s-6)' }}>
          <section>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', paddingBottom: 'var(--nx-s-3)', marginBottom: 'var(--nx-s-3)', borderBottom: '1px solid var(--border)' }}>
              <span className="nx-eyebrow">Tax and banking</span>
              <Button variant="text" size="sm">Edit</Button>
            </div>
            <DataGrid columns={1} items={[
              { label: 'Tax number', value: '••• 428 91', mono: true },
              { label: 'SSC number', value: '••• 7734', mono: true },
              { label: 'Payment method', value: 'Bank transfer' },
              { label: 'Bank', value: 'Bank Windhoek · ••• 8021', mono: true },
            ]} />
          </section>

          <section>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', paddingBottom: 'var(--nx-s-3)', marginBottom: 'var(--nx-s-3)', borderBottom: '1px solid var(--border)' }}>
              <span className="nx-eyebrow">Allowances (4)</span>
              <Button variant="text" size="sm">Manage</Button>
            </div>
            <DataGrid columns={1} items={[
              { label: 'Housing', value: 'NAD 9,600.00', mono: true },
              { label: 'Travel', value: 'NAD 4,200.00', mono: true },
              { label: 'Site allowance', value: 'NAD 2,600.00', mono: true },
              { label: 'Cellphone', value: 'NAD 0.00', mono: true },
            ]} />
          </section>

          <section>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'baseline', paddingBottom: 'var(--nx-s-3)', marginBottom: 'var(--nx-s-3)', borderBottom: '1px solid var(--border)' }}>
              <span className="nx-eyebrow">Benefits</span>
              <Button variant="text" size="sm">Manage</Button>
            </div>
            <DataGrid columns={1} items={[
              { label: 'Pension fund', value: 'Old Mutual · 7.5%' },
              { label: 'Medical aid', value: 'NHP Platinum · principal + 2' },
            ]} />
            <div style={{ marginTop: 'var(--nx-s-4)' }}>
              <EmptyState compact title="No loans" description="Staff loans and their repayment schedules appear here." />
            </div>
          </section>
        </div>
      </Card>
    </>
  );
}
