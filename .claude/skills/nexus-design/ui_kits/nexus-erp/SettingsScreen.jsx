import React from 'react';

/* Settings, cardless: a sub-rail of grouped panes, hairline-separated SettingsRows.
   Field sets are the real ones — company_information columns from
   database/migrations/2025_04_29_145321_create_company_information_table.php and
   app/Models/CompanyInformation.php; expense_categories columns and seed rows from
   2025_04_30_153114_create_expense_categories_table.php +
   2025_01_22_100001_add_is_capital_expense_to_expense_categories.php.
   Nav groups follow the Settings module in layouts/sidebar.blade.php. */

const GROUPS = [
  { title: 'Practice', items: [
    { key: 'company', label: 'Company information' },
    { key: 'addresses', label: 'Addresses' },
    { key: 'branding', label: 'Branding' },
    { key: 'financial-year', label: 'Financial year' },
  ]},
  { title: 'Documents', items: [
    { key: 'documents', label: 'Invoice and quotation defaults' },
    { key: 'terms', label: 'Terms and conditions' },
  ]},
  { title: 'Managed lists', items: [
    { key: 'expense-categories', label: 'Expense categories', count: 13 },
    { key: 'document-categories', label: 'Document categories' },
    { key: 'activities', label: 'Activities' },
    { key: 'payment-methods', label: 'Payment methods' },
    { key: 'vendor-categories', label: 'Vendor categories' },
    { key: 'task-categories', label: 'Task categories' },
    { key: 'task-statuses', label: 'Task statuses' },
    { key: 'leave-types', label: 'Leave types' },
    { key: 'fee-scales', label: 'Fee scales' },
    { key: 'multipliers', label: 'Multipliers' },
    { key: 'other-income-categories', label: 'Other income categories' },
  ]},
  { title: 'Calendar', items: [
    { key: 'working-days', label: 'Working days' },
    { key: 'public-holidays', label: 'Public holidays' },
  ]},
  { title: 'Access', items: [
    { key: 'users', label: 'User accounts', count: 63 },
    { key: 'roles', label: 'Roles and permissions', count: 8 },
  ]},
];

/* The table's seeded rows, verbatim from the create migration. */
const CATEGORIES = [
  { id: 1, name: 'Venue Rental', code: 'VENUE', capital: false, active: true, used: 148 },
  { id: 2, name: 'Equipment Rental', code: 'EQUIP', capital: false, active: true, used: 312 },
  { id: 3, name: 'Transportation', code: 'TRANS', capital: false, active: true, used: 274 },
  { id: 4, name: 'Freelance Staff', code: 'STAFF', capital: false, active: true, used: 96 },
  { id: 5, name: 'Equipment Purchase', code: 'PURCHASE', capital: true, active: true, used: 41 },
  { id: 6, name: 'Equipment Maintenance', code: 'MAINT', capital: false, active: true, used: 87 },
  { id: 7, name: 'Software & Subscriptions', code: 'SUBS', capital: false, active: true, used: 63 },
  { id: 8, name: 'Bank Fees', code: 'BANK', capital: false, active: false, used: 11 },
];

const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'];

export function SettingsScreen({ onNavigate }) {
  const NX = window.__nxNS();
  const {
    PageHeader, SettingsNav, SettingsRow, Input, Select, Textarea, Switch,
    Button, IconButton, Badge, StatusPill, DataTable, Alert, SectionHeader,
  } = NX;
  const KIT = window.NexusKit || {};
  const Icon = KIT.Icon || function () { return null; };

  const [pane, setPane] = React.useState('company');
  const [vatRegistered, setVatRegistered] = React.useState(true);
  const [c, setC] = React.useState({
    company_name: 'Nexus Consulting Engineers (Pty) Ltd',
    registration_number: '2014/0842',
    vat_number: '2648117015',
    vat_rate: '15.00',
    phone: '+264 61 302 118',
    fax: '+264 61 302 119',
    email: 'accounts@example.na',
    website: 'www.example.na',
    a1: '12 Nachtigal Street', a2: 'Windhoek West', a3: 'Windhoek', a4: 'Namibia',
    p1: 'PO Box 21148', p2: 'Windhoek', p3: '10005', p4: 'Namibia',
    invoice_prefix: 'INV-', quote_prefix: 'QUO-',
    invoice_footer_text: 'Payment is due 30 days from invoice date. Deposits to Bank Windhoek, account 8021, branch 481972.',
    quote_footer_text: 'This quotation is valid for 30 days from the date of issue.',
    terms: 'Fees are charged in accordance with the ECN fee scale current at the date of instruction. Disbursements are recovered at cost. Interest accrues on overdue amounts at the prevailing prime rate plus two per cent, calculated daily.',
    primary_color: '#4e73df', secondary_color: '#2d3748',
    fy_start_month: 'March', fy_start_day: '1', fy_end_month: 'February', fy_end_day: '28',
  });
  const set = function (k) { return function (e) { const n = Object.assign({}, c); n[k] = e.target.value; setC(n); }; };

  const heading = function (title, description) {
    return (
      <header style={{ paddingBottom: 'var(--nx-s-5)', marginBottom: 'var(--nx-s-2)', borderBottom: '1px solid var(--input)' }}>
        <h2 className="nx-h3" style={{ fontSize: 'var(--nx-fs-xl)' }}>{title}</h2>
        <p className="nx-meta" style={{ margin: '6px 0 0', maxWidth: 560, textWrap: 'pretty' }}>{description}</p>
      </header>
    );
  };
  const saveBar = function (label) {
    return (
      <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 10, marginTop: 'var(--nx-s-7)' }}>
        <Button>Discard changes</Button>
        <Button variant="primary" icon={<Icon name="save" size={15} />}>{label}</Button>
      </div>
    );
  };

  let body;

  if (pane === 'company') {
    body = (
      <>
        {heading('Company information',
          'Legal and contact details for the practice. These print on every quotation, invoice, statement and payslip, so a change here is a change to your documents.')}
        <SettingsRow label="Company name" description="As registered with BIPA.">
          <Input value={c.company_name} onChange={set('company_name')} />
        </SettingsRow>
        <SettingsRow label="Registration number">
          <Input value={c.registration_number} onChange={set('registration_number')} mono align="right" />
        </SettingsRow>
        <SettingsRow label="VAT registration"
          description={vatRegistered
            ? 'VAT is calculated at the rate below on invoices and expenses.'
            : 'Invoices are issued without VAT and expenses are captured gross.'}>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--nx-s-3)', alignItems: 'stretch' }}>
            <Switch id="vat-reg" checked={vatRegistered} onChange={function () { setVatRegistered(!vatRegistered); }} label="Registered for VAT" />
            {vatRegistered ? (
              <>
                <Input value={c.vat_number} onChange={set('vat_number')} mono align="right" />
                <Input value={c.vat_rate} onChange={set('vat_rate')} suffix="%" mono align="right" />
              </>
            ) : null}
          </div>
        </SettingsRow>
        <SettingsRow label="Telephone">
          <Input value={c.phone} onChange={set('phone')} mono align="right" />
        </SettingsRow>
        <SettingsRow label="Fax" description="Still required by some public-sector clients.">
          <Input value={c.fax} onChange={set('fax')} mono align="right" />
        </SettingsRow>
        <SettingsRow label="Email" description="Where client payment remittances are received.">
          <Input value={c.email} onChange={set('email')} type="email" />
        </SettingsRow>
        <SettingsRow label="Website" last>
          <Input value={c.website} onChange={set('website')} />
        </SettingsRow>
        {saveBar('Save company information')}
      </>
    );
  } else if (pane === 'addresses') {
    body = (
      <>
        {heading('Addresses',
          'Four lines each, printed as entered. The physical address appears on invoices; the postal address on statements and correspondence.')}
        <SectionHeader title="Physical address" style={{ marginTop: 'var(--nx-s-6)' }} />
        {[['a1', 'Line 1'], ['a2', 'Line 2'], ['a3', 'Line 3'], ['a4', 'Line 4']].map(function (r, i) {
          return (
            <SettingsRow key={r[0]} label={r[1]} last={i === 3}>
              <Input value={c[r[0]]} onChange={set(r[0])} />
            </SettingsRow>
          );
        })}
        <SectionHeader title="Postal address" style={{ marginTop: 'var(--nx-s-8)' }} />
        {[['p1', 'Line 1'], ['p2', 'Line 2'], ['p3', 'Line 3'], ['p4', 'Line 4']].map(function (r, i) {
          return (
            <SettingsRow key={r[0]} label={r[1]} last={i === 3}>
              <Input value={c[r[0]]} onChange={set(r[0])} />
            </SettingsRow>
          );
        })}
        {saveBar('Save addresses')}
      </>
    );
  } else if (pane === 'branding') {
    body = (
      <>
        {heading('Branding',
          'The mark and two colours the application and its documents use. Both colours are stored as hex and applied to headings and accents on generated PDFs.')}
        <SettingsRow label="Logo" description="Printed top-left on every document. SVG or PNG, at least 400px wide."
          action={<Button size="sm" icon={<Icon name="upload" size={14} />}>Upload</Button>}>
          <span className="nx-meta">No logo uploaded</span>
        </SettingsRow>
        <SettingsRow label="Favicon" description="Shown in the browser tab. 32×32 PNG or ICO."
          action={<Button size="sm" icon={<Icon name="upload" size={14} />}>Upload</Button>}>
          <span className="nx-meta">No favicon uploaded</span>
        </SettingsRow>
        <SettingsRow label="Primary colour" description="Headings and accents on generated documents.">
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <span style={{ flex: 'none', width: 22, height: 22, borderRadius: 'var(--nx-r-2)', background: c.primary_color, border: '1px solid var(--border)' }} />
            <Input value={c.primary_color} onChange={set('primary_color')} mono align="right" />
          </div>
        </SettingsRow>
        <SettingsRow label="Secondary colour" description="Body text and rules on generated documents." last>
          <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
            <span style={{ flex: 'none', width: 22, height: 22, borderRadius: 'var(--nx-r-2)', background: c.secondary_color, border: '1px solid var(--border)' }} />
            <Input value={c.secondary_color} onChange={set('secondary_color')} mono align="right" />
          </div>
        </SettingsRow>
        {saveBar('Save branding')}
      </>
    );
  } else if (pane === 'financial-year') {
    body = (
      <>
        {heading('Financial year',
          'The start and end of the reporting year. Every financial report, budget and comparative figure is bracketed by these dates.')}
        <SettingsRow label="Year starts" description="Day and month.">
          <div style={{ display: 'flex', gap: 10 }}>
            <Input value={c.fy_start_day} onChange={set('fy_start_day')} mono align="right" style={{ maxWidth: 70 }} />
            <Select value={c.fy_start_month} onChange={set('fy_start_month')} options={MONTHS} />
          </div>
        </SettingsRow>
        <SettingsRow label="Year ends" description="Day and month." last>
          <div style={{ display: 'flex', gap: 10 }}>
            <Input value={c.fy_end_day} onChange={set('fy_end_day')} mono align="right" style={{ maxWidth: 70 }} />
            <Select value={c.fy_end_month} onChange={set('fy_end_month')} options={MONTHS} />
          </div>
        </SettingsRow>
        <div style={{ marginTop: 'var(--nx-s-6)' }}>
          <Alert tone="warning" title="Changing these dates re-brackets historical reports">
            The current year runs 01 March 2025 to 28 February 2026. Comparatives already issued to
            clients or auditors will not match a report run after a change.
          </Alert>
        </div>
        {saveBar('Save financial year')}
      </>
    );
  } else if (pane === 'documents') {
    body = (
      <>
        {heading('Invoice and quotation defaults',
          'The prefix each reference is built from, and the block of text printed at the foot of the document.')}
        <SettingsRow label="Quotation prefix" description="Next reference: QUO-0184.">
          <Input value={c.quote_prefix} onChange={set('quote_prefix')} mono align="right" />
        </SettingsRow>
        <SettingsRow label="Invoice prefix" description="Next reference: INV-0118.">
          <Input value={c.invoice_prefix} onChange={set('invoice_prefix')} mono align="right" />
        </SettingsRow>
        <SettingsRow label="Quotation footer" description="Printed below the fee summary on every quotation." stacked>
          <Textarea value={c.quote_footer_text} onChange={set('quote_footer_text')} rows={2} />
        </SettingsRow>
        <SettingsRow label="Invoice footer" description="Payment terms and banking details. Printed below the total." stacked last>
          <Textarea value={c.invoice_footer_text} onChange={set('invoice_footer_text')} rows={3} />
        </SettingsRow>
        {saveBar('Save document defaults')}
      </>
    );
  } else if (pane === 'terms') {
    body = (
      <>
        {heading('Terms and conditions',
          'Attached to quotations as a second page. Changing this affects quotations issued from now on; those already sent keep the text they were issued with.')}
        <SettingsRow label="Standard terms" stacked last>
          <Textarea value={c.terms} onChange={set('terms')} rows={8} />
        </SettingsRow>
        {saveBar('Save terms')}
      </>
    );
  } else if (pane === 'expense-categories') {
    body = (
      <>
        {heading('Expense categories',
          'The categories staff choose from when capturing an expense. Deactivating one keeps its history but removes it from new captures.')}
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 'var(--nx-s-4)', flexWrap: 'wrap', padding: 'var(--nx-s-5) 0' }}>
          <span className="nx-meta">13 categories · 8 shown</span>
          <Button variant="primary" size="sm" icon={<Icon name="plus" size={14} />}>New category</Button>
        </div>
        <div style={{ border: '1px solid var(--border)', borderRadius: 'var(--nx-r-4)', overflow: 'hidden' }}>
          <DataTable
            columns={[
              { key: 'name', label: 'Category' },
              { key: 'code', label: 'Code', mono: true, width: '96px' },
              { key: 'capital', label: 'Treatment', width: '120px', render: function (r) {
                return r.capital ? <Badge tone="info">Capital</Badge> : <span className="nx-meta">Operating</span>;
              }},
              { key: 'used', label: 'Expenses', align: 'right', mono: true, muted: true, width: '92px' },
              { key: 'active', label: 'Status', width: '116px', render: function (r) {
                return <StatusPill status={r.active ? 'active' : 'inactive'} />;
              }},
              { key: 'actions', label: '', align: 'right', width: '84px', render: function () {
                return (
                  <div style={{ display: 'flex', gap: 6, justifyContent: 'flex-end' }}>
                    <IconButton label="Edit category" size={24}><Icon name="edit" size={13} /></IconButton>
                    <IconButton label="Deactivate" size={24} tone="negative"><Icon name="x" size={13} /></IconButton>
                  </div>
                );
              }},
            ]}
            rows={CATEGORIES}
          />
        </div>
        <div style={{ marginTop: 'var(--nx-s-6)' }}>
          <Alert tone="info" title="Categories are shared across the practice">
            A category marked <strong>capital</strong> posts to the asset register instead of the
            income statement. Renaming one updates it on historical expenses and in financial
            reports — create a new category instead if the meaning has changed.
          </Alert>
        </div>
      </>
    );
  } else {
    let label = 'Settings';
    GROUPS.forEach(function (g) { g.items.forEach(function (i) { if (i.key === pane) label = i.label; }); });
    body = (
      <>
        {heading(label,
          'Not drawn yet. It follows the same pattern: hairline-separated rows on the right, no cards.')}
        <SettingsRow label="Which panes are built"
          description="Company information, Addresses, Branding, Financial year, Invoice and quotation defaults, Terms and conditions, and Expense categories — between them they cover the three shapes a pane takes: a form, a block of document text, and a managed list."
          action={<Button size="sm" onClick={function () { setPane('company'); }}>See a finished pane</Button>} last />
      </>
    );
  }

  return (
    <>
      <PageHeader
        eyebrow="Settings"
        title="Practice settings"
        subtitle="Company details, document defaults, the managed lists staff choose from, and who may see what."
        actions={<Button icon={<Icon name="arrowLeft" size={15} />} onClick={function () { onNavigate('dashboard'); }}>Back</Button>}
      />

      <div style={{
        display: 'grid',
        gridTemplateColumns: '220px minmax(0, 1fr)',
        gap: 'var(--nx-s-7)', alignItems: 'start',
      }}>
        <div style={{ minWidth: 0, position: 'sticky', top: 0 }}>
          <SettingsNav groups={GROUPS} activeKey={pane} onNavigate={setPane} />
        </div>
        <div style={{ minWidth: 0, maxWidth: 760 }}>{body}</div>
      </div>
    </>
  );
}
