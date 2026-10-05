import React from 'react';

/* Recreates resources/views/finance/expenses/create.blade.php:
   header + validation alert, four cards in the main column (Basic information,
   Financial details, Associations, Receipt & notes) and a sticky sidebar
   (Amount preview, Initial status, form footer). VAT maths matches the Blade JS. */

const VAT_RATE = 15;

export function ExpenseFormScreen({ onNavigate, onSubmit }) {
  const NX = window.__nxNS();
  const { PageHeader, Card, FormField, Input, Select, Textarea, Switch, RadioPills, Button, Alert, Badge } = NX;
  const KIT = window.NexusKit || {};
  const Icon = KIT.Icon || function () { return null; };

  const [amount, setAmount] = React.useState('12480.00');
  const [vatInclusive, setVatInclusive] = React.useState(true);
  const [status, setStatus] = React.useState('draft');
  const [showErrors, setShowErrors] = React.useState(false);
  const [f, setF] = React.useState({
    description: 'Site inspection accommodation, Ondangwa',
    category: 'Subsistence',
    date: '2026-02-14',
    method: 'Company card',
    account: 'Bank Windhoek cheque ••• 8021',
    vendor: 'Protea Hotel Ondangwa',
    project: 'NX-0231 — Ondangwa reticulation design',
    receipt: 'PH-114820',
    notes: 'Two nights, 11–13 February. Approved verbally by project lead before travel.',
  });
  const set = function (k) { return function (e) { const n = Object.assign({}, f); n[k] = e.target.value; setF(n); }; };

  const money = function (v) {
    return 'NAD ' + Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };
  const raw = parseFloat(amount) || 0;
  const total = vatInclusive ? raw : raw * (1 + VAT_RATE / 100);
  const net = vatInclusive ? raw / (1 + VAT_RATE / 100) : raw;
  const vat = total - net;

  const divider = function (label) {
    return (
      <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--nx-s-4)', margin: 'var(--nx-s-6) 0 var(--nx-s-5)' }}>
        <span className="nx-eyebrow" style={{ flex: 'none' }}>{label}</span>
        <hr className="nx-rule" style={{ flex: 1 }} />
      </div>
    );
  };

  return (
    <>
      <PageHeader
        eyebrow="Finance · Expense tracking"
        title="Create expense"
        subtitle="Record a business expense, attach its receipt, and either save it as a draft or send it for approval."
        actions={<Button icon={<Icon name="arrowLeft" size={15} />} onClick={function () { onNavigate('dashboard'); }}>Back to list</Button>}
      />

      {showErrors ? (
        <div style={{ marginBottom: 'var(--nx-s-6)' }}>
          <Alert tone="error" title="Fix the following before saving" onDismiss={function () { setShowErrors(false); }}>
            <ul style={{ margin: '4px 0 0', paddingLeft: 18 }}>
              <li>A receipt or supporting document is required above NAD 10,000.</li>
              <li>Cost centre must be set when a project is selected.</li>
            </ul>
          </Alert>
        </div>
      ) : null}

      <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--nx-s-5)', alignItems: 'flex-start' }}>
        <div style={{ flex: '1 1 520px', minWidth: 0, display: 'flex', flexDirection: 'column', gap: 'var(--nx-s-5)' }}>

          <Card title="Basic information" icon={<Icon name="info" />} padding="lg">
            <FormField label="Description" required hint="What was bought, and for whom">
              <Input value={f.description} onChange={set('description')}
                placeholder="e.g. Office supplies, travel expenses, software subscription" />
            </FormField>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 'var(--nx-s-6)', marginTop: 'var(--nx-s-5)' }}>
              <FormField label="Expense category" required>
                <Select value={f.category} onChange={set('category')} placeholder="Select category"
                  options={['Travel', 'Subsistence', 'Subconsultants', 'Plant hire', 'Printing and reproduction', 'Professional fees']} />
              </FormField>
              <FormField label="Expense date" required>
                <Input type="date" value={f.date} onChange={set('date')} mono />
              </FormField>
            </div>
          </Card>

          <Card title="Financial details" icon={<Icon name="money" />} padding="lg">
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 'var(--nx-s-6)' }}>
              <FormField label="Amount" required>
                <Input value={amount} onChange={function (e) { setAmount(e.target.value); }}
                  prefix="NAD" align="right" mono placeholder="0.00" />
                <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', marginTop: 'var(--nx-s-3)' }}>
                  {['100', '500', '1000', '5000'].map(function (q) {
                    const active = amount === q;
                    return (
                      <button key={q} type="button" className="nx-focusable"
                        onClick={function () { setAmount(q); }}
                        style={{
                          padding: '3px 10px', borderRadius: 'var(--nx-r-pill)', cursor: 'pointer',
                          border: '1px solid ' + (active ? 'var(--nx-brass)' : 'var(--border)'),
                          background: active ? 'var(--nx-brass-wash-2)' : 'transparent',
                          color: active ? 'var(--nx-brass-hi)' : 'var(--muted-foreground)',
                          fontFamily: 'var(--nx-font-mono)', fontSize: 'var(--nx-fs-micro)',
                          transition: 'border-color var(--nx-dur) var(--nx-ease), color var(--nx-dur) var(--nx-ease)',
                        }}>
                        {'NAD ' + Number(q).toLocaleString('en-US')}
                      </button>
                    );
                  })}
                </div>
              </FormField>
              <FormField label="VAT treatment" hint={vatInclusive ? 'VAT is backed out of the amount entered.' : 'VAT is added to the amount entered.'}>
                <div style={{ height: 'var(--nx-control-h)', display: 'flex', alignItems: 'center' }}>
                  <Switch id="vat" checked={vatInclusive}
                    onChange={function () { setVatInclusive(!vatInclusive); }}
                    label={'Amount includes VAT (' + VAT_RATE + '%)'} />
                </div>
              </FormField>
            </div>

            {divider('Payment information')}

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 'var(--nx-s-6)' }}>
              <FormField label="Payment method">
                <Select value={f.method} onChange={set('method')} placeholder="Select method (optional)"
                  options={['Company card', 'EFT', 'Petty cash', 'Employee reimbursement']} />
              </FormField>
              <FormField label="Bank account">
                <Select value={f.account} onChange={set('account')} placeholder="Select account (optional)"
                  options={['Bank Windhoek cheque ••• 8021', 'Standard Bank call ••• 4417']} />
              </FormField>
            </div>
          </Card>

          <Card title="Associations" icon={<Icon name="link" />} padding="lg">
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 'var(--nx-s-6)' }}>
              <FormField label="Vendor" hint="Links this expense to a vendor for tracking">
                <Select value={f.vendor} onChange={set('vendor')} placeholder="Select vendor (optional)"
                  options={['Protea Hotel Ondangwa', 'Namib Plant Hire', 'Windhoek Reprographics']} />
              </FormField>
              <FormField label="Project" hint="Assigns the cost to a project">
                <Select value={f.project} onChange={set('project')} placeholder="Select project (optional)"
                  options={['NX-0231 — Ondangwa reticulation design', 'NX-0198 — MR44 pavement rehabilitation', 'NX-0176 — Erongo substation supervision']} />
              </FormField>
            </div>
          </Card>

          <Card title="Receipt and notes" icon={<Icon name="paperclip" />} padding="lg">
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))', gap: 'var(--nx-s-6)' }}>
              <FormField label="Receipt number">
                <Input value={f.receipt} onChange={set('receipt')} mono placeholder="e.g. INV-2026-001" />
              </FormField>
              <FormField label="Receipt / document">
                <div style={{
                  border: '1px dashed var(--input)', borderRadius: 'var(--nx-r-4)',
                  padding: 'var(--nx-s-5)', textAlign: 'center', cursor: 'pointer',
                }}>
                  <div style={{ display: 'flex', justifyContent: 'center', color: 'var(--nx-fg-4)', marginBottom: 6 }}>
                    <Icon name="upload" size={22} />
                  </div>
                  <div style={{ fontSize: 'var(--nx-fs-body)', fontWeight: 500 }}>Click to upload or drag and drop</div>
                  <div className="nx-meta" style={{ marginTop: 2 }}>PDF, JPG or PNG, up to 10 MB</div>
                </div>
              </FormField>
            </div>
            <div style={{ marginTop: 'var(--nx-s-5)' }}>
              <FormField label="Notes">
                <Textarea value={f.notes} onChange={set('notes')} rows={3}
                  placeholder="Additional detail about this expense" />
              </FormField>
            </div>
          </Card>
        </div>

        <div style={{ flex: '1 1 300px', minWidth: 0 }}>
          <div style={{ position: 'sticky', top: 0, display: 'flex', flexDirection: 'column', gap: 'var(--nx-s-5)' }}>
            <Card title="Amount preview" icon={<Icon name="calculator" />} emphasis padding="lg">
              <div style={{ textAlign: 'center', paddingBottom: 'var(--nx-s-5)' }}>
                <div className="nx-eyebrow" style={{ marginBottom: 8 }}>Total amount</div>
                <div className="nx-num" style={{ fontSize: 'var(--nx-fs-2xl)', lineHeight: 1, color: 'var(--nx-brass-hi)' }}>
                  {money(total)}
                </div>
              </div>
              <hr className="nx-rule" />
              <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--nx-s-4)', paddingTop: 'var(--nx-s-4)', textAlign: 'center' }}>
                <div>
                  <div className="nx-money" style={{ fontWeight: 600 }}>{money(net)}</div>
                  <div className="nx-eyebrow" style={{ marginTop: 3 }}>Excl. VAT</div>
                </div>
                <div>
                  <div className="nx-money" style={{ fontWeight: 600 }}>{money(vat)}</div>
                  <div className="nx-eyebrow" style={{ marginTop: 3 }}>{'VAT ' + VAT_RATE + '%'}</div>
                </div>
              </div>
              <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', marginTop: 'var(--nx-s-5)' }}>
                <Badge>{f.category}</Badge>
                <Badge mono>NX-0231</Badge>
              </div>
            </Card>

            <Card title="Initial status" icon={<Icon name="flag" />} padding="lg">
              <RadioPills name="status" value={status} onChange={setStatus} options={[
                { value: 'draft', label: 'Draft', description: 'Save for later', icon: <Icon name="file" /> },
                { value: 'pending', label: 'Submit', description: 'For approval', icon: <Icon name="send" /> },
              ]} />
              <div style={{ marginTop: 'var(--nx-s-4)' }}>
                <Alert tone="info">
                  <strong>Draft</strong> saves the expense for review. <strong>Submit</strong> sends it to the
                  project lead, then to finance above NAD 10,000.
                </Alert>
              </div>
            </Card>

            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 10 }}>
              <Button block onClick={function () { onNavigate('dashboard'); }}>Cancel</Button>
              <Button block variant="primary" icon={<Icon name="save" size={15} />}
                onClick={function () { if (status === 'pending' && !showErrors) { setShowErrors(true); } else { onSubmit(status); } }}>
                {status === 'pending' ? 'Submit expense' : 'Create expense'}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
