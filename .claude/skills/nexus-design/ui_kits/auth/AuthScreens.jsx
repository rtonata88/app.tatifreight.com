import React from 'react';

function Frame({ title, subtitle, children, footer }) {
  const NX = window.__nxNS();
  const { Card } = NX;
  return (
    <div style={{
      minHeight: '100%', display: 'grid', gridTemplateColumns: 'minmax(0, 1fr) minmax(0, 1fr)',
      background: 'var(--background)',
    }}>
      <aside style={{
        display: 'flex', flexDirection: 'column', justifyContent: 'space-between',
        padding: 'var(--nx-s-10) var(--nx-s-9)',
        background: 'var(--secondary)', borderRight: '1px solid var(--border)',
      }}>
        <div style={{ fontFamily: 'var(--nx-font-display)', fontWeight: 800, fontSize: 26, letterSpacing: '0.02em' }}>Nexus</div>
        <div>
          <p style={{
            margin: 0, maxWidth: 420, textWrap: 'pretty',
            fontFamily: 'var(--nx-font-display)', fontWeight: 500,
            fontSize: 26, lineHeight: 1.35, color: 'var(--foreground)', letterSpacing: '-0.015em',
          }}>
            Projects, people and money for a Namibian consulting practice — in one ledger.
          </p>
          <hr className="nx-rule-brass" style={{ margin: 'var(--nx-s-6) 0 var(--nx-s-5)', maxWidth: 420 }} />
          <div className="nx-meta" style={{ maxWidth: 420 }}>
            Access is granted by role. Every sign-in and record change is logged for audit.
          </div>
        </div>
        <div className="nx-mono" style={{ color: 'var(--muted-foreground)' }}>Windhoek · NAD · v2.0</div>
      </aside>

      <main style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 'var(--nx-s-9) var(--nx-s-8)' }}>
        <div style={{ width: '100%', maxWidth: 380 }}>
          <h1 className="nx-h2" style={{ marginBottom: 8 }}>{title}</h1>
          <p style={{
            margin: '0 0 var(--nx-s-8)', textWrap: 'pretty',
            fontFamily: 'var(--nx-font-body)',
            fontSize: 'var(--nx-fs-md)', color: 'var(--muted-foreground)',
          }}>{subtitle}</p>
          {children}
          {footer ? <div style={{ marginTop: 'var(--nx-s-7)' }}>{footer}</div> : null}
        </div>
      </main>
    </div>
  );
}

export function LoginScreen({ onNavigate, onSubmit }) {
  const NX = window.__nxNS();
  const { FormField, Input, Checkbox, Button, Alert } = NX;
  const [email, setEmail] = React.useState('selma.kaunatjike@example.na');
  const [pwd, setPwd] = React.useState('');
  const [remember, setRemember] = React.useState(true);
  const [error, setError] = React.useState(false);

  return (
    <Frame title="Sign in" subtitle="Use the work address your account was created with."
      footer={
        <div className="nx-meta">
          No account yet? <a href="#" onClick={function (e) { e.preventDefault(); onNavigate('register'); }}>Register</a>
        </div>
      }>
      {error ? (
        <div style={{ marginBottom: 'var(--nx-s-6)' }}>
          <Alert tone="error" title="Those details do not match our records">
            Check the address and password, then try again. Five failed attempts locks the account for 15 minutes.
          </Alert>
        </div>
      ) : null}
      <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--nx-s-6)' }}>
        <FormField label="Email address" htmlFor="email">
          <Input id="email" type="email" value={email} onChange={function (e) { setEmail(e.target.value); }} />
        </FormField>
        <FormField label="Password" htmlFor="pwd">
          <Input id="pwd" type="password" value={pwd} placeholder="••••••••••"
            onChange={function (e) { setPwd(e.target.value); }} invalid={error} />
        </FormField>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 16 }}>
          <Checkbox id="remember" checked={remember} onChange={function () { setRemember(!remember); }} label="Remember this device" />
          <Button variant="text" onClick={function () { onNavigate('forgot'); }}>Forgot password</Button>
        </div>
        <Button variant="primary" size="lg" block
          onClick={function () { if (!pwd) { setError(true); } else { setError(false); onSubmit(); } }}>
          Sign in
        </Button>
      </div>
    </Frame>
  );
}

export function ForgotScreen({ onNavigate }) {
  const NX = window.__nxNS();
  const { FormField, Input, Button, Alert } = NX;
  const [sent, setSent] = React.useState(false);
  return (
    <Frame title="Reset your password" subtitle="We will send a reset link to your work address."
      footer={<Button variant="text" onClick={function () { onNavigate('login'); }}>Back to sign in</Button>}>
      {sent ? (
        <Alert tone="success" title="Reset link sent">
          Check selma.kaunatjike@example.na. The link expires in 60 minutes.
        </Alert>
      ) : (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--nx-s-6)' }}>
          <FormField label="Email address" hint="Must match the address on your account">
            <Input type="email" value="selma.kaunatjike@example.na" onChange={function () {}} />
          </FormField>
          <Button variant="primary" size="lg" block onClick={function () { setSent(true); }}>Send reset link</Button>
        </div>
      )}
    </Frame>
  );
}

export function RegisterScreen({ onNavigate }) {
  const NX = window.__nxNS();
  const { FormField, Input, Button, Alert } = NX;
  return (
    <Frame title="Create an account" subtitle="You will be asked to verify your email address before signing in."
      footer={<Button variant="text" onClick={function () { onNavigate('login'); }}>Back to sign in</Button>}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--nx-s-6)' }}>
        <FormField label="Name"><Input value="" placeholder="Tangeni Shipanga" onChange={function () {}} /></FormField>
        <FormField label="Email"><Input type="email" value="" placeholder="name@example.na" onChange={function () {}} /></FormField>
        <FormField label="Password" hint="At least eight characters">
          <Input type="password" value="" placeholder="••••••••••" onChange={function () {}} />
        </FormField>
        <FormField label="Confirm password">
          <Input type="password" value="" placeholder="••••••••••" onChange={function () {}} />
        </FormField>
        <Alert tone="info">
          A new account carries no permissions until an administrator assigns a role.
        </Alert>
        <Button variant="primary" size="lg" block>Register</Button>
      </div>
    </Frame>
  );
}
